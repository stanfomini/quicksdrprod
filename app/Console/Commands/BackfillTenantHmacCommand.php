<?php

namespace App\Console\Commands;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Services\Provisioning\ForgeClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('tenants:backfill-hmac {--tenant= : Target a single tenant by subdomain} {--apply : Actually write to Forge (default is dry-run)} {--no-refresh-config : Skip running config:clear on the tenant site after env update}')]
#[Description("Ensure every live tenant's Forge .env has STATS_HMAC_KEY set to the value stored on the Tenant row. Idempotent: skips tenants whose .env already has the correct key. Defaults to dry-run — pass --apply to actually write.")]
class BackfillTenantHmacCommand extends Command
{
    public function handle(ForgeClient $forge): int
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

        $stats = ['ok' => 0, 'wrote' => 0, 'skipped' => 0, 'errors' => 0];

        foreach ($tenants as $tenant) {
            try {
                $action = $this->processTenant($tenant, $forge, $apply, $refreshConfig);
                $stats[$action]++;
            } catch (Throwable $e) {
                $stats['errors']++;
                $this->error("  [{$tenant->subdomain}] ERROR: ".$e->getMessage());
            }
        }

        $this->newLine();
        $this->line("[{$mode}] done — ok={$stats['ok']} wrote={$stats['wrote']} skipped={$stats['skipped']} errors={$stats['errors']}");

        if (! $apply && $stats['wrote'] > 0) {
            $this->warn('Re-run with --apply to actually write the '.$stats['wrote'].' pending change(s).');
        }

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return 'ok'|'wrote'|'skipped'
     */
    private function processTenant(Tenant $tenant, ForgeClient $forge, bool $apply, bool $refreshConfig): string
    {
        $expected = (string) ($tenant->stats_hmac_key ?? '');

        if ($expected === '') {
            if (! $apply) {
                $this->line("  [{$tenant->subdomain}] stats_hmac_key is NULL on Tenant row — would generate a new one.");

                return 'wrote';
            }

            $tenant->stats_hmac_key = bin2hex(random_bytes(32));
            $tenant->save();
            $expected = (string) $tenant->stats_hmac_key;
            $this->info("  [{$tenant->subdomain}] generated new stats_hmac_key on Tenant row.");
        }

        $currentEnv = $forge->siteEnvironment($tenant->forge_server_id, $tenant->forge_site_id);
        $currentValue = $this->extractEnvValue($currentEnv, 'STATS_HMAC_KEY');

        if ($currentValue === $expected) {
            $this->line("  [{$tenant->subdomain}] already correct — skipping.");

            return 'ok';
        }

        $newEnv = $this->upsertEnvLines($currentEnv, [
            'STATS_HMAC_KEY' => $expected,
            'ORCHESTRATOR_URL' => rtrim(config('app.url', 'https://quicksdr.com'), '/'),
            'APP_MODE' => 'tenant',
        ]);

        if (! $apply) {
            $missing = $currentValue === '' ? 'MISSING' : 'MISMATCH';
            $this->comment("  [{$tenant->subdomain}] STATS_HMAC_KEY {$missing} — would write (server={$tenant->forge_server_id} site={$tenant->forge_site_id}).");

            return 'wrote';
        }

        $forge->updateSiteEnvironment($tenant->forge_server_id, $tenant->forge_site_id, $newEnv);
        $this->info("  [{$tenant->subdomain}] wrote env.");

        if ($refreshConfig) {
            try {
                $forge->executeSiteCommand(
                    $tenant->forge_server_id,
                    $tenant->forge_site_id,
                    'php artisan config:clear',
                );
                $this->line("  [{$tenant->subdomain}] queued config:clear.");
            } catch (Throwable $e) {
                $this->warn("  [{$tenant->subdomain}] config:clear failed: ".$e->getMessage());
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
            } elseif (str_starts_with($value, "'") && str_ends_with($value, "'") && strlen($value) >= 2) {
                $value = substr($value, 1, -1);
            }

            return $value;
        }

        return '';
    }

    /**
     * Replace or append KEY=VALUE lines. Preserves every other line including
     * comments and blank lines. If a key is missing, appends it at the end.
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
                $out[] = $matched.'='.$pendingKeys[$matched];
                unset($pendingKeys[$matched]);
            } else {
                $out[] = $line;
            }
        }

        if ($pendingKeys !== []) {
            // Drop a trailing empty line if the file ends cleanly so we don't
            // create a gap; then add a separator + our new keys.
            if ($out !== [] && end($out) === '') {
                array_pop($out);
            }

            $out[] = '';
            $out[] = '# Orchestrator-managed (tenants:backfill-hmac)';
            foreach ($pendingKeys as $key => $value) {
                $out[] = $key.'='.$value;
            }
        }

        return implode("\n", $out)."\n";
    }
}
