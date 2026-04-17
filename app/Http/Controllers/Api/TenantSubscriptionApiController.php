<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

/**
 * Orchestrator → tenant pull endpoint for live Stripe subscription state.
 *
 * The tenant's /settings/billing page calls this on render because the
 * subscription snapshot written into the tenant's .env at provisioning time
 * (TIER, STRIPE_SUBSCRIPTION_STATUS, TRIAL_ENDS_AT, STRIPE_CURRENT_PERIOD_END)
 * never updates when Stripe events land on the orchestrator. Protected by
 * the shared stats_hmac_key so only the tenant server can read its own
 * state.
 *
 * URL shape (tenant builds this): /api/tenant/{subdomain}/subscription?expires=X&sig=Y
 * where sig = hash_hmac('sha256', "{subdomain}.{expires}", stats_hmac_key).
 * Same HMAC scheme BillingPortalRedirectController already uses.
 */
class TenantSubscriptionApiController extends Controller
{
    public function show(string $subdomain, Request $request): JsonResponse
    {
        $expires = (int) $request->query('expires', 0);
        $sig = (string) $request->query('sig', '');

        if ($expires === 0 || $sig === '') {
            return response()->json(['error' => 'missing signature'], 401);
        }

        if ($expires < time()) {
            return response()->json(['error' => 'expired'], 401);
        }

        $tenant = Tenant::where('subdomain', $subdomain)->first();

        if (! $tenant || ! $tenant->stats_hmac_key) {
            return response()->json(['error' => 'unknown tenant'], 404);
        }

        $expected = hash_hmac('sha256', "{$subdomain}.{$expires}", $tenant->stats_hmac_key);

        if (! hash_equals($expected, $sig)) {
            return response()->json(['error' => 'invalid signature'], 401);
        }

        $secret = config('services.stripe.secret');

        if (! $secret || ! $tenant->stripe_customer_id) {
            return response()->json([
                'tier' => $tenant->tier->value,
                'status' => null,
                'trial_ends_at' => null,
                'current_period_end' => null,
                'cancel_at_period_end' => false,
                'has_subscription' => false,
            ]);
        }

        try {
            $stripe = new StripeClient($secret);

            $subscription = null;
            if ($tenant->stripe_subscription_id) {
                $subscription = $stripe->subscriptions->retrieve($tenant->stripe_subscription_id);
            } else {
                $subs = $stripe->subscriptions->all([
                    'customer' => $tenant->stripe_customer_id,
                    'limit' => 1,
                    'status' => 'all',
                ]);
                $subscription = $subs->data[0] ?? null;
            }

            return response()->json([
                'tier' => $tenant->tier->value,
                'status' => $subscription?->status,
                'trial_ends_at' => isset($subscription->trial_end) && $subscription->trial_end
                    ? date('c', $subscription->trial_end)
                    : null,
                'current_period_end' => isset($subscription->current_period_end)
                    ? date('c', $subscription->current_period_end)
                    : null,
                'cancel_at_period_end' => (bool) ($subscription?->cancel_at_period_end ?? false),
                'has_subscription' => $subscription !== null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('tenant subscription api: stripe fetch failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'tier' => $tenant->tier->value,
                'status' => null,
                'trial_ends_at' => null,
                'current_period_end' => null,
                'cancel_at_period_end' => false,
                'has_subscription' => false,
                'stripe_error' => true,
            ], 200);
        }
    }
}
