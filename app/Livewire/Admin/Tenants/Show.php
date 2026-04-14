<?php

namespace App\Livewire\Admin\Tenants;

use App\Enums\TenantStatus;
use App\Jobs\DestroyTenantServer;
use App\Jobs\ProvisionTenantServer;
use App\Models\Tenant;
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
