<?php

namespace App\Livewire\Admin\Surveys;

use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Services\SurveyCsvExporter;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Survey responses')]
class Responses extends Component
{
    use WithPagination;

    public Survey $survey;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $completed = '';

    public ?int $viewingResponseId = null;

    public function mount(Survey $survey): void
    {
        $this->survey = $survey->load('questions');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCompleted(): void
    {
        $this->resetPage();
    }

    public function viewResponse(int $responseId): void
    {
        if (SurveyResponse::query()
            ->where('id', $responseId)
            ->where('survey_id', $this->survey->id)
            ->exists()
        ) {
            $this->viewingResponseId = $responseId;
        }
    }

    public function closeResponse(): void
    {
        $this->viewingResponseId = null;
    }

    public function exportCsv(SurveyCsvExporter $exporter): StreamedResponse
    {
        return $exporter->streamDownload($this->survey);
    }

    #[Computed]
    public function viewingResponse(): ?SurveyResponse
    {
        if ($this->viewingResponseId === null) {
            return null;
        }

        return SurveyResponse::query()
            ->with(['answers.question', 'survey.questions', 'lead'])
            ->where('id', $this->viewingResponseId)
            ->where('survey_id', $this->survey->id)
            ->first();
    }

    protected function query(): Builder
    {
        return SurveyResponse::query()
            ->with('lead')
            ->where('survey_id', $this->survey->id)
            ->when($this->search !== '', function (Builder $q): void {
                $term = '%'.$this->search.'%';
                $q->where('email', 'like', $term);
            })
            ->when($this->completed === 'yes', fn (Builder $q) => $q->whereNotNull('completed_at'))
            ->when($this->completed === 'no', fn (Builder $q) => $q->whereNull('completed_at'))
            ->latest('id');
    }

    public function render()
    {
        /** @var LengthAwarePaginator $responses */
        $responses = $this->query()->paginate(25);

        return view('livewire.admin.surveys.responses', [
            'responses' => $responses,
        ])->layout('layouts.app');
    }
}
