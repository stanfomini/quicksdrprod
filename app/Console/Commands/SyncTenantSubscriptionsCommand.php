<?php

namespace App\Console\Commands;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Services\Provisioning\TenantSubscriptionSyncer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('tenants:sync-subscriptions {--tenant= : Target a single tenant by subdomain}')]
#[Description("Pull each tenant's current Stripe subscription state and push TIER / STATUS / TRIAL_ENDS_AT / CURRENT_PERIOD_END / CANCEL_AT_PERIOD_END into their Forge .env. Runs config:clear on each tenant site after the write. Safe to re-run — no-ops when values already match.")]
class SyncTenantSubscriptionsCommand extends Command
{
    public function handle(TenantSubscriptionSyncer $syncer): int
    {
        $query = Tenant::query()
            ->whereNotNull('forge_server_id')
            ->whereNotNull('forge_site_id')
            ->whereNotIn('status', [TenantStatus::Destroyed, TenantStatus::Pending]);

        if ($subdomain = $this->option('tenant')) {
            $query->where('subdomain', $subdomain);
        }

        $tenants = $query->orderBy('subdomain')->get();

        if ($tenants->isEmpty()) {
            $this->warn('No eligible tenants found.');

            return self::SUCCESS;
        }

        $this->line('Syncing '.$tenants->count().' tenant(s)…');

        $synced = 0;
        $noop = 0;
        $errors = 0;

        foreach ($tenants as $tenant) {
            try {
                if ($syncer->sync($tenant)) {
                    $this->info("  [{$tenant->subdomain}] synced");
                    $synced++;
                } else {
                    $this->line("  [{$tenant->subdomain}] no change");
                    $noop++;
                }
            } catch (Throwable $e) {
                $this->error("  [{$tenant->subdomain}] ERROR: ".$e->getMessage());
                $errors++;
            }
        }

        $this->newLine();
        $this->line("done — synced={$synced} noop={$noop} errors={$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
