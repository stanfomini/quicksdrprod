<?php

namespace App\Livewire\Admin\Tenants;

use App\Enums\TenantStatus;
use App\Jobs\DestroyTenantServer;
use App\Jobs\ProvisionTenantServer;
use App\Models\Tenant;
use App\Services\Provisioning\StripeCheckout;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Tenant detail')]
class Show extends Component
{
    public Tenant $tenant;

    public function mount(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function retryProvisioning(): void
    {
        if ($this->tenant->status !== TenantStatus::Failed) {
            $this->dispatch('toast', type: 'warning', message: 'Retry is only available for failed tenants.');

            return;
        }

        ProvisionTenantServer::dispatch($this->tenant);

        $step = (int) ($this->tenant->status_step ?? 0);
        $this->dispatch('toast', type: 'success', message: "Re-queued; steps {$step} onward will run.");

        $this->tenant->refresh();
    }

    /**
     * Kick off provisioning on a Pending tenant. Used for Tier 2/3 tenants
     * that didn't auto-provision at signup (kickoff-call-first flow), or
     * any tenant created manually via /admin/tenants/create without the
     * "Provision immediately" checkbox.
     */
    public function provisionNow(): void
    {
        if ($this->tenant->status !== TenantStatus::Pending) {
            $this->dispatch('toast', type: 'warning', message: 'Provision now is only available for Pending tenants. Use Retry for failed ones.');

            return;
        }

        ProvisionTenantServer::dispatch($this->tenant);

        $mode = config('services.provisioning.dry_run')
            ? 'dry-run (simulated)'
            : 'REAL Forge provisioning';

        $this->dispatch('toast', type: 'success', message: "Provision dispatched — {$mode}. Watch the step log below.");

        $this->tenant->refresh();
    }

    public ?string $paymentLinkUrl = null;

    /**
     * Generate a Stripe Checkout Session URL for this tenant. The URL is
     * usable once (Stripe Checkout sessions are single-use). Matthew copies
     * or emails it to the prospect after their kickoff call.
     */
    public function generatePaymentLink(StripeCheckout $checkout): void
    {
        if ($this->tenant->stripe_customer_id) {
            $this->dispatch('toast', type: 'warning', message: 'This tenant already has a Stripe customer. No new link needed.');

            return;
        }

        if (! $checkout->isConfigured($this->tenant->tier)) {
            $this->dispatch('toast', type: 'danger', message: 'Stripe not configured for '.$this->tenant->tier->label().'. Check STRIPE_SECRET and STRIPE_PRICE_* env vars.');

            return;
        }

        try {
            $this->paymentLinkUrl = $checkout->createSession($this->tenant);

            $this->tenant->forceFill([
                'status_detail' => [
                    ...($this->tenant->status_detail ?? []),
                    'payment_link_url' => $this->paymentLinkUrl,
                    'payment_link_generated_at' => now()->toIso8601String(),
                ],
            ])->save();

            $this->dispatch('toast', type: 'success', message: 'Payment link generated. Copy it or send it below.');
        } catch (\Throwable $e) {
            Log::error('generatePaymentLink failed', [
                'tenant_id' => $this->tenant->id,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('toast', type: 'danger', message: 'Stripe error: '.$e->getMessage());
        }
    }

    public function copyPaymentLink(): void
    {
        $url = $this->paymentLinkUrl;

        if (! $url) {
            $this->dispatch('toast', type: 'warning', message: 'Generate a payment link first.');

            return;
        }

        $this->dispatch('copy-to-clipboard', text: $url);
        $this->dispatch('toast', type: 'success', message: 'Copied to clipboard.');
    }

    public function emailPaymentLink(): void
    {
        $url = $this->paymentLinkUrl;

        if (! $url) {
            $this->dispatch('toast', type: 'warning', message: 'Generate a payment link first.');

            return;
        }

        $tenant = $this->tenant;
        $tier = $tenant->tier;
        $setupFee = $tier->setupFeeCents() > 0 ? '$'.number_format($tier->setupFeeCents() / 100).' setup + ' : '';
        $monthly = '$'.number_format($tier->monthlyPriceCents() / 100).'/mo';

        Mail::raw(
            "Hi {$tenant->admin_name},\n\n"
            ."Great talking with you! Here's your QuickSDR payment link to get started:\n\n"
            ."{$url}\n\n"
            ."Plan: {$tier->label()} ({$setupFee}{$monthly})\n"
            ."Your subdomain: {$tenant->subdomain}.quicksdr.com\n\n"
            ."Once payment completes, your dedicated server will start building automatically. "
            ."You'll receive login credentials by email within 15 minutes.\n\n"
            ."Questions? Reply to this email or call me directly.\n\n"
            ."— Matthew\nQuickSDR",
            fn ($m) => $m
                ->to($tenant->admin_email)
                ->subject("QuickSDR — complete your {$tier->label()} setup")
                ->replyTo(config('mail.from.address'))
        );

        $this->dispatch('toast', type: 'success', message: "Payment link emailed to {$tenant->admin_email}.");
    }

    public function destroyServer(): void
    {
        DestroyTenantServer::dispatch($this->tenant);

        $this->dispatch('toast', type: 'success', message: 'Destroy job dispatched.');

        $this->tenant->refresh();
    }

    /**
     * Returns the decrypted admin signup URL for clipboard copy. Called
     * imperatively from the view via a wire:click so we don't serialize the
     * plaintext into the page for no reason.
     */
    public function adminSignupUrl(): ?string
    {
        return $this->tenant->admin_signup_url;
    }

    public function copySignupUrl(): void
    {
        $url = $this->adminSignupUrl();

        if ($url === null || $url === '') {
            $this->dispatch('toast', type: 'warning', message: 'No signup URL on this tenant.');

            return;
        }

        $this->dispatch('copy-to-clipboard', text: $url);
    }

    #[Computed]
    public function log(): array
    {
        return $this->tenant->status_detail['log'] ?? [];
    }

    #[Computed]
    public function recentStats()
    {
        return $this->tenant->stats()
            ->latest('period_start')
            ->limit(48)
            ->get()
            ->reverse()
            ->values();
    }

    public function render()
    {
        return view('livewire.admin.tenants.show')->layout('layouts.app');
    }
}
