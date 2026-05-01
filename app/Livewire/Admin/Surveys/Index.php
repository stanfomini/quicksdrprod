<?php

namespace App\Livewire\Admin\Surveys;

use App\Enums\SurveyAudience;
use App\Enums\SurveyStatus;
use App\Models\Survey;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Surveys')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $audience = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $sortField = 'updated_at';

    #[Url]
    public string $sortDirection = 'desc';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedAudience(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
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
        $this->reset(['search', 'audience', 'status']);
        $this->resetPage();
    }

    #[Computed]
    public function audienceOptions(): array
    {
        return array_map(
            fn (SurveyAudience $a) => ['value' => $a->value, 'label' => $a->label()],
            SurveyAudience::cases()
        );
    }

    #[Computed]
    public function statusOptions(): array
    {
        return array_map(
            fn (SurveyStatus $s) => ['value' => $s->value, 'label' => $s->label()],
            SurveyStatus::cases()
        );
    }

    protected function query(): Builder
    {
        $sortable = ['title', 'audience', 'status', 'created_at', 'updated_at'];
        $sortField = in_array($this->sortField, $sortable, true) ? $this->sortField : 'updated_at';
        $sortDirection = $this->sortDirection === 'asc' ? 'asc' : 'desc';

        return Survey::query()
            ->withCount(['questions', 'responses'])
            ->when($this->search !== '', function (Builder $q): void {
                $term = '%'.$this->search.'%';
                $q->where(function (Builder $q) use ($term): void {
                    $q->where('title', 'like', $term)
                        ->orWhere('slug', 'like', $term);
                });
            })
            ->when($this->audience !== '', fn (Builder $q) => $q->where('audience', $this->audience))
            ->when($this->status !== '', fn (Builder $q) => $q->where('status', $this->status))
            ->orderBy($sortField, $sortDirection);
    }

    public function render()
    {
        /** @var LengthAwarePaginator $surveys */
        $surveys = $this->query()->paginate(25);

        return view('livewire.admin.surveys.index', [
            'surveys' => $surveys,
        ])->layout('layouts.app');
    }
}
