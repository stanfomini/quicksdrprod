<?php

namespace App\Livewire\Admin\Leads;

use App\Models\Lead;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Edit lead')]
class Edit extends Component
{
    public Lead $lead;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $company = '';

    public string $source = '';

    public function mount(Lead $lead): void
    {
        $this->lead = $lead;
        $this->name = (string) $lead->name;
        $this->email = (string) $lead->email;
        $this->phone = (string) $lead->phone;
        $this->company = (string) $lead->company;
        $this->source = (string) $lead->source;
    }

    public function save()
    {
        $data = $this->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email:rfc', 'max:255', Rule::unique('leads', 'email')->ignore($this->lead->id)],
            'phone' => ['nullable', 'string', 'max:32'],
            'company' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
        ]);

        $this->lead->fill([
            'name' => $data['name'] ?: null,
            'email' => Lead::normalizeEmail($data['email']),
            'phone' => Lead::normalizePhone($data['phone']),
            'company' => $data['company'] ?: null,
            'source' => $data['source'] ?: null,
        ])->save();

        $this->lead->linkToTenantIfPossible();

        session()->flash('status', 'Lead saved.');

        return $this->redirectRoute('admin.leads.show', ['lead' => $this->lead], navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.leads.edit')->layout('layouts.app');
    }
}
