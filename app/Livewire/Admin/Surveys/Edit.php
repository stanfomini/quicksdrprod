<?php

namespace App\Livewire\Admin\Surveys;

use App\Models\Survey;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit survey')]
class Edit extends Component
{
    public Survey $survey;

    public function mount(Survey $survey): void
    {
        $this->survey = $survey;
    }

    public function render()
    {
        return view('livewire.admin.surveys.edit', [
            'embedUrl' => url('/internal/surveys/'.$this->survey->id.'/edit?embed=1'),
        ])->layout('layouts.app');
    }
}
