<?php

namespace App\Livewire\Admin\Surveys;

use App\Models\Lead;
use App\Models\Survey;
use App\Services\SurveyStatistics;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Survey')]
class Show extends Component
{
    public Survey $survey;

    public function mount(Survey $survey): void
    {
        $this->survey = $survey->load('questions');
    }

    #[Computed]
    public function statistics(): SurveyStatistics
    {
        return new SurveyStatistics($this->survey);
    }

    #[Computed]
    public function recentResponses()
    {
        return $this->survey->responses()
            ->with(['lead'])
            ->latest('completed_at')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function linkedLeads()
    {
        return Lead::query()
            ->where('source', 'survey:'.$this->survey->slug)
            ->withCount('responses')
            ->orderByDesc('last_seen_at')
            ->limit(10)
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.surveys.show')->layout('layouts.app');
    }
}
