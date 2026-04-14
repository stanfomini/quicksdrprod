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
     * Build a Stripe Checkout session for a pending tenant and return the
     * redirect URL. Tier 4 is "contact sales" and must not reach this flow.
     */
    public function createSession(Tenant $tenant): string
    {
        if ($tenant->tier === TenantTier::Tier4) {
            throw new InvalidArgumentException('Tier 4 is handled via the contact-sales form, not Stripe Checkout.');
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

        if ($tenant->tier->freeTrialMonths() > 0) {
            $params['subscription_data'] = [
                'trial_period_days' => $tenant->tier->freeTrialMonths() * 30,
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
