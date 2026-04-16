<?php

namespace App\Services\Provisioning;

use App\Enums\TenantTier;
use App\Models\Tenant;
use InvalidArgumentException;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

class StripeCheckout
{
    /**
     * Whether enough Stripe config is in place to build a checkout session
     * for the given tier. Public signup falls back to a manual-onboarding
     * flow when this is false so a missing env var doesn't produce a 500.
     */
    public function isConfigured(TenantTier $tier = TenantTier::Tier1): bool
    {
        if (! config('services.stripe.secret')) {
            return false;
        }

        if (! config("services.stripe.tiers.{$tier->value}.price")) {
            return false;
        }

        if ($tier->setupFeeCents() > 0 && ! config("services.stripe.tiers.{$tier->value}.setup")) {
            return false;
        }

        return true;
    }

    /**
     * Update an existing Stripe customer's email. Used when the orchestrator
     * admin_email is edited — keeps receipts/invoices flowing to the right
     * address. Safe to call with any customer ID Stripe recognises.
     */
    public function updateCustomerEmail(string $customerId, string $email): void
    {
        $secret = config('services.stripe.secret');

        if (! $secret) {
            throw new RuntimeException('Stripe secret key is not configured.');
        }

        $stripe = new StripeClient($secret);
        $stripe->customers->update($customerId, ['email' => $email]);
    }

    /**
     * Build a one-time Stripe Checkout session for Tier 4 consulting hours.
     * Uses price_data (ad-hoc pricing) — no preconfigured Stripe price needed.
     * Hourly rate comes from TenantTier::Tier4's monthlyPriceCents() equivalent
     * (actually 16000 cents/hr hardcoded here — Tier 4 doesn't have a monthly).
     */
    public function createHourlyCheckoutSession(Tenant $tenant, int $hours): string
    {
        if ($tenant->tier !== TenantTier::Tier4) {
            throw new InvalidArgumentException('Hourly checkout is only for Tier 4 tenants.');
        }

        $secret = config('services.stripe.secret');

        if (! $secret) {
            throw new RuntimeException('Stripe secret key is not configured.');
        }

        if ($hours < 1 || $hours > 1000) {
            throw new InvalidArgumentException('Hours must be between 1 and 1000.');
        }

        $hourlyRateCents = 16000; // $160/hr
        $totalCents = $hours * $hourlyRateCents;

        $stripe = new StripeClient($secret);

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'customer_email' => $tenant->admin_email,
            'client_reference_id' => (string) $tenant->id,
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => $hourlyRateCents,
                    'product_data' => [
                        'name' => 'QuickSDR consulting',
                        'description' => "{$hours} hour".($hours === 1 ? '' : 's').' @ $160/hr',
                    ],
                ],
                'quantity' => $hours,
            ]],
            'success_url' => 'https://quicksdr.com/signup/success?session_id={CHECKOUT_SESSION_ID}&tenant_id='.$tenant->id,
            'cancel_url' => 'https://quicksdr.com/signup?cancelled=1&tenant_id='.$tenant->id,
            'metadata' => [
                'tenant_id' => (string) $tenant->id,
                'tier' => 'tier4',
                'hours' => (string) $hours,
                'total_cents' => (string) $totalCents,
            ],
        ]);

        return $session->url;
    }

    /**
     * Build a Stripe Checkout session for a pending tenant and return the
     * redirect URL. Tier 4 is "contact sales" and must not reach this flow.
     */
    public function createSession(Tenant $tenant): string
    {
        if ($tenant->tier === TenantTier::Tier4) {
            throw new InvalidArgumentException('Tier 4 uses createHourlyCheckoutSession(). Pass hours in.');
        }

        $secret = config('services.stripe.secret');

        if (! $secret) {
            throw new RuntimeException('Stripe secret key is not configured.');
        }

        $stripe = new StripeClient($secret);

        $params = [
            'mode' => 'subscription',
            'customer_email' => $tenant->admin_email,
            'client_reference_id' => (string) $tenant->id,
            'line_items' => $this->lineItemsForTier($tenant->tier),
            'success_url' => 'https://quicksdr.com/signup/success?session_id={CHECKOUT_SESSION_ID}&tenant_id='.$tenant->id,
            'cancel_url' => 'https://quicksdr.com/signup?cancelled=1&tenant_id='.$tenant->id,
            'metadata' => [
                'tenant_id' => (string) $tenant->id,
                'tier' => $tenant->tier->value,
            ],
        ];

        if ($tenant->tier->freeTrialDays() > 0) {
            $params['subscription_data'] = [
                'trial_period_days' => $tenant->tier->freeTrialDays(),
                'metadata' => [
                    'tenant_id' => (string) $tenant->id,
                    'tier' => $tenant->tier->value,
                ],
            ];
        }

        /** @var Session $session */
        $session = $stripe->checkout->sessions->create($params);

        return $session->url;
    }

    /**
     * Build the line_items array for a tier: Tier 1 = monthly only,
     * Tiers 2 & 3 = one-time setup fee + monthly.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function lineItemsForTier(TenantTier $tier): array
    {
        $recurring = config("services.stripe.tiers.{$tier->value}.price");

        if (! $recurring) {
            throw new RuntimeException("Missing Stripe recurring price for {$tier->value}.");
        }

        $items = [[
            'price' => $recurring,
            'quantity' => 1,
        ]];

        if ($tier->setupFeeCents() > 0) {
            $setup = config("services.stripe.tiers.{$tier->value}.setup");

            if (! $setup) {
                throw new RuntimeException("Missing Stripe setup price for {$tier->value}.");
            }

            $items[] = [
                'price' => $setup,
                'quantity' => 1,
            ];
        }

        return $items;
    }
}
