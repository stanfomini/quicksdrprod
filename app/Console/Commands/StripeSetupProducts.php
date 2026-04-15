<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stripe\StripeClient;
use Throwable;

/**
 * Idempotent one-shot command that bootstraps Stripe for QuickSDR. Creates
 * the 3 products + 5 prices the signup flow expects, plus the webhook
 * endpoint, and prints the env lines for Forge. Safe to re-run: products
 * are matched by name, prices by (product + recurring flag + amount).
 */
class StripeSetupProducts extends Command
{
    protected $signature = 'stripe:setup-products
                            {--dry : Print what would be created without calling Stripe}';

    protected $description = 'Create QuickSDR Stripe products + prices + webhook endpoint (idempotent)';

    private const WEBHOOK_URL = 'https://quicksdr.com/stripe/webhook';

    private const WEBHOOK_EVENTS = [
        'checkout.session.completed',
        'invoice.payment_succeeded',
        'invoice.payment_failed',
        'customer.subscription.deleted',
        'customer.subscription.updated',
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $secret = config('services.stripe.secret');

        if (! $dry && ! $secret) {
            $this->error('STRIPE_SECRET is not configured. Either set it and re-run, or pass --dry to preview what would happen.');

            return self::FAILURE;
        }

        $stripe = $secret ? new StripeClient($secret) : null;

        $this->info($dry ? 'DRY RUN — no Stripe API calls will be made.' : 'Running against the live Stripe account for the configured key.');
        $this->newLine();

        $env = [];

        try {
            $tier1 = $this->ensureProduct($stripe, $dry, 'QuickSDR — Self-serve (Tier 1)', [
                'description' => 'Dedicated QuickSDR instance. $350/mo recurring.',
                'metadata' => ['tier' => 'tier1'],
            ]);
            $env['STRIPE_PRICE_TIER1'] = $this->ensurePrice($stripe, $dry, $tier1, 35000, 'month', 'Tier 1 monthly');

            $tier2 = $this->ensureProduct($stripe, $dry, 'QuickSDR — Done-for-you (Tier 2)', [
                'description' => 'Dedicated instance + Twilio/AI setup. $350/mo + $1000 one-time setup.',
                'metadata' => ['tier' => 'tier2'],
            ]);
            $env['STRIPE_PRICE_TIER2'] = $this->ensurePrice($stripe, $dry, $tier2, 35000, 'month', 'Tier 2 monthly');
            $env['STRIPE_PRICE_TIER2_SETUP'] = $this->ensurePrice($stripe, $dry, $tier2, 100000, null, 'Tier 2 setup fee');

            $tier3 = $this->ensureProduct($stripe, $dry, 'QuickSDR — Multi-location + training (Tier 3)', [
                'description' => 'Tier 2 + multi-location + SDR training. $350/mo + $3000 one-time setup.',
                'metadata' => ['tier' => 'tier3'],
            ]);
            $env['STRIPE_PRICE_TIER3'] = $this->ensurePrice($stripe, $dry, $tier3, 35000, 'month', 'Tier 3 monthly');
            $env['STRIPE_PRICE_TIER3_SETUP'] = $this->ensurePrice($stripe, $dry, $tier3, 300000, null, 'Tier 3 setup fee');

            $env['STRIPE_WEBHOOK_SECRET'] = $this->ensureWebhook($stripe, $dry);
        } catch (Throwable $e) {
            $this->error('Stripe setup failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('==== Env vars — paste these into Forge → quicksdr.com → Environment ====');
        foreach ($env as $k => $v) {
            $this->line("{$k}={$v}");
        }
        $this->newLine();
        $this->info('Then redeploy quicksdr.com and the signup + webhook flow is live.');

        return self::SUCCESS;
    }

    private function ensureProduct(?StripeClient $stripe, bool $dry, string $name, array $attrs): string
    {
        if ($dry) {
            $this->line("[dry] would ensure product '{$name}'");

            return 'prod_DRYRUN_'.substr(md5($name), 0, 10);
        }

        $existing = $stripe->products->all(['limit' => 100, 'active' => true]);
        foreach ($existing->data as $p) {
            if ($p->name === $name) {
                $this->line("✓ product exists: {$name} ({$p->id})");

                return $p->id;
            }
        }

        $product = $stripe->products->create(array_merge(['name' => $name], $attrs));
        $this->info("+ product created: {$name} ({$product->id})");

        return $product->id;
    }

    private function ensurePrice(
        ?StripeClient $stripe,
        bool $dry,
        string $productId,
        int $amountCents,
        ?string $recurringInterval,
        string $nickname,
    ): string {
        if ($dry) {
            $this->line("[dry] would ensure price: {$nickname} — \${$this->formatCents($amountCents)} ".($recurringInterval ? "per {$recurringInterval}" : 'one-time'));

            return 'price_DRYRUN_'.substr(md5("{$productId}:{$amountCents}:{$recurringInterval}"), 0, 10);
        }

        $existing = $stripe->prices->all([
            'product' => $productId,
            'limit' => 100,
            'active' => true,
        ]);

        foreach ($existing->data as $p) {
            $sameRecurrence = ($recurringInterval === null && $p->type === 'one_time')
                || ($recurringInterval !== null && isset($p->recurring->interval) && $p->recurring->interval === $recurringInterval);
            if ($sameRecurrence && (int) $p->unit_amount === $amountCents && $p->currency === 'usd') {
                $this->line("✓ price exists: {$nickname} ({$p->id})");

                return $p->id;
            }
        }

        $params = [
            'product' => $productId,
            'currency' => 'usd',
            'unit_amount' => $amountCents,
            'nickname' => $nickname,
        ];

        if ($recurringInterval !== null) {
            $params['recurring'] = ['interval' => $recurringInterval];
        }

        $price = $stripe->prices->create($params);
        $this->info("+ price created: {$nickname} ({$price->id})");

        return $price->id;
    }

    private function ensureWebhook(?StripeClient $stripe, bool $dry): string
    {
        if ($dry) {
            $this->line('[dry] would ensure webhook endpoint '.self::WEBHOOK_URL);

            return 'whsec_DRYRUN_'.substr(md5(self::WEBHOOK_URL), 0, 10);
        }

        $existing = $stripe->webhookEndpoints->all(['limit' => 100]);
        foreach ($existing->data as $w) {
            if ($w->url === self::WEBHOOK_URL) {
                $this->line("✓ webhook endpoint exists: {$w->id}");
                $this->warn('  Stripe does not expose the signing secret after creation. If STRIPE_WEBHOOK_SECRET is not set, delete this endpoint in the Stripe dashboard and re-run this command.');

                return '(already exists — see warning above)';
            }
        }

        $endpoint = $stripe->webhookEndpoints->create([
            'url' => self::WEBHOOK_URL,
            'enabled_events' => self::WEBHOOK_EVENTS,
            'description' => 'QuickSDR orchestrator — signup + subscription lifecycle',
        ]);
        $this->info("+ webhook endpoint created: {$endpoint->id}");

        return $endpoint->secret;
    }

    private function formatCents(int $cents): string
    {
        return number_format($cents / 100, 2);
    }
}
