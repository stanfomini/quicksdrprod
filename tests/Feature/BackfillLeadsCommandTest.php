<?php

use App\Enums\SurveyQuestionType;
use App\Models\Lead;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\Tenant;

function seedAnswerableResponse(Survey $survey, array $answers): SurveyResponse
{
    $response = SurveyResponse::factory()->for($survey)->completed()->create();
    foreach ($answers as $questionId => $value) {
        SurveyAnswer::create([
            'survey_response_id' => $response->id,
            'survey_question_id' => $questionId,
            'value' => ['answer' => $value],
        ]);
    }

    return $response;
}

test('backfill creates leads from existing completed responses', function () {
    $survey = Survey::factory()->create(['slug' => 'discovery']);
    $emailQ = SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Email,
        'order' => 1,
    ]);

    seedAnswerableResponse($survey, [$emailQ->id => 'a@example.com']);
    seedAnswerableResponse($survey, [$emailQ->id => 'b@example.com']);

    $this->artisan('surveys:backfill-leads')->assertSuccessful();

    expect(Lead::count())->toBe(2);
    expect(SurveyResponse::whereNotNull('lead_id')->count())->toBe(2);
});

test('backfill deduplicates leads sharing an email', function () {
    $survey = Survey::factory()->create();
    $emailQ = SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Email, 'order' => 1]);

    seedAnswerableResponse($survey, [$emailQ->id => 'shared@example.com']);
    seedAnswerableResponse($survey, [$emailQ->id => 'shared@example.com']);

    $this->artisan('surveys:backfill-leads')->assertSuccessful();

    expect(Lead::count())->toBe(1);
    expect(SurveyResponse::whereNotNull('lead_id')->count())->toBe(2);
});

test('backfill links leads to existing matching tenants', function () {
    $tenant = Tenant::factory()->create(['admin_email' => 'tenant-match@example.com']);
    $survey = Survey::factory()->create();
    $emailQ = SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Email, 'order' => 1]);

    seedAnswerableResponse($survey, [$emailQ->id => 'tenant-match@example.com']);

    $this->artisan('surveys:backfill-leads')->assertSuccessful();

    expect(Lead::first()->tenant_id)->toBe($tenant->id);
});

test('dry-run does not write any leads', function () {
    $survey = Survey::factory()->create();
    $emailQ = SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Email, 'order' => 1]);

    seedAnswerableResponse($survey, [$emailQ->id => 'noop@example.com']);

    $this->artisan('surveys:backfill-leads', ['--dry-run' => true])->assertSuccessful();

    expect(Lead::count())->toBe(0);
});

test('backfill skips responses with no email or phone', function () {
    $survey = Survey::factory()->create();
    $textQ = SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Text,
        'order' => 1,
    ]);

    seedAnswerableResponse($survey, [$textQ->id => 'no contact info here']);

    $this->artisan('surveys:backfill-leads')->assertSuccessful();

    expect(Lead::count())->toBe(0);
});
