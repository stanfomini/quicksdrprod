<?php

use App\Enums\SurveyQuestionType;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyResponse;
use Livewire\Attributes\Locked;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public int $surveyId;

    #[Locked]
    public ?int $responseId = null;

    /** @var array<int, mixed> */
    public array $answers = [];

    public bool $submitted = false;

    public function mount(Survey $survey, ?SurveyResponse $response = null): void
    {
        $this->surveyId = $survey->id;
        $this->responseId = $response?->id;

        if ($response && $response->isCompleted()) {
            $this->submitted = true;
        }

        if ($response && ! $response->started_at) {
            $response->forceFill(['started_at' => now()])->save();
        }

        foreach ($survey->questions as $question) {
            $this->answers[$question->id] = $question->type->valueIsArray() ? [] : null;
        }
    }

    public function submit(): void
    {
        $survey = Survey::with('questions')->findOrFail($this->surveyId);

        $rules = [];
        $messages = [];

        foreach ($survey->questions as $question) {
            $key = "answers.{$question->id}";
            $rules[$key] = $question->validationRules();
            $messages["{$key}.required"] = "“{$question->label}” is required.";
        }

        $this->validate($rules, $messages);

        $response = $this->responseId
            ? SurveyResponse::findOrFail($this->responseId)
            : new SurveyResponse(['survey_id' => $survey->id]);

        $response->survey_id = $survey->id;
        $response->started_at ??= now();
        $response->completed_at = now();
        $response->save();

        foreach ($survey->questions as $question) {
            $raw = $this->answers[$question->id] ?? null;

            SurveyAnswer::updateOrCreate(
                [
                    'survey_response_id' => $response->id,
                    'survey_question_id' => $question->id,
                ],
                ['value' => ['answer' => $raw]],
            );
        }

        $this->responseId = $response->id;
        $this->submitted = true;
    }

    public function with(): array
    {
        return [
            'survey' => Survey::with('questions')->findOrFail($this->surveyId),
            'questionType' => SurveyQuestionType::class,
        ];
    }
};
?>

<div class="max-w-2xl mx-auto p-6">
    @if ($submitted)
        <div class="rounded-lg border border-green-200 bg-green-50 p-6 text-center dark:border-green-800 dark:bg-green-950">
            <flux:heading size="lg">Thanks!</flux:heading>
            <flux:text class="mt-2">Your response has been recorded.</flux:text>
        </div>
    @else
        <form wire:submit="submit" class="space-y-6">
            <div>
                <flux:heading size="xl">{{ $survey->title }}</flux:heading>
                @if ($survey->description)
                    <flux:text class="mt-2">{{ $survey->description }}</flux:text>
                @endif
            </div>

            @foreach ($survey->questions as $question)
                @php($key = 'answers.'.$question->id)

                <div class="space-y-2">
                    @switch($question->type)
                        @case($questionType::Text)
                            <flux:input
                                :label="$question->label"
                                :description="$question->help_text"
                                :required="$question->required"
                                wire:model="{{ $key }}"
                            />
                            @break

                        @case($questionType::Textarea)
                            <flux:textarea
                                :label="$question->label"
                                :description="$question->help_text"
                                :required="$question->required"
                                wire:model="{{ $key }}"
                                rows="4"
                            />
                            @break

                        @case($questionType::Radio)
                            <flux:radio.group
                                :label="$question->label"
                                :description="$question->help_text"
                                wire:model="{{ $key }}"
                            >
                                @foreach ($question->options['choices'] ?? [] as $choice)
                                    <flux:radio
                                        :value="$choice['value']"
                                        :label="$choice['label']"
                                    />
                                @endforeach
                            </flux:radio.group>
                            @break

                        @case($questionType::Checkbox)
                            <flux:checkbox.group
                                :label="$question->label"
                                :description="$question->help_text"
                                wire:model="{{ $key }}"
                            >
                                @foreach ($question->options['choices'] ?? [] as $choice)
                                    <flux:checkbox
                                        :value="$choice['value']"
                                        :label="$choice['label']"
                                    />
                                @endforeach
                            </flux:checkbox.group>
                            @break

                        @case($questionType::Select)
                            <flux:select
                                :label="$question->label"
                                :description="$question->help_text"
                                :required="$question->required"
                                wire:model="{{ $key }}"
                                placeholder="Choose..."
                            >
                                @foreach ($question->options['choices'] ?? [] as $choice)
                                    <flux:select.option :value="$choice['value']">{{ $choice['label'] }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            @break

                        @case($questionType::Scale)
                        @case($questionType::Rating)
                            <flux:input
                                type="number"
                                :label="$question->label"
                                :description="$question->help_text"
                                :required="$question->required"
                                wire:model="{{ $key }}"
                                min="1"
                                :max="$question->options['max'] ?? 5"
                            />
                            @break

                        @case($questionType::Date)
                            <flux:input
                                type="date"
                                :label="$question->label"
                                :description="$question->help_text"
                                :required="$question->required"
                                wire:model="{{ $key }}"
                            />
                            @break
                    @endswitch

                    @error($key)
                        <flux:text class="text-red-600">{{ $message }}</flux:text>
                    @enderror
                </div>
            @endforeach

            <div class="pt-4">
                <flux:button type="submit" variant="primary">Submit</flux:button>
            </div>
        </form>
    @endif
</div>
