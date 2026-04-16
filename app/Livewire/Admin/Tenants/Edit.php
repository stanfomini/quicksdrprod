<?php

namespace App\Livewire\Admin\Tenants;

use App\Models\Tenant;
use App\Services\Provisioning\StripeCheckout;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit Tenant')]
class Edit extends Component
{
    public Tenant $tenant;

    public string $businessName = '';

    public string $adminName = '';

    public string $adminEmail = '';

    public string $adminPhone = '';

    public string $companyWebsite = '';

    public string $roleTitle = '';

    protected string $originalEmail = '';

    public function mount(Tenant $tenant): void
    {
        $this->tenant = $tenant;
        $this->businessName = (string) $tenant->business_name;
        $this->adminName = (string) $tenant->admin_name;
        $this->adminEmail = (string) $tenant->admin_email;
        $this->adminPhone = (string) ($tenant->admin_phone ?? '');
        $this->companyWebsite = (string) ($tenant->company_website ?? '');
        $this->roleTitle = (string) ($tenant->role_title ?? '');
        $this->originalEmail = (string) $tenant->admin_email;
    }

    public function save(StripeCheckout $stripe)
    {
        $data = $this->validate([
            'businessName' => ['required', 'string', 'max:255'],
            'adminName' => ['required', 'string', 'max:255'],
            'adminEmail' => ['required', 'email', 'max:255', 'unique:tenants,admin_email,'.$this->tenant->id],
            'adminPhone' => ['nullable', 'string', 'max:32'],
            'companyWebsite' => ['nullable', 'url', 'max:255'],
            'roleTitle' => ['nullable', 'string', 'max:255'],
        ]);

        $emailChanged = $data['adminEmail'] !== $this->originalEmail;

        $this->tenant->forceFill([
            'business_name' => $data['businessName'],
            'admin_name' => $data['adminName'],
            'admin_email' => $data['adminEmail'],
            'admin_phone' => $data['adminPhone'] ?: null,
            'company_website' => $data['companyWebsite'] ?: null,
            'role_title' => $data['roleTitle'] ?: null,
        ])->save();

        if ($emailChanged && $this->tenant->stripe_customer_id) {
            try {
                $stripe->updateCustomerEmail($this->tenant->stripe_customer_id, $data['adminEmail']);
            } catch (\Throwable $e) {
                Log::warning('Stripe customer email sync failed', [
                    'tenant_id' => $this->tenant->id,
                    'customer_id' => $this->tenant->stripe_customer_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->redirect(route('admin.tenants.show', $this->tenant), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.tenants.edit')->layout('layouts.app');
    }
}
