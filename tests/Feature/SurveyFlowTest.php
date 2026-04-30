<?php

use App\Enums\SurveyAudience;
use App\Enums\SurveyQuestionType;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Livewire\Livewire;

test('public survey route 404s on unknown token', function () {
    $this->get('/r/'.str_repeat('z', 40))->assertNotFound();
});

test('public survey route renders the survey for a valid token', function () {
    $survey = Survey::factory()->audience(SurveyAudience::Prospect)->create([
        'title' => 'Discovery call',
    ]);

    SurveyQuestion::factory()->for($survey)->required()->create([
        'label' => 'What is your timeline?',
        'order' => 1,
    ]);

    $response = SurveyResponse::factory()->for($survey)->create();

    $this->get('/r/'.$response->token)
        ->assertOk()
        ->assertSee('Discovery call')
        ->assertSee('What is your timeline?');
});

test('submitting valid answers persists response and answer rows', function () {
    $survey = Survey::factory()->create();

    $textQ = SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Text,
        'label' => 'Name',
        'required' => true,
        'order' => 1,
    ]);

    $radioQ = SurveyQuestion::factory()
        ->for($survey)
        ->withChoices(['yes', 'no'])
        ->create([
            'type' => SurveyQuestionType::Radio,
            'label' => 'Decision maker?',
            'required' => true,
            'order' => 2,
        ]);

    $response = SurveyResponse::factory()->for($survey)->create();

    Livewire::test('survey-form', ['survey' => $survey, 'response' => $response])
        ->set("answers.{$textQ->id}", 'Acme Corp')
        ->set("answers.{$radioQ->id}", 'yes')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    expect($response->fresh()->isCompleted())->toBeTrue();
    expect(SurveyAnswer::count())->toBe(2);
    expect(SurveyAnswer::where('survey_question_id', $textQ->id)->first()->value)
        ->toBe(['answer' => 'Acme Corp']);
});

test('required question without answer raises a validation error', function () {
    $survey = Survey::factory()->create();
    $question = SurveyQuestion::factory()->for($survey)->required()->create([
        'type' => SurveyQuestionType::Text,
        'label' => 'Email',
        'order' => 1,
    ]);
    $response = SurveyResponse::factory()->for($survey)->create();

    Livewire::test('survey-form', ['survey' => $survey, 'response' => $response])
        ->call('submit')
        ->assertHasErrors(["answers.{$question->id}"]);

    expect($response->fresh()->isCompleted())->toBeFalse();
    expect(SurveyAnswer::count())->toBe(0);
});

test('completed response shows the thank-you state on revisit', function () {
    $survey = Survey::factory()->create();
    SurveyQuestion::factory()->for($survey)->create(['order' => 1]);
    $response = SurveyResponse::factory()->for($survey)->completed()->create();

    $this->get('/r/'.$response->token)
        ->assertOk()
        ->assertSee('Thanks!');
});
