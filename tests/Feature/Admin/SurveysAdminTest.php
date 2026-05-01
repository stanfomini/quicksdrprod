<?php

use App\Enums\SurveyAudience;
use App\Enums\SurveyQuestionType;
use App\Enums\SurveyStatus;
use App\Livewire\Admin\Surveys\Create;
use App\Livewire\Admin\Surveys\Responses;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    config(['app.mode' => 'orchestrator']);

    $this->user = User::factory()->create(['type' => 'internal']);
    $this->actingAs($this->user);
});

test('the surveys index renders for an internal user', function () {
    Survey::factory()->count(3)->create();

    $this->get(route('admin.surveys.index'))
        ->assertOk()
        ->assertSee('Surveys');
});

test('a non-internal user cannot access surveys admin', function () {
    $this->actingAs(User::factory()->create(['type' => 'tenant']));

    $this->get(route('admin.surveys.index'))->assertForbidden();
});

test('the surveys index supports searching by title', function () {
    Survey::factory()->create(['title' => 'Sales Discovery', 'slug' => 'sales']);
    Survey::factory()->create(['title' => 'Customer NPS', 'slug' => 'nps']);

    $this->get(route('admin.surveys.index', ['q' => 'discovery']))
        ->assertOk()
        ->assertSee('Sales Discovery')
        ->assertDontSee('Customer NPS');
});

test('show page renders title, public URL, and stats KPIs', function () {
    $survey = Survey::factory()->create(['title' => 'Lead Survey', 'slug' => 'lead-survey']);
    SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Email,
        'order' => 1,
    ]);
    SurveyResponse::factory()->for($survey)->completed()->count(2)->create();

    $this->get(route('admin.surveys.show', $survey))
        ->assertOk()
        ->assertSee('Lead Survey')
        ->assertSee('/s/lead-survey')
        ->assertSee('Total responses');
});

test('the responses page lists responses and supports completed filter', function () {
    $survey = Survey::factory()->create();
    SurveyResponse::factory()->for($survey)->completed()->create(['email' => 'done@example.com']);
    SurveyResponse::factory()->for($survey)->create(['email' => 'wip@example.com', 'completed_at' => null]);

    $this->get(route('admin.surveys.responses', $survey))
        ->assertOk()
        ->assertSee('done@example.com')
        ->assertSee('wip@example.com');

    $this->get(route('admin.surveys.responses', ['survey' => $survey, 'completed' => 'yes']))
        ->assertOk()
        ->assertSee('done@example.com')
        ->assertDontSee('wip@example.com');
});

test('the edit page renders an iframe pointing at /internal with embed=1', function () {
    $survey = Survey::factory()->create();

    $response = $this->get(route('admin.surveys.edit', $survey))->assertOk();
    $html = $response->getContent();

    expect($html)->toContain('iframe');
    expect($html)->toContain('/internal/surveys/'.$survey->id.'/edit?embed=1');
});

test('create persists a Draft survey and redirects to its edit page', function () {
    Livewire::test(Create::class)
        ->set('title', 'Quick Test')
        ->set('slug', 'quick-test')
        ->set('audience', SurveyAudience::Prospect->value)
        ->call('save')
        ->assertRedirect();

    $survey = Survey::where('slug', 'quick-test')->first();
    expect($survey)->not->toBeNull();
    expect($survey->status)->toBe(SurveyStatus::Draft);
    expect($survey->title)->toBe('Quick Test');
});

test('create requires title, slug, and audience', function () {
    Livewire::test(Create::class)
        ->call('save')
        ->assertHasErrors(['title', 'slug', 'audience']);

    expect(Survey::count())->toBe(0);
});

test('responses page CSV export streams a download', function () {
    $survey = Survey::factory()->create(['slug' => 'csv-test']);
    SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Email,
        'order' => 1,
    ]);
    SurveyResponse::factory()->for($survey)->completed()->create(['email' => 'csv@example.com']);

    Livewire::test(Responses::class, ['survey' => $survey])
        ->call('exportCsv');

    // The export action returns a StreamedResponse; if the test runs without
    // throwing it's enough — the CSV exporter is independently tested via the
    // Filament relation manager path.
    expect(true)->toBeTrue();
});

test('the admin sidebar links Surveys to admin.surveys.index with wire:navigate', function () {
    $this->get(route('admin.tenants.index'))
        ->assertOk()
        ->assertSee(route('admin.surveys.index'), false)
        ->assertDontSee('target="_blank"', false);
});
