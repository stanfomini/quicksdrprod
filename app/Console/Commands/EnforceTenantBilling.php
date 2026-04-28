<?php

namespace App\Console\Commands;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Services\Provisioning\TenantBilling;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Signature('tenants:enforce-billing {--dry-run : Report only; do not cancel any subscriptions} {--grace-days= : Override config(billing.past_due_grace_days)}')]
#[Description('Safety net for failed payments: cancels Stripe subscriptions stuck in past_due / unpaid for longer than the configured grace window (default 7 days). The customer.subscription.deleted webhook then moves the tenant to ExportOnly and marks open invoices uncollectible. Runs daily.')]
class EnforceTenantBilling extends Command
{
    public function handle(TenantBilling $billing): int
    {
        $graceDays = (int) ($this->option('grace-days') ?? config('billing.past_due_grace_days', 7));
        $dryRun = (bool) $this->option('dry-run');
        $threshold = Carbon::now()->subDays($graceDays);

        $tenants = Tenant::query()
            ->whereIn('status', [TenantStatus::Ready, TenantStatus::Provisioning])
            ->whereNotNull('stripe_subscription_id')
            ->orderBy('subdomain')
            ->get();

        if ($tenants->isEmpty()) {
            $this->line('No eligible tenants.');

            return self::SUCCESS;
        }

        $this->line("Scanning {$tenants->count()} tenant(s) — grace={$graceDays}d".($dryRun ? ' [DRY RUN]' : ''));

        $cancelled = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($tenants as $tenant) {
            try {
                $sub = $billing->subscription($tenant);

                if (! $sub || ! in_array($sub['status'], ['past_due', 'unpaid'], true)) {
                    $skipped++;

                    continue;
                }

                $periodEnd = isset($sub['current_period_end'])
                    ? Carbon::createFromTimestamp($sub['current_period_end'])
                    : null;

                if (! $periodEnd || $periodEnd->greaterThan($threshold)) {
                    $this->line("  [{$tenant->subdomain}] {$sub['status']} but within grace ({$graceDays}d) — skip");
                    $skipped++;

                    continue;
                }

                $this->warn("  [{$tenant->subdomain}] {$sub['status']} since {$periodEnd->toDateString()} — cancelling");

                Log::warning('tenants:enforce-billing: cancelling subscription past grace', [
                    'tenant_id' => $tenant->id,
                    'subdomain' => $tenant->subdomain,
                    'customer' => $tenant->stripe_customer_id,
                    'subscription' => $tenant->stripe_subscription_id,
                    'status' => $sub['status'],
                    'past_due_since' => $periodEnd->toIso8601String(),
                    'grace_days' => $graceDays,
                    'dry_run' => $dryRun,
                ]);

                if (! $dryRun) {
                    $billing->cancelSubscription($tenant);
                }

                $cancelled++;
            } catch (Throwable $e) {
                $this->error("  [{$tenant->subdomain}] ERROR: ".$e->getMessage());

                Log::warning('tenants:enforce-billing: error', [
                    'tenant_id' => $tenant->id,
                    'error' => $e->getMessage(),
                ]);

                $errors++;
            }
        }

        $this->newLine();
        $this->line("done — cancelled={$cancelled} skipped={$skipped} errors={$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
