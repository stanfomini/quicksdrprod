<?php

namespace App\Livewire\Admin\Tenants;

use App\Enums\TenantStatus;
use App\Enums\TenantTier;
use App\Models\Tenant;
use App\Models\TenantStat;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Tenants')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $tier = '';

    #[Url]
    public string $sortField = 'created_at';

    #[Url]
    public string $sortDirection = 'desc';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedTier(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status', 'tier']);
        $this->resetPage();
    }

    #[Computed]
    public function statusOptions(): array
    {
        return array_map(
            fn (TenantStatus $s) => ['value' => $s->value, 'label' => $s->label()],
            TenantStatus::cases()
        );
    }

    #[Computed]
    public function tierOptions(): array
    {
        return array_map(
            fn (TenantTier $t) => ['value' => $t->value, 'label' => $t->label()],
            TenantTier::cases()
        );
    }

    protected function query(): Builder
    {
        $sortable = ['subdomain', 'tier', 'status', 'created_at', 'provisioned_at'];
        $sortField = in_array($this->sortField, $sortable, true) ? $this->sortField : 'created_at';
        $sortDirection = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        return Tenant::query()
            ->when($this->search !== '', function (Builder $q): void {
                $term = '%'.$this->search.'%';
                $q->where(function (Builder $q) use ($term): void {
                    $q->where('business_name', 'like', $term)
                        ->orWhere('subdomain', 'like', $term)
                        ->orWhere('admin_email', 'like', $term);
                });
            })
            ->when($this->status !== '', fn (Builder $q) => $q->where('status', $this->status))
            ->when($this->tier !== '', fn (Builder $q) => $q->where('tier', $this->tier))
            ->orderBy($sortField, $sortDirection);
    }

    public function render()
    {
        /** @var LengthAwarePaginator $tenants */
        $tenants = $this->query()->paginate(25);

        $tenantIds = collect($tenants->items())->pluck('id');
        $latestIngests = TenantStat::query()
            ->whereIn('tenant_id', $tenantIds)
            ->selectRaw('tenant_id, max(ingested_at) as last_ingested_at')
            ->groupBy('tenant_id')
            ->pluck('last_ingested_at', 'tenant_id');

        return view('livewire.admin.tenants.index', [
            'tenants' => $tenants,
            'latestIngests' => $latestIngests,
        ])->layout('layouts.app');
    }
}
