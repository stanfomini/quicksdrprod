<?php

use App\Enums\SurveyAudience;
use App\Enums\SurveyQuestionType;
use App\Enums\SurveyStatus;
use App\Filament\Resources\Surveys\Pages\CreateSurvey;
use App\Filament\Resources\Surveys\Pages\SurveyStats;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $user = User::factory()->create([
        'type' => 'internal',
    ]);

    $this->actingAs($user);
});

test('the create page renders for internal users', function () {
    Livewire::test(CreateSurvey::class)->assertSuccessful();
});

test('a survey with no questions can be created', function () {
    Livewire::test(CreateSurvey::class)
        ->set('data', [
            'title' => 'Sales Discovery',
            'slug' => 'sales-discovery',
            'audience' => SurveyAudience::Prospect->value,
            'status' => SurveyStatus::Draft->value,
            'description' => 'Initial qualification survey.',
            'questions' => [],
        ])
        ->call('create')
        ->assertHasNoErrors();

    expect(Survey::count())->toBe(1);
    expect(Survey::first())
        ->title->toBe('Sales Discovery')
        ->slug->toBe('sales-discovery')
        ->audience->toBe(SurveyAudience::Prospect);
});

test('a survey with a text question can be created', function () {
    Livewire::test(CreateSurvey::class)
        ->set('data', [
            'title' => 'Quick QA',
            'slug' => 'quick-qa',
            'audience' => SurveyAudience::Agent->value,
            'status' => SurveyStatus::Draft->value,
            'description' => null,
            'published_at' => null,
            'questions' => [
                'q1' => [
                    'type' => SurveyQuestionType::Text->value,
                    'label' => 'What was the outcome?',
                    'help_text' => null,
                    'required' => true,
                    'options' => null,
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoErrors();

    $survey = Survey::with('questions')->first();
    expect($survey->questions)->toHaveCount(1);
    expect($survey->questions->first())
        ->type->toBe(SurveyQuestionType::Text)
        ->label->toBe('What was the outcome?')
        ->required->toBeTrue();
});

test('a survey with a radio question and choices can be created', function () {
    Livewire::test(CreateSurvey::class)
        ->set('data', [
            'title' => 'Lead survey',
            'slug' => 'lead-survey',
            'audience' => SurveyAudience::Prospect->value,
            'status' => SurveyStatus::Draft->value,
            'description' => null,
            'published_at' => null,
            'questions' => [
                'q1' => [
                    'type' => SurveyQuestionType::Radio->value,
                    'label' => 'Decision maker?',
                    'help_text' => null,
                    'required' => true,
                    'options' => [
                        'choices' => [
                            ['value' => 'yes', 'label' => 'Yes'],
                            ['value' => 'no', 'label' => 'No'],
                        ],
                    ],
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoErrors();

    $survey = Survey::with('questions')->first();
    $question = $survey->questions->first();
    expect($question->type)->toBe(SurveyQuestionType::Radio);
    expect($question->options)->toBe([
        'choices' => [
            ['value' => 'yes', 'label' => 'Yes'],
            ['value' => 'no', 'label' => 'No'],
        ],
    ]);
});

test('the stats page renders for a survey', function () {
    $survey = Survey::factory()->create(['title' => 'Pipeline Discovery']);

    SurveyQuestion::factory()->for($survey)->count(2)->sequence(
        ['order' => 1, 'label' => 'Q1'],
        ['order' => 2, 'label' => 'Q2'],
    )->create();

    SurveyResponse::factory()->for($survey)->completed()->count(3)->create();

    Livewire::test(SurveyStats::class, ['record' => $survey->id])
        ->assertSuccessful()
        ->assertSee('Pipeline Discovery')
        ->assertSee('3'); // total responses
});
