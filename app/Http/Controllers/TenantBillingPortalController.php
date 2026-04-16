<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\Provisioning\TenantBilling;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Bounces a tenant admin from their tenant app over to their Stripe Customer
 * Portal. Called from the tenant-side "Billing" link; protected by an HMAC
 * signature over (tenant_id + expires) using the tenant's stats_hmac_key,
 * which is only known to the orchestrator + that one tenant server.
 *
 * Flow:
 *   tenant app → GET /billing/portal/{tenant}?expires=...&sig=...
 *   → orchestrator verifies HMAC(tenant_id.expires, stats_hmac_key)
 *   → creates Stripe Billing Portal session
 *   → 302 → Stripe's hosted page
 *
 * Customer never sees the orchestrator UI — Stripe hosts the whole billing
 * experience. After they're done, Stripe returns them to their tenant app.
 */
class TenantBillingPortalController extends Controller
{
    public function __invoke(Request $request, Tenant $tenant, TenantBilling $billing): RedirectResponse
    {
        $expires = (int) $request->query('expires', 0);
        $sig = (string) $request->query('sig', '');

        if ($expires < time()) {
            abort(410, 'Billing link expired — ask your admin for a fresh one.');
        }

        if (! $tenant->stats_hmac_key) {
            abort(409, 'This tenant is not fully provisioned yet.');
        }

        $expected = hash_hmac('sha256', "{$tenant->id}.{$expires}", $tenant->stats_hmac_key);

        if (! hash_equals($expected, $sig)) {
            abort(403, 'Invalid signature.');
        }

        if (! $tenant->stripe_customer_id) {
            abort(409, 'No Stripe customer on file. Contact support.');
        }

        $returnUrl = $tenant->appUrl().'/settings/profile';
        $portalUrl = $billing->billingPortalUrl($tenant, $returnUrl);

        return redirect()->away($portalUrl);
    }
}
