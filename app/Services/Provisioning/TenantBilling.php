<?php

namespace App\Services\Provisioning;

use App\Models\Tenant;
use RuntimeException;
use Stripe\StripeClient;

/**
 * Read-only billing lookups for a Tenant. Pulls subscription + invoice data
 * from Stripe on demand for the admin UI and (via signed URL) the
 * tenant-side settings page.
 */
class TenantBilling
{
    private StripeClient $stripe;

    public function __construct(?string $secret = null)
    {
        $secret ??= config('services.stripe.secret');

        if (! $secret) {
            throw new RuntimeException('Stripe secret key is not configured.');
        }

        $this->stripe = new StripeClient($secret);
    }

    /**
     * @return array{status:?string, current_period_end:?int, trial_end:?int, cancel_at_period_end:?bool, default_payment_method:?string, price_id:?string}|null
     */
    public function subscription(Tenant $tenant): ?array
    {
        if (! $tenant->stripe_subscription_id) {
            return null;
        }

        $sub = $this->stripe->subscriptions->retrieve($tenant->stripe_subscription_id, []);

        return [
            'id' => $sub->id,
            'status' => $sub->status,
            'current_period_end' => $sub->current_period_end,
            'current_period_start' => $sub->current_period_start,
            'trial_end' => $sub->trial_end,
            'cancel_at_period_end' => $sub->cancel_at_period_end,
            'default_payment_method' => $sub->default_payment_method,
            'price_id' => $sub->items->data[0]?->price?->id ?? null,
            'amount_cents' => $sub->items->data[0]?->price?->unit_amount ?? null,
        ];
    }

    /**
     * @return array<int, array{id:string, amount_paid:int, amount_due:int, status:?string, paid:bool, created:int, hosted_invoice_url:?string, invoice_pdf:?string, number:?string}>
     */
    public function invoices(Tenant $tenant, int $limit = 25): array
    {
        if (! $tenant->stripe_customer_id) {
            return [];
        }

        $list = $this->stripe->invoices->all([
            'customer' => $tenant->stripe_customer_id,
            'limit' => $limit,
        ]);

        $out = [];
        foreach ($list->data as $inv) {
            $out[] = [
                'id' => $inv->id,
                'number' => $inv->number,
                'amount_paid' => $inv->amount_paid,
                'amount_due' => $inv->amount_due,
                'status' => $inv->status,
                'paid' => $inv->paid,
                'created' => $inv->created,
                'hosted_invoice_url' => $inv->hosted_invoice_url,
                'invoice_pdf' => $inv->invoice_pdf,
            ];
        }

        return $out;
    }

    /**
     * Generate a short-lived Stripe Billing Portal URL the customer can use
     * to manage their payment method, view invoices, and cancel. Stripe
     * hosts the page; we just create a session and redirect.
     */
    public function billingPortalUrl(Tenant $tenant, string $returnUrl): string
    {
        if (! $tenant->stripe_customer_id) {
            throw new RuntimeException('Tenant has no Stripe customer yet — send them a payment link first.');
        }

        $session = $this->stripe->billingPortal->sessions->create([
            'customer' => $tenant->stripe_customer_id,
            'return_url' => $returnUrl,
        ]);

        return $session->url;
    }

    /**
     * Force-cancel the tenant's Stripe subscription immediately (no proration,
     * no period-end wait). Stripe fires customer.subscription.deleted, which
     * the webhook handler turns into the existing ExportOnly transition.
     */
    public function cancelSubscription(Tenant $tenant): void
    {
        if (! $tenant->stripe_subscription_id) {
            throw new RuntimeException('Tenant has no Stripe subscription to cancel.');
        }

        $this->stripe->subscriptions->cancel($tenant->stripe_subscription_id, []);
    }

    /**
     * Mark every open (unpaid) invoice for the tenant's Stripe customer as
     * uncollectible. Used when we cancel a tenant for non-payment so we
     * don't leave dangling open invoices behind. Returns the count.
     */
    public function markOpenInvoicesUncollectible(Tenant $tenant): int
    {
        if (! $tenant->stripe_customer_id) {
            return 0;
        }

        $list = $this->stripe->invoices->all([
            'customer' => $tenant->stripe_customer_id,
            'status' => 'open',
            'limit' => 100,
        ]);

        $count = 0;
        foreach ($list->data as $invoice) {
            $this->stripe->invoices->markUncollectible($invoice->id, []);
            $count++;
        }

        return $count;
    }
}
