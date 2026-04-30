<?php

namespace App\Livewire\Admin\Leads;

use App\Models\Lead;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Lead')]
class Show extends Component
{
    public Lead $lead;

    public function mount(Lead $lead): void
    {
        $this->lead = $lead->load('tenant', 'responses.survey');
    }

    public function render()
    {
        return view('livewire.admin.leads.show', [
            'lead' => $this->lead,
            'responses' => $this->lead->responses()->with('survey')->latest('completed_at')->get(),
        ])->layout('layouts.app');
    }
}
