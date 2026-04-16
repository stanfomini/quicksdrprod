<?php

namespace App\Services\Provisioning;

use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Stripe\Subscription;
use Throwable;

/**
 * Computes a tenant's current subscription snapshot from Stripe and pushes
 * the result into the tenant's Forge .env so the tenant app (via
 * config/subscription.php) can show trial countdown, cancel state, next
 * invoice date, etc. Only the subscription-related lines are touched —
 * other env keys the tenant has added (Twilio, xAI, etc.) are preserved.
 *
 * Safe to call repeatedly: it's idempotent and no-ops when the tenant
 * has no stripe_subscription_id or when Stripe is not configured.
 */
class TenantSubscriptionSyncer
{
    /**
     * Env keys this service owns. Everything else in the tenant .env is
     * preserved verbatim.
     */
    public const KEYS = [
        'TIER',
        'STRIPE_SUBSCRIPTION_STATUS',
        'TRIAL_ENDS_AT',
        'STRIPE_CURRENT_PERIOD_END',
        'STRIPE_CANCEL_AT_PERIOD_END',
    ];

    public function __construct(private ForgeClient $forge) {}

    /**
     * Re-sync a single tenant from Stripe to its Forge .env.
     *
     * @return bool  True if we successfully wrote new env values; false if
     *               we no-oped (no subscription, no Forge site, etc.).
     */
    public function sync(Tenant $tenant): bool
    {
        if (! $tenant->forge_server_id || ! $tenant->forge_site_id) {
            return false;
        }

        $values = $this->valuesForTenant($tenant);

        if ($values === null) {
            return false;
        }

        $currentEnv = $this->forge->siteEnvironment($tenant->forge_server_id, $tenant->forge_site_id);
        $newEnv = $this->upsertEnvLines($currentEnv, $values);

        if ($newEnv === $currentEnv) {
            return false;
        }

        $this->forge->updateSiteEnvironment($tenant->forge_server_id, $tenant->forge_site_id, $newEnv);

        try {
            $this->forge->executeSiteCommand(
                $tenant->forge_server_id,
                $tenant->forge_site_id,
                'php artisan config:clear',
            );
        } catch (Throwable $e) {
            Log::warning('TenantSubscriptionSyncer: config:clear failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('TenantSubscriptionSyncer: synced', [
            'tenant_id' => $tenant->id,
            'status' => $values['STRIPE_SUBSCRIPTION_STATUS'] ?? null,
            'trial_ends_at' => $values['TRIAL_ENDS_AT'] ?? null,
        ]);

        return true;
    }

    /**
     * Return the env-key map for a tenant, or null if we cannot compute
     * one (no Stripe subscription, Stripe not configured, etc.).
     *
     * @return array<string, string>|null
     */
    public function valuesForTenant(Tenant $tenant): ?array
    {
        $tierValue = $tenant->tier?->value ?? '';

        if (! $tenant->stripe_subscription_id) {
            if (! $tierValue) {
                return null;
            }

            return [
                'TIER' => $tierValue,
                'STRIPE_SUBSCRIPTION_STATUS' => '',
                'TRIAL_ENDS_AT' => '',
                'STRIPE_CURRENT_PERIOD_END' => '',
                'STRIPE_CANCEL_AT_PERIOD_END' => 'false',
            ];
        }

        $secret = config('services.stripe.secret');

        if (! $secret) {
            return null;
        }

        try {
            $subscription = (new StripeClient($secret))
                ->subscriptions
                ->retrieve($tenant->stripe_subscription_id, []);
        } catch (ApiErrorException $e) {
            Log::warning('TenantSubscriptionSyncer: Stripe retrieve failed', [
                'tenant_id' => $tenant->id,
                'subscription' => $tenant->stripe_subscription_id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        return [
            'TIER' => $tierValue,
            'STRIPE_SUBSCRIPTION_STATUS' => (string) $subscription->status,
            'TRIAL_ENDS_AT' => $this->toIso($subscription->trial_end),
            'STRIPE_CURRENT_PERIOD_END' => $this->toIso($subscription->current_period_end),
            'STRIPE_CANCEL_AT_PERIOD_END' => $subscription->cancel_at_period_end ? 'true' : 'false',
        ];
    }

    private function toIso(?int $timestamp): string
    {
        return $timestamp ? Carbon::createFromTimestamp($timestamp)->toIso8601String() : '';
    }

    /**
     * Replace or append the owned KEY=VALUE lines. Preserves every other
     * line including comments and blank lines. Missing keys are appended
     * under a managed-block header so readers know where they came from.
     *
     * @param  array<string, string>  $updates
     */
    private function upsertEnvLines(string $env, array $updates): string
    {
        $lines = preg_split("/\r\n|\n|\r/", $env);
        $pending = $updates;
        $out = [];

        foreach ($lines as $line) {
            $matched = null;
            foreach ($pending as $key => $value) {
                if (preg_match('/^\s*'.preg_quote($key, '/').'\s*=/', $line)) {
                    $matched = $key;
                    break;
                }
            }

            if ($matched !== null) {
                $out[] = $matched.'='.$pending[$matched];
                unset($pending[$matched]);
            } else {
                $out[] = $line;
            }
        }

        if ($pending !== []) {
            if ($out !== [] && end($out) === '') {
                array_pop($out);
            }

            $out[] = '';
            $out[] = '# Orchestrator-managed subscription snapshot (TenantSubscriptionSyncer)';
            foreach ($pending as $key => $value) {
                $out[] = $key.'='.$value;
            }
        }

        return implode("\n", $out)."\n";
    }
}
