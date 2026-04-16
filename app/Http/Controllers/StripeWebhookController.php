<?php

namespace App\Http\Controllers;

use App\Enums\TenantStatus;
use App\Jobs\ProvisionTenantServer;
use App\Models\Tenant;
use App\Services\Provisioning\TenantSubscriptionSyncer;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class StripeWebhookController extends CashierWebhookController
{
    public function __construct(private TenantSubscriptionSyncer $subscriptionSyncer)
    {
        parent::__construct();
    }

    /**
     * Best-effort re-sync of the subscription env snapshot pushed into the
     * tenant's Forge .env. Failures here are logged but never bubble up —
     * webhook acks must stay 200 so Stripe doesn't retry indefinitely.
     */
    private function resyncSubscriptionEnv(Tenant $tenant): void
    {
        if ($tenant->status === TenantStatus::Pending) {
            // Provisioning pipeline will write the env itself in step 8.
            return;
        }

        if (! $tenant->forge_server_id || ! $tenant->forge_site_id) {
            return;
        }

        try {
            $this->subscriptionSyncer->sync($tenant);
        } catch (Throwable $e) {
            Log::warning('stripe.webhook: subscription env sync failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a completed Checkout Session. Links the Stripe customer + subscription
     * back to the Tenant (looked up by client_reference_id = tenant.id). This fires
     * BEFORE invoice.payment_succeeded for the same payment, so by the time the
     * invoice handler runs, the tenant already has its stripe_customer_id set and
     * provisioning can auto-dispatch.
     */
    protected function handleCheckoutSessionCompleted(array $payload): Response
    {
        $session = $payload['data']['object'] ?? [];
        $tenantId = $session['client_reference_id'] ?? null;
        $customerId = $session['customer'] ?? null;
        $subscriptionId = $session['subscription'] ?? null;

        if (! $tenantId || ! $customerId) {
            Log::info('stripe.checkout_session_completed: missing tenant or customer', [
                'client_reference_id' => $tenantId,
                'customer' => $customerId,
            ]);

            return $this->successMethod();
        }

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            Log::warning('stripe.checkout_session_completed: tenant not found', [
                'tenant_id' => $tenantId,
                'customer' => $customerId,
            ]);

            return $this->successMethod();
        }

        $tenant->forceFill(array_filter([
            'stripe_customer_id' => $customerId,
            'stripe_subscription_id' => $subscriptionId,
        ]))->save();

        Log::info('stripe.checkout_session_completed: tenant linked to Stripe', [
            'tenant_id' => $tenant->id,
            'customer' => $customerId,
            'subscription' => $subscriptionId,
        ]);

        // If the tenant is still Pending (not yet provisioning), kick off
        // the build now. This handles Tier 2/3 where Matthew sent a payment
        // link after the kickoff call.
        if ($tenant->status === TenantStatus::Pending) {
            ProvisionTenantServer::dispatch($tenant);

            Log::info('stripe.checkout_session_completed: dispatching provisioner post-payment', [
                'tenant_id' => $tenant->id,
            ]);
        }

        return $this->successMethod();
    }

    /**
     * Handle a successful invoice payment. On the first successful payment
     * for a Pending tenant, dispatch the provisioning pipeline.
     */
    protected function handleInvoicePaymentSucceeded(array $payload): Response
    {
        $customerId = $payload['data']['object']['customer'] ?? null;
        $invoiceId = $payload['data']['object']['id'] ?? null;

        if (! $customerId) {
            return $this->successMethod();
        }

        $tenant = Tenant::where('stripe_customer_id', $customerId)->first();

        if (! $tenant) {
            Log::info('stripe.invoice_payment_succeeded: no tenant for customer', [
                'customer' => $customerId,
                'invoice' => $invoiceId,
            ]);

            return $this->successMethod();
        }

        if ($tenant->status === TenantStatus::Pending) {
            Log::info('stripe.invoice_payment_succeeded: dispatching provisioner', [
                'tenant_id' => $tenant->id,
                'customer' => $customerId,
                'invoice' => $invoiceId,
            ]);

            ProvisionTenantServer::dispatch($tenant);
        } else {
            Log::info('stripe.invoice_payment_succeeded: recurring payment', [
                'tenant_id' => $tenant->id,
                'customer' => $customerId,
                'invoice' => $invoiceId,
                'status' => $tenant->status->value,
            ]);

            $this->resyncSubscriptionEnv($tenant);
        }

        return $this->successMethod();
    }

    /**
     * Handle subscription lifecycle updates (trial→active transition,
     * cancel_at_period_end toggle, plan changes). Re-sync the tenant's
     * env snapshot so the trial banner + Billing page stay accurate.
     */
    protected function handleCustomerSubscriptionUpdated(array $payload): Response
    {
        $customerId = $payload['data']['object']['customer'] ?? null;

        if (! $customerId) {
            return $this->successMethod();
        }

        $tenant = Tenant::where('stripe_customer_id', $customerId)->first();

        if (! $tenant) {
            return $this->successMethod();
        }

        $this->resyncSubscriptionEnv($tenant);

        return $this->successMethod();
    }

    /**
     * Handle a cancelled subscription. Move the tenant to ExportOnly with a
     * 7-day grace window. The daily sweep command destroys expired tenants.
     */
    protected function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        $customerId = $payload['data']['object']['customer'] ?? null;

        if (! $customerId) {
            return $this->successMethod();
        }

        $tenant = Tenant::where('stripe_customer_id', $customerId)->first();

        if (! $tenant) {
            Log::info('stripe.subscription_deleted: no tenant for customer', [
                'customer' => $customerId,
            ]);

            return $this->successMethod();
        }

        $tenant->forceFill([
            'status' => TenantStatus::ExportOnly,
            'export_only_until' => now()->addDays(7),
        ])->save();

        Log::warning('stripe.subscription_deleted: tenant moved to export-only', [
            'tenant_id' => $tenant->id,
            'customer' => $customerId,
            'export_only_until' => $tenant->export_only_until->toIso8601String(),
        ]);

        return $this->successMethod();
    }

    /**
     * Handle a failed invoice payment. Log only — grace-period handling
     * comes in a later phase.
     */
    protected function handleInvoicePaymentFailed(array $payload): Response
    {
        $customerId = $payload['data']['object']['customer'] ?? null;
        $invoiceId = $payload['data']['object']['id'] ?? null;

        if (! $customerId) {
            return $this->successMethod();
        }

        $tenant = Tenant::where('stripe_customer_id', $customerId)->first();

        Log::warning('stripe.invoice_payment_failed', [
            'tenant_id' => $tenant?->id,
            'customer' => $customerId,
            'invoice' => $invoiceId,
        ]);

        return $this->successMethod();
    }
}
