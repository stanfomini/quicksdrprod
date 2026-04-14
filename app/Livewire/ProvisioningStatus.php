<?php

namespace App\Livewire;

use App\Models\Tenant;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ProvisioningStatus extends Component
{
    public int $tenantId;

    public function mount(int $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    #[Computed]
    public function tenant(): ?Tenant
    {
        return Tenant::find($this->tenantId);
    }

    public function render()
    {
        return view('livewire.provisioning-status');
    }
}
