<?php

namespace App\Http\Controllers;

use App\Enums\TenantStatus;
use App\Jobs\ProvisionTenantServer;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends CashierWebhookController
{
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
        }

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
