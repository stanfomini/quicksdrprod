<?php

use App\Enums\SurveyQuestionType;
use App\Events\SurveyResponseSubmitted;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyResponse;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $surveyId;

    #[Locked]
    public ?int $responseId = null;

    #[Locked]
    public bool $embed = false;

    public bool $started = false;

    public bool $submitted = false;

    public int $currentStep = 0;

    public string $hp_field = '';

    /** @var array<int, mixed> */
    public array $answers = [];

    public function mount(Survey $survey, ?SurveyResponse $response = null, bool $embed = false): void
    {
        $this->surveyId = $survey->id;
        $this->responseId = $response?->id;
        $this->embed = $embed;

        if ($response && $response->isCompleted()) {
            $this->submitted = true;
        }

        if ($response) {
            $this->started = true;
        } elseif (empty($survey->intro_message) && empty($survey->cover_image_path)) {
            $this->started = true;
        }

        if ($response && ! $response->started_at) {
            $response->forceFill(['started_at' => now()])->save();
        }

        foreach ($survey->questions as $question) {
            if (! $question->type->isAnswerable()) {
                continue;
            }
            $this->answers[$question->id] = $question->type->valueIsArray() ? [] : null;
        }
    }

    public function start(): void
    {
        $this->started = true;
    }

    /**
     * Group the survey's questions into steps based on Section dividers.
     * Each step has a `title`, optional `help_text`, and a list of `questions`.
     *
     * @return array<int, array{title: ?string, help_text: ?string, questions: Collection}>
     */
    public function buildSteps(Survey $survey): array
    {
        $steps = [];
        $current = ['title' => null, 'help_text' => null, 'questions' => collect()];

        foreach ($survey->questions as $question) {
            if ($question->type === SurveyQuestionType::Section) {
                if ($current['questions']->isNotEmpty() || $current['title'] !== null) {
                    $steps[] = $current;
                }
                $current = [
                    'title' => $question->label,
                    'help_text' => $question->help_text,
                    'questions' => collect(),
                ];

                continue;
            }

            $current['questions']->push($question);
        }

        if ($current['questions']->isNotEmpty() || $current['title'] !== null) {
            $steps[] = $current;
        }

        if ($steps === []) {
            $steps[] = ['title' => null, 'help_text' => null, 'questions' => collect()];
        }

        return $steps;
    }

    public function nextStep(): void
    {
        $survey = Survey::with('questions')->findOrFail($this->surveyId);
        $steps = $this->buildSteps($survey);

        if (! $survey->multi_step || $this->currentStep >= count($steps) - 1) {
            return;
        }

        $this->validateStep($steps[$this->currentStep]['questions']);
        $this->currentStep++;
    }

    public function prevStep(): void
    {
        if ($this->currentStep > 0) {
            $this->currentStep--;
            // Clear validation state on going back so old errors don't linger.
            $this->resetValidation();
        }
    }

    public function submit()
    {
        if (! empty($this->hp_field)) {
            $this->submitted = true;

            return null;
        }

        $survey = Survey::with('questions')->findOrFail($this->surveyId);

        // In multi-step mode, only the LAST step's "submit" call goes through.
        // For all earlier steps, submit() acts like nextStep().
        if ($survey->multi_step) {
            $steps = $this->buildSteps($survey);
            if ($this->currentStep < count($steps) - 1) {
                $this->validateStep($steps[$this->currentStep]['questions']);
                $this->currentStep++;

                return null;
            }
            // Validate the final step before persisting.
            $this->validateStep($steps[$this->currentStep]['questions']);
        } else {
            $this->validateStep($survey->questions);
        }

        $email = $this->extractEmail($survey);

        if ($survey->require_unique_email && $email !== null) {
            $exists = SurveyResponse::query()
                ->where('survey_id', $survey->id)
                ->where('email', $email)
                ->whereNotNull('completed_at')
                ->exists();

            if ($exists) {
                $this->addError('answers', 'A response from this email has already been recorded.');

                return null;
            }
        }

        $response = $this->responseId
            ? SurveyResponse::findOrFail($this->responseId)
            : new SurveyResponse(['survey_id' => $survey->id]);

        $response->survey_id = $survey->id;
        $response->email = $email;
        $response->started_at ??= now();
        $response->completed_at = now();
        $response->ip_address ??= request()->ip();
        $response->user_agent ??= substr((string) request()->userAgent(), 0, 255);
        $response->save();

        foreach ($survey->questions as $question) {
            if (! $question->type->isAnswerable()) {
                continue;
            }
            $raw = $this->answers[$question->id] ?? null;

            if ($question->type === SurveyQuestionType::FileUpload && $raw && method_exists($raw, 'store')) {
                $raw = $raw->store('survey-uploads', 'public');
            }

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

        if (class_exists(SurveyResponseSubmitted::class)) {
            event(new SurveyResponseSubmitted($response));
        }

        if (! empty($survey->success_redirect_url)) {
            return $this->redirect($survey->success_redirect_url);
        }

        return null;
    }

    /**
     * Build a rules+messages set for the given questions and run validate().
     *
     * @param  Collection<int, \App\Models\SurveyQuestion>|iterable  $questions
     */
    protected function validateStep(iterable $questions): void
    {
        $rules = [];
        $messages = [];

        foreach ($questions as $question) {
            if (! $question->type->isAnswerable()) {
                continue;
            }
            if (! $question->isVisible($this->answers)) {
                continue;
            }
            $key = "answers.{$question->id}";
            $rules[$key] = $question->validationRules();
            $messages["{$key}.required"] = "“{$question->label}” is required.";
            $messages["{$key}.email"] = "“{$question->label}” must be a valid email.";
            $messages["{$key}.url"] = "“{$question->label}” must be a valid URL.";
            $messages["{$key}.numeric"] = "“{$question->label}” must be a number.";
        }

        if ($rules === []) {
            return;
        }

        $this->validate($rules, $messages);
    }

    protected function extractEmail(Survey $survey): ?string
    {
        foreach ($survey->questions as $question) {
            if ($question->type === SurveyQuestionType::Email) {
                $value = $this->answers[$question->id] ?? null;
                if (is_string($value) && $value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    public function with(): array
    {
        $survey = Survey::with('questions')->findOrFail($this->surveyId);
        $steps = $this->buildSteps($survey);
        $isMultiStep = $survey->multi_step && count($steps) > 1;

        // Clamp currentStep to valid range
        if ($this->currentStep >= count($steps)) {
            $this->currentStep = count($steps) - 1;
        }
        if ($this->currentStep < 0) {
            $this->currentStep = 0;
        }

        if ($isMultiStep) {
            $currentQuestions = $steps[$this->currentStep]['questions'];
            $visibleQuestions = $currentQuestions->filter(fn ($q) => $q->isVisible($this->answers));
        } else {
            $visibleQuestions = $survey->questions->filter(fn ($q) => $q->isVisible($this->answers));
        }

        return [
            'survey' => $survey,
            'questionType' => SurveyQuestionType::class,
            'visibleQuestions' => $visibleQuestions,
            'steps' => $steps,
            'isMultiStep' => $isMultiStep,
            'isLastStep' => $isMultiStep ? $this->currentStep === count($steps) - 1 : true,
            'isFirstStep' => $this->currentStep === 0,
            'totalSteps' => count($steps),
            'currentStepData' => $isMultiStep ? $steps[$this->currentStep] : null,
            'answerableCount' => $survey->questions->filter(fn ($q) => $q->type->isAnswerable() && $q->isVisible($this->answers))->count(),
            'answeredCount' => collect($this->answers)
                ->filter(fn ($v) => $v !== null && $v !== '' && $v !== [])
                ->count(),
        ];
    }
};
?>

<div
    @if ($survey->brand_color)
        style="--survey-brand: {{ $survey->brand_color }};"
    @endif
    class="survey-root {{ $embed ? 'p-4' : 'py-10' }}"
>
    <style>
        .survey-root .btn-primary { background-color: var(--survey-brand, #10b981); }
        .survey-root .btn-primary:hover { filter: brightness(0.95); }
        .survey-root .progress-fill { background-color: var(--survey-brand, #10b981); }
        .survey-root .step-active { background-color: var(--survey-brand, #10b981); color: white; }
    </style>

    <div class="max-w-2xl mx-auto px-4">

        @if ($survey->cover_image_path && ! $submitted)
            <img
                src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($survey->cover_image_path) }}"
                alt=""
                class="w-full h-48 object-cover rounded-lg mb-6"
            >
        @endif

        @if ($submitted)
            <div class="rounded-lg border p-8 text-center bg-white dark:bg-zinc-900 dark:border-zinc-800">
                <div class="text-4xl mb-4">✓</div>
                <flux:heading size="lg">Thanks!</flux:heading>
                <div class="mt-3 text-zinc-600 dark:text-zinc-400">
                    @if (! empty($survey->thank_you_message))
                        {!! nl2br(e($survey->thank_you_message)) !!}
                    @else
                        Your response has been recorded.
                    @endif
                </div>
            </div>

        @elseif (! $started)
            <div class="rounded-lg border p-8 bg-white dark:bg-zinc-900 dark:border-zinc-800">
                <flux:heading size="xl">{{ $survey->title }}</flux:heading>

                @if (! empty($survey->intro_message))
                    <div class="mt-4 text-zinc-700 dark:text-zinc-300 prose">
                        {!! nl2br(e($survey->intro_message)) !!}
                    </div>
                @endif

                <div class="mt-6">
                    <button
                        type="button"
                        wire:click="start"
                        class="btn-primary text-white px-5 py-2.5 rounded-md font-medium"
                    >
                        Start
                    </button>
                </div>
            </div>

        @else
            <form wire:submit="submit" class="space-y-6">
                <div>
                    <flux:heading size="xl">{{ $survey->title }}</flux:heading>
                    @if ($survey->description && empty($survey->intro_message) && ! $isMultiStep)
                        <flux:text class="mt-2">{{ $survey->description }}</flux:text>
                    @endif
                </div>

                {{-- Step indicator --}}
                @if ($isMultiStep)
                    <div class="flex items-center gap-2">
                        @for ($i = 0; $i < $totalSteps; $i++)
                            <div class="flex-1 flex items-center gap-2">
                                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-semibold border
                                    {{ $i === $currentStep
                                        ? 'step-active border-transparent'
                                        : ($i < $currentStep
                                            ? 'bg-emerald-500 text-white border-transparent'
                                            : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500 border-zinc-200 dark:border-zinc-700') }}">
                                    @if ($i < $currentStep)
                                        ✓
                                    @else
                                        {{ $i + 1 }}
                                    @endif
                                </div>
                                @if ($i < $totalSteps - 1)
                                    <div class="flex-1 h-0.5 {{ $i < $currentStep ? 'bg-emerald-500' : 'bg-zinc-200 dark:bg-zinc-800' }}"></div>
                                @endif
                            </div>
                        @endfor
                    </div>
                    <div class="text-sm text-zinc-500 -mt-2">Step {{ $currentStep + 1 }} of {{ $totalSteps }}</div>
                @endif

                {{-- Progress bar (only show for single-page or if explicitly enabled) --}}
                @if ($survey->show_progress_bar && ! $isMultiStep && $answerableCount > 0)
                    @php($pct = $answerableCount > 0 ? round(($answeredCount / $answerableCount) * 100) : 0)
                    <div class="h-1.5 w-full bg-zinc-200 dark:bg-zinc-800 rounded-full overflow-hidden">
                        <div class="progress-fill h-full transition-all" style="width: {{ $pct }}%"></div>
                    </div>
                @endif

                {{-- Step heading (multi-step only) --}}
                @if ($isMultiStep && $currentStepData && $currentStepData['title'])
                    <div class="border-l-4 pl-4 py-1" style="border-color: var(--survey-brand, #10b981);">
                        <flux:heading size="lg">{{ $currentStepData['title'] }}</flux:heading>
                        @if ($currentStepData['help_text'])
                            <flux:text class="mt-1">{{ $currentStepData['help_text'] }}</flux:text>
                        @endif
                    </div>
                @endif

                {{-- Honeypot --}}
                <div aria-hidden="true" style="position:absolute; left:-9999px; top:-9999px;">
                    <label>Leave this empty <input type="text" wire:model="hp_field" tabindex="-1" autocomplete="off"></label>
                </div>

                @foreach ($visibleQuestions as $question)
                    @php($key = 'answers.'.$question->id)

                    @switch($question->type)
                        @case($questionType::Section)
                            {{-- In single-page mode, sections render as inline dividers. In multi-step they're already pulled out as step titles. --}}
                            @if (! $isMultiStep)
                                <div class="border-t border-zinc-300 dark:border-zinc-700 pt-4 mt-4">
                                    <flux:heading size="lg">{{ $question->label }}</flux:heading>
                                    @if ($question->help_text)
                                        <flux:text class="mt-1">{{ $question->help_text }}</flux:text>
                                    @endif
                                </div>
                            @endif
                            @break

                        @case($questionType::Description)
                            <div class="text-zinc-700 dark:text-zinc-300">
                                {!! nl2br(e($question->label)) !!}
                                @if ($question->help_text)
                                    <div class="mt-1 text-sm text-zinc-500">{!! nl2br(e($question->help_text)) !!}</div>
                                @endif
                            </div>
                            @break

                        @default
                            <div class="space-y-2">
                                @switch($question->type)
                                    @case($questionType::Text)
                                    @case($questionType::Name)
                                        <flux:input
                                            :label="$question->label"
                                            :description="$question->help_text"
                                            :placeholder="$question->placeholder ?: $question->type->defaultPlaceholder()"
                                            :required="$question->required"
                                            wire:model.live.debounce.400ms="{{ $key }}"
                                        />
                                        @break

                                    @case($questionType::Email)
                                        <flux:input
                                            type="email"
                                            :label="$question->label"
                                            :description="$question->help_text"
                                            :placeholder="$question->placeholder ?: $question->type->defaultPlaceholder()"
                                            :required="$question->required"
                                            wire:model.live.debounce.400ms="{{ $key }}"
                                        />
                                        @break

                                    @case($questionType::Phone)
                                        <flux:input
                                            type="tel"
                                            :label="$question->label"
                                            :description="$question->help_text"
                                            :placeholder="$question->placeholder ?: $question->type->defaultPlaceholder()"
                                            :required="$question->required"
                                            wire:model.live.debounce.400ms="{{ $key }}"
                                        />
                                        @break

                                    @case($questionType::Url)
                                        <flux:input
                                            type="url"
                                            :label="$question->label"
                                            :description="$question->help_text"
                                            :placeholder="$question->placeholder ?: $question->type->defaultPlaceholder()"
                                            :required="$question->required"
                                            wire:model.live.debounce.400ms="{{ $key }}"
                                        />
                                        @break

                                    @case($questionType::Number)
                                        <flux:input
                                            type="number"
                                            :label="$question->label"
                                            :description="$question->help_text"
                                            :placeholder="$question->placeholder"
                                            :required="$question->required"
                                            wire:model.live.debounce.400ms="{{ $key }}"
                                        />
                                        @break

                                    @case($questionType::Textarea)
                                        <flux:textarea
                                            :label="$question->label"
                                            :description="$question->help_text"
                                            :placeholder="$question->placeholder"
                                            :required="$question->required"
                                            wire:model.live.debounce.400ms="{{ $key }}"
                                            rows="4"
                                        />
                                        @break

                                    @case($questionType::Date)
                                        <flux:input
                                            type="date"
                                            :label="$question->label"
                                            :description="$question->help_text"
                                            :required="$question->required"
                                            wire:model.live.debounce.400ms="{{ $key }}"
                                        />
                                        @break

                                    @case($questionType::DateTime)
                                        <flux:input
                                            type="datetime-local"
                                            :label="$question->label"
                                            :description="$question->help_text"
                                            :required="$question->required"
                                            wire:model.live.debounce.400ms="{{ $key }}"
                                        />
                                        @break

                                    @case($questionType::Time)
                                        <flux:input
                                            type="time"
                                            :label="$question->label"
                                            :description="$question->help_text"
                                            :required="$question->required"
                                            wire:model.live.debounce.400ms="{{ $key }}"
                                        />
                                        @break

                                    @case($questionType::Radio)
                                        <flux:radio.group
                                            :label="$question->label"
                                            :description="$question->help_text"
                                            wire:model.live.debounce.400ms="{{ $key }}"
                                        >
                                            @foreach ($question->options['choices'] ?? [] as $choice)
                                                <flux:radio :value="$choice['value']" :label="$choice['label']" />
                                            @endforeach
                                        </flux:radio.group>
                                        @break

                                    @case($questionType::YesNo)
                                        <flux:radio.group
                                            :label="$question->label"
                                            :description="$question->help_text"
                                            wire:model.live.debounce.400ms="{{ $key }}"
                                        >
                                            <flux:radio value="yes" label="Yes" />
                                            <flux:radio value="no" label="No" />
                                        </flux:radio.group>
                                        @break

                                    @case($questionType::Checkbox)
                                        <flux:checkbox.group
                                            :label="$question->label"
                                            :description="$question->help_text"
                                            wire:model.live.debounce.400ms="{{ $key }}"
                                        >
                                            @foreach ($question->options['choices'] ?? [] as $choice)
                                                <flux:checkbox :value="$choice['value']" :label="$choice['label']" />
                                            @endforeach
                                        </flux:checkbox.group>
                                        @break

                                    @case($questionType::Select)
                                        <flux:select
                                            :label="$question->label"
                                            :description="$question->help_text"
                                            :required="$question->required"
                                            wire:model.live.debounce.400ms="{{ $key }}"
                                            placeholder="Choose..."
                                        >
                                            @foreach ($question->options['choices'] ?? [] as $choice)
                                                <flux:select.option :value="$choice['value']">{{ $choice['label'] }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        @break

                                    @case($questionType::Scale)
                                        @php($max = (int) ($question->options['max'] ?? 5))
                                        <div>
                                            <flux:label>{{ $question->label }}</flux:label>
                                            @if ($question->help_text)
                                                <flux:description>{{ $question->help_text }}</flux:description>
                                            @endif
                                            <div class="flex gap-2 mt-2 flex-wrap">
                                                @for ($i = 1; $i <= $max; $i++)
                                                    <button
                                                        type="button"
                                                        wire:click="$set('{{ $key }}', {{ $i }})"
                                                        class="w-10 h-10 rounded-md border font-medium transition
                                                            {{ (int)($answers[$question->id] ?? 0) === $i
                                                                ? 'btn-primary text-white border-transparent'
                                                                : 'bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 border-zinc-300 dark:border-zinc-600' }}"
                                                    >{{ $i }}</button>
                                                @endfor
                                            </div>
                                        </div>
                                        @break

                                    @case($questionType::Rating)
                                        @php($max = (int) ($question->options['max'] ?? 5))
                                        <div>
                                            <flux:label>{{ $question->label }}</flux:label>
                                            @if ($question->help_text)
                                                <flux:description>{{ $question->help_text }}</flux:description>
                                            @endif
                                            <div class="flex gap-1 mt-2">
                                                @for ($i = 1; $i <= $max; $i++)
                                                    <button
                                                        type="button"
                                                        wire:click="$set('{{ $key }}', {{ $i }})"
                                                        class="text-3xl transition {{ (int)($answers[$question->id] ?? 0) >= $i ? 'text-amber-400' : 'text-zinc-300 dark:text-zinc-600 hover:text-zinc-400' }}"
                                                        aria-label="{{ $i }} stars"
                                                    >★</button>
                                                @endfor
                                            </div>
                                        </div>
                                        @break

                                    @case($questionType::FileUpload)
                                        <div>
                                            <flux:label>{{ $question->label }}</flux:label>
                                            @if ($question->help_text)
                                                <flux:description>{{ $question->help_text }}</flux:description>
                                            @endif
                                            <input
                                                type="file"
                                                wire:model="{{ $key }}"
                                                class="mt-2 block w-full text-sm text-zinc-700 dark:text-zinc-300 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-zinc-100 dark:file:bg-zinc-800 file:text-zinc-700 dark:file:text-zinc-300"
                                            >
                                            <div wire:loading wire:target="{{ $key }}" class="text-xs text-zinc-500 mt-1">Uploading...</div>
                                        </div>
                                        @break
                                @endswitch

                                @error($key)
                                    <flux:text class="text-red-600 text-sm">{{ $message }}</flux:text>
                                @enderror
                            </div>
                    @endswitch
                @endforeach

                @error('answers')
                    <flux:text class="text-red-600 text-sm">{{ $message }}</flux:text>
                @enderror

                {{-- Navigation buttons --}}
                <div class="pt-4 flex items-center gap-3">
                    @if ($isMultiStep && ! $isFirstStep)
                        <button
                            type="button"
                            wire:click="prevStep"
                            class="px-5 py-2.5 rounded-md font-medium border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-900 hover:bg-zinc-50 dark:hover:bg-zinc-800"
                        >← Back</button>
                    @endif

                    @if ($isMultiStep && ! $isLastStep)
                        <button
                            type="button"
                            wire:click="nextStep"
                            class="btn-primary text-white px-5 py-2.5 rounded-md font-medium ml-auto"
                        >Next →</button>
                    @else
                        <button
                            type="submit"
                            class="btn-primary text-white px-5 py-2.5 rounded-md font-medium {{ $isMultiStep ? 'ml-auto' : '' }}"
                        >Submit</button>
                    @endif
                </div>
            </form>
        @endif
    </div>
</div>
