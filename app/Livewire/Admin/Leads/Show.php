<?php

namespace App\Livewire\Admin\Leads;

use App\Enums\LeadStage;
use App\Models\Lead;
use App\Models\SurveyResponse;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Lead')]
class Show extends Component
{
    public Lead $lead;

    public ?int $viewingResponseId = null;

    public function mount(Lead $lead): void
    {
        $this->lead = $lead->load('tenant');
    }

    public function changeStage(string $stage): void
    {
        $next = LeadStage::tryFrom($stage);
        if ($next === null) {
            return;
        }

        $this->lead->setStage($next);
        $this->lead->refresh();
    }

    public function viewResponse(int $responseId): void
    {
        // Confirm the response actually belongs to this lead before exposing it.
        if (SurveyResponse::query()
            ->where('id', $responseId)
            ->where('lead_id', $this->lead->id)
            ->exists()
        ) {
            $this->viewingResponseId = $responseId;
        }
    }

    public function closeResponse(): void
    {
        $this->viewingResponseId = null;
    }

    #[Computed]
    public function viewingResponse(): ?SurveyResponse
    {
        if ($this->viewingResponseId === null) {
            return null;
        }

        return SurveyResponse::query()
            ->with(['answers.question', 'survey.questions'])
            ->where('id', $this->viewingResponseId)
            ->where('lead_id', $this->lead->id)
            ->first();
    }

    #[Computed]
    public function responses()
    {
        return $this->lead->responses()
            ->with('survey')
            ->latest('completed_at')
            ->get();
    }

    #[Computed]
    public function stageOptions(): array
    {
        return array_map(
            fn (LeadStage $s) => ['value' => $s->value, 'label' => $s->label(), 'color' => $s->color()],
            LeadStage::cases()
        );
    }

    public function render()
    {
        return view('livewire.admin.leads.show')->layout('layouts.app');
    }
}
