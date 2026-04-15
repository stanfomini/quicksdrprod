<?php

namespace App\Livewire\Admin\Tenants;

use App\Enums\TenantStatus;
use App\Enums\TenantTier;
use App\Jobs\ProvisionTenantServer;
use App\Models\Tenant;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Create Tenant')]
class Create extends Component
{
    public string $businessName = '';

    public string $adminName = '';

    public string $adminEmail = '';

    public string $adminPhone = '';

    public string $companyWebsite = '';

    public string $roleTitle = '';

    public string $subdomain = '';

    public string $tier = 'tier1';

    public bool $skipPayment = true;

    public bool $provisionImmediately = true;

    public string $stripeCustomerId = '';

    public string $stripeSubscriptionId = '';

    public string $subdomainStatus = '';

    public function updatedSubdomain(string $value): void
    {
        $normalized = Str::lower(trim($value));

        if ($normalized !== $value) {
            $this->subdomain = $normalized;
        }

        $this->subdomainStatus = $this->computeSubdomainStatus($normalized);
    }

    protected function computeSubdomainStatus(string $subdomain): string
    {
        if ($subdomain === '') {
            return '';
        }

        if (! preg_match('/^[a-z0-9][a-z0-9-]{1,39}$/', $subdomain)) {
            return 'invalid';
        }

        if (Tenant::isReservedSubdomain($subdomain)) {
            return 'reserved';
        }

        if (Tenant::where('subdomain', $subdomain)->exists()) {
            return 'taken';
        }

        return 'available';
    }

    public function submit()
    {
        $data = $this->validate([
            'businessName' => ['required', 'string', 'max:255'],
            'adminName' => ['required', 'string', 'max:255'],
            'adminEmail' => ['required', 'email', 'max:255', 'unique:tenants,admin_email'],
            'adminPhone' => ['nullable', 'string', 'max:32'],
            'companyWebsite' => ['nullable', 'url', 'max:255'],
            'roleTitle' => ['nullable', 'string', 'max:255'],
            'subdomain' => [
                'required',
                'string',
                'regex:/^[a-z0-9][a-z0-9-]{1,39}$/',
                'unique:tenants,subdomain',
                Rule::notIn(Tenant::reservedSubdomains()),
            ],
            'tier' => ['required', Rule::in(['tier1', 'tier2', 'tier3'])],
            'skipPayment' => ['boolean'],
            'provisionImmediately' => ['boolean'],
            'stripeCustomerId' => ['nullable', 'string', 'starts_with:cus_', 'max:255'],
            'stripeSubscriptionId' => ['nullable', 'string', 'starts_with:sub_', 'max:255'],
        ]);

        $tenant = Tenant::create([
            'business_name' => $data['businessName'],
            'admin_name' => $data['adminName'],
            'admin_email' => $data['adminEmail'],
            'admin_phone' => $data['adminPhone'] ?: null,
            'company_website' => $data['companyWebsite'] ?: null,
            'role_title' => $data['roleTitle'] ?: null,
            'subdomain' => $data['subdomain'],
            'tier' => TenantTier::from($data['tier']),
            'status' => TenantStatus::Pending,
            'dry_run' => (bool) config('services.provisioning.dry_run'),
            'stripe_customer_id' => $data['stripeCustomerId'] ?: null,
            'stripe_subscription_id' => $data['stripeSubscriptionId'] ?: null,
        ]);

        if ($data['provisionImmediately']) {
            ProvisionTenantServer::dispatch($tenant);
        }

        return $this->redirect(route('admin.tenants.show', $tenant), navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.tenants.create', [
            'tiers' => collect(TenantTier::cases())
                ->filter(fn (TenantTier $t) => $t !== TenantTier::Tier4)
                ->map(fn (TenantTier $t) => ['value' => $t->value, 'label' => $t->label()])
                ->values()
                ->all(),
            'dryRun' => (bool) config('services.provisioning.dry_run'),
        ])->layout('layouts.app');
    }
}
