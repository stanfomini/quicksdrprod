<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Contact Sales — QuickSDR')]
class ContactSales extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    #[Validate('required|string|max:50')]
    public string $phone = '';

    #[Validate('required|string|max:255')]
    public string $company = '';

    #[Validate('required|string|max:2000')]
    public string $scope = '';

    public bool $submitted = false;

    public function submit(): void
    {
        $this->validate();

        Mail::raw(
            "New Tier 4 (Custom Build) inquiry:\n\n"
            ."Name: {$this->name}\n"
            ."Email: {$this->email}\n"
            ."Phone: {$this->phone}\n"
            ."Company: {$this->company}\n\n"
            ."Scope:\n{$this->scope}\n",
            function ($message) {
                $message->to(config('mail.sales_address', 'matthew@scriptandautomations.com'))
                    ->subject('QuickSDR — Tier 4 Contact Sales inquiry from '.$this->name)
                    ->replyTo($this->email, $this->name);
            }
        );

        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.contact-sales');
    }
}
