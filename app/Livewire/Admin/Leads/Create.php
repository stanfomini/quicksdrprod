<?php

namespace App\Livewire\Admin\Leads;

use App\Models\Lead;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('New lead')]
class Create extends Component
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $company = '';

    public string $source = 'manual';

    public function save()
    {
        $data = $this->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'company' => ['nullable', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
        ]);

        if (empty($data['email']) && empty($data['phone'])) {
            $this->addError('email', 'Provide at least an email or a phone number.');

            return null;
        }

        $lead = Lead::findOrCreateMatching($data);
        $lead->linkToTenantIfPossible();

        session()->flash('status', 'Lead created.');

        return $this->redirectRoute('admin.leads.show', ['lead' => $lead], navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.leads.create')->layout('layouts.app');
    }
}
