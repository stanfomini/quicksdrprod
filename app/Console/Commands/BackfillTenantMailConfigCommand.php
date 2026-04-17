<?php

namespace App\Console\Commands;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Services\Provisioning\ForgeClient;
use App\Services\Provisioning\TenantProvisioner;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('tenants:backfill-mail-config {--tenant= : Target a single tenant by subdomain} {--apply : Actually write to Forge (default is dry-run)} {--no-refresh-config : Skip running config:clear on the tenant site after env update}')]
#[Description("Push the orchestrator's MAIL_* and MAILGUN_* env values to every live tenant's Forge .env so tenants can send their own mail (password resets, queue notifications, etc.) through the same Mailgun pipe. Idempotent: skips tenants whose .env already has matching values. Defaults to dry-run — pass --apply to actually write.")]
class BackfillTenantMailConfigCommand extends Command
{
    public function handle(ForgeClient $forge, TenantProvisioner $provisioner): int
    {
        $apply = (bool) $this->option('apply');
        $refreshConfig = ! (bool) $this->option('no-refresh-config');
        $targetSubdomain = $this->option('tenant');

        $query = Tenant::query()
            ->whereNotNull('forge_server_id')
            ->whereNotNull('forge_site_id')
            ->whereNotIn('status', [TenantStatus::Destroyed]);

        if ($targetSubdomain) {
            $query->where('subdomain', $targetSubdomain);
        }

        $tenants = $query->orderBy('subdomain')->get();

        if ($tenants->isEmpty()) {
            $this->warn($targetSubdomain
                ? "No tenant found with subdomain '{$targetSubdomain}' that has a Forge site."
                : 'No tenants with Forge sites found.');

            return self::SUCCESS;
        }

        $mode = $apply ? 'APPLY' : 'DRY-RUN';
        $this->line("[{$mode}] Scanning ".$tenants->count().' tenant(s)…');
        $this->newLine();

        $stats = ['ok' => 0, 'wrote' => 0, 'errors' => 0];

        foreach ($tenants as $tenant) {
            try {
                $action = $this->processTenant($tenant, $forge, $provisioner, $apply, $refreshConfig);
                $stats[$action]++;
            } catch (Throwable $e) {
                $stats['errors']++;
                $this->error("  [{$tenant->subdomain}] ERROR: ".$e->getMessage());
            }
        }

        $this->newLine();
        $this->line("[{$mode}] done — ok={$stats['ok']} wrote={$stats['wrote']} errors={$stats['errors']}");

        if (! $apply && $stats['wrote'] > 0) {
            $this->warn('Re-run with --apply to actually write the '.$stats['wrote'].' pending change(s).');
        }

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return 'ok'|'wrote'
     */
    private function processTenant(Tenant $tenant, ForgeClient $forge, TenantProvisioner $provisioner, bool $apply, bool $refreshConfig): string
    {
        $desired = $provisioner->mailEnvPairs($tenant);
        $desired = array_filter($desired, fn ($v) => $v !== null && $v !== '');

        if ($desired === []) {
            $this->warn("  [{$tenant->subdomain}] orchestrator has no mail config to push — skipping.");

            return 'ok';
        }

        $currentEnv = $forge->siteEnvironment($tenant->forge_server_id, $tenant->forge_site_id);

        $drift = [];
        foreach ($desired as $key => $value) {
            $current = $this->extractEnvValue($currentEnv, $key);
            if ($current !== (string) $value) {
                $drift[$key] = ['current' => $current, 'desired' => $value];
            }
        }

        if ($drift === []) {
            $this->line("  [{$tenant->subdomain}] already correct — skipping.");

            return 'ok';
        }

        $newEnv = $this->upsertEnvLines($currentEnv, $desired);

        if (! $apply) {
            $this->comment("  [{$tenant->subdomain}] would write ".count($drift).' key(s): '.implode(', ', array_keys($drift)));

            return 'wrote';
        }

        $forge->updateSiteEnvironment($tenant->forge_server_id, $tenant->forge_site_id, $newEnv);
        $this->info("  [{$tenant->subdomain}] wrote ".count($drift).' mail key(s).');

        if ($refreshConfig) {
            try {
                $forge->executeSiteCommand(
                    $tenant->forge_server_id,
                    $tenant->forge_site_id,
                    'php artisan config:clear && php artisan config:cache',
                );
                $this->line("  [{$tenant->subdomain}] queued config:clear + cache.");
            } catch (Throwable $e) {
                $this->warn("  [{$tenant->subdomain}] config refresh failed: ".$e->getMessage());
            }
        }

        return 'wrote';
    }

    /**
     * Return the value of KEY in a .env string, or '' if not set.
     * Handles quoted and unquoted values. Ignores comments.
     */
    private function extractEnvValue(string $env, string $key): string
    {
        foreach (preg_split("/\r\n|\n|\r/", $env) as $line) {
            if (! preg_match('/^\s*'.preg_quote($key, '/').'\s*=\s*(.*)$/', $line, $m)) {
                continue;
            }

            $value = trim($m[1]);

            if (str_starts_with($value, '"') && str_ends_with($value, '"') && strlen($value) >= 2) {
                $value = substr($value, 1, -1);
                $value = str_replace('\\"', '"', $value);
            } elseif (str_starts_with($value, "'") && str_ends_with($value, "'") && strlen($value) >= 2) {
                $value = substr($value, 1, -1);
            }

            return $value;
        }

        return '';
    }

    /**
     * Replace or append KEY=VALUE lines. Preserves every other line including
     * comments and blank lines. If a key is missing, appends it under a
     * clearly-labeled banner so hand-edits of the tenant .env don't get lost.
     *
     * Values that contain whitespace or shell-sensitive chars get quoted.
     *
     * @param  array<string, string>  $updates  key → raw value (unquoted)
     */
    private function upsertEnvLines(string $env, array $updates): string
    {
        $lines = preg_split("/\r\n|\n|\r/", $env);
        $pendingKeys = $updates;
        $out = [];

        foreach ($lines as $line) {
            $matched = null;
            foreach ($pendingKeys as $key => $value) {
                if (preg_match('/^\s*'.preg_quote($key, '/').'\s*=/', $line)) {
                    $matched = $key;
                    break;
                }
            }

            if ($matched !== null) {
                $out[] = $this->formatLine($matched, $pendingKeys[$matched]);
                unset($pendingKeys[$matched]);
            } else {
                $out[] = $line;
            }
        }

        if ($pendingKeys !== []) {
            if ($out !== [] && end($out) === '') {
                array_pop($out);
            }

            $out[] = '';
            $out[] = '# Orchestrator-managed (tenants:backfill-mail-config)';
            foreach ($pendingKeys as $key => $value) {
                $out[] = $this->formatLine($key, $value);
            }
        }

        return implode("\n", $out)."\n";
    }

    private function formatLine(string $key, string $value): string
    {
        $needsQuotes = (bool) preg_match('/[\s"\'#$@]/', $value);

        return $needsQuotes
            ? $key.'="'.str_replace('"', '\\"', $value).'"'
            : $key.'='.$value;
    }
}
