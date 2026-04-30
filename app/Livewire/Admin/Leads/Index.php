<?php

namespace App\Livewire\Admin\Leads;

use App\Models\Lead;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Leads')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $linked = '';

    #[Url]
    public string $sortField = 'last_seen_at';

    #[Url]
    public string $sortDirection = 'desc';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedLinked(): void
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
        $this->reset(['search', 'linked']);
        $this->resetPage();
    }

    protected function query(): Builder
    {
        $sortable = ['name', 'email', 'phone', 'company', 'last_seen_at', 'created_at'];
        $sortField = in_array($this->sortField, $sortable, true) ? $this->sortField : 'last_seen_at';
        $sortDirection = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        return Lead::query()
            ->with('tenant:id,business_name,subdomain')
            ->withCount('responses')
            ->when($this->search !== '', function (Builder $q): void {
                $term = '%'.$this->search.'%';
                $q->where(function (Builder $q) use ($term): void {
                    $q->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('company', 'like', $term);
                });
            })
            ->when($this->linked === 'tenant', fn (Builder $q) => $q->whereNotNull('tenant_id'))
            ->when($this->linked === 'no-tenant', fn (Builder $q) => $q->whereNull('tenant_id'))
            ->orderBy($sortField, $sortDirection);
    }

    public function render()
    {
        /** @var LengthAwarePaginator $leads */
        $leads = $this->query()->paginate(25);

        return view('livewire.admin.leads.index', [
            'leads' => $leads,
        ])->layout('layouts.app');
    }
}
