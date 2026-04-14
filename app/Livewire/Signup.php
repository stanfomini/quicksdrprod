<?php

namespace App\Livewire;

use App\Enums\TenantStatus;
use App\Enums\TenantTier;
use App\Models\Tenant;
use App\Services\Provisioning\StripeCheckout;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Signup extends Component
{
    public string $businessName = '';

    public string $adminName = '';

    public string $adminEmail = '';

    public string $adminPhone = '';

    public string $companyWebsite = '';

    public string $roleTitle = '';

    public string $subdomain = '';

    public string $tier = 'tier1';

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

    public function selectTier(string $tier): void
    {
        if (in_array($tier, ['tier1', 'tier2', 'tier3'], true)) {
            $this->tier = $tier;
        }
    }

    public function submit(StripeCheckout $checkout)
    {
        $data = $this->validate([
            'businessName' => ['required', 'string', 'max:255'],
            'adminName' => ['required', 'string', 'max:255'],
            'adminEmail' => ['required', 'email', 'max:255', 'unique:tenants,admin_email'],
            'adminPhone' => ['required', 'string', 'max:32'],
            'companyWebsite' => ['nullable', 'url', 'max:255'],
            'roleTitle' => ['required', 'string', 'max:255'],
            'subdomain' => [
                'required',
                'string',
                'regex:/^[a-z0-9][a-z0-9-]{1,39}$/',
                'unique:tenants,subdomain',
                Rule::notIn(Tenant::reservedSubdomains()),
            ],
            'tier' => ['required', Rule::in(['tier1', 'tier2', 'tier3'])],
        ]);

        $tier = TenantTier::from($data['tier']);

        $tenant = Tenant::create([
            'business_name' => $data['businessName'],
            'admin_name' => $data['adminName'],
            'admin_email' => $data['adminEmail'],
            'admin_phone' => $data['adminPhone'],
            'company_website' => $data['companyWebsite'] ?: null,
            'role_title' => $data['roleTitle'],
            'subdomain' => $data['subdomain'],
            'tier' => $tier,
            'status' => TenantStatus::Pending,
        ]);

        $url = $checkout->createSession($tenant);

        return $this->redirect($url);
    }

    public function render()
    {
        return view('livewire.signup', [
            'tiers' => [
                TenantTier::Tier1,
                TenantTier::Tier2,
                TenantTier::Tier3,
            ],
            'tierBenefits' => [
                'tier1' => 'Self-serve — you handle Twilio/AI setup',
                'tier2' => 'Done-for-you — we set up Twilio A2P + AI + call flows',
                'tier3' => 'Multi-location + SDR training + everything in Tier 2',
            ],
        ])->layout('layouts.signup');
    }
}
