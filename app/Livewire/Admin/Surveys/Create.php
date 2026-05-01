<?php

namespace App\Livewire\Admin\Surveys;

use App\Enums\SurveyAudience;
use App\Enums\SurveyStatus;
use App\Models\Survey;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('New survey')]
class Create extends Component
{
    public string $title = '';

    public string $slug = '';

    public string $audience = '';

    public function updatedTitle(string $value): void
    {
        if (empty($this->slug)) {
            $this->slug = Str::slug($value);
        }
    }

    #[Computed]
    public function audienceOptions(): array
    {
        return array_map(
            fn (SurveyAudience $a) => ['value' => $a->value, 'label' => $a->label()],
            SurveyAudience::cases()
        );
    }

    public function save()
    {
        $data = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('surveys', 'slug')],
            'audience' => ['required', 'string', Rule::in(array_map(fn (SurveyAudience $a) => $a->value, SurveyAudience::cases()))],
        ]);

        $survey = Survey::create([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'audience' => $data['audience'],
            'status' => SurveyStatus::Draft,
        ]);

        return $this->redirectRoute('admin.surveys.edit', ['survey' => $survey], navigate: true);
    }

    public function render()
    {
        return view('livewire.admin.surveys.create')->layout('layouts.app');
    }
}
