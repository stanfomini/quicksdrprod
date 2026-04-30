<?php

use App\Enums\SurveyQuestionType;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Services\SurveyStatistics;

function seedAnswers(SurveyQuestion $question, array $values): void
{
    $survey = $question->survey;

    foreach ($values as $value) {
        $response = SurveyResponse::factory()->for($survey)->completed()->create();
        SurveyAnswer::create([
            'survey_response_id' => $response->id,
            'survey_question_id' => $question->id,
            'value' => ['answer' => $value],
        ]);
    }
}

test('summary returns total / completed / completion rate', function () {
    $survey = Survey::factory()->create();

    SurveyResponse::factory()->for($survey)->completed()->count(7)->create();
    SurveyResponse::factory()->for($survey)->count(3)->create(['completed_at' => null]);

    $stats = (new SurveyStatistics($survey))->summary();

    expect($stats['total_responses'])->toBe(10);
    expect($stats['completed'])->toBe(7);
    expect($stats['in_progress'])->toBe(3);
    expect($stats['completion_rate'])->toBe(70.0);
});

test('time series buckets responses into 30 daily slots', function () {
    $survey = Survey::factory()->create();

    SurveyResponse::factory()->for($survey)->create([
        'started_at' => now()->subMinute(),
        'completed_at' => now(),
    ]);
    SurveyResponse::factory()->for($survey)->create([
        'started_at' => now()->subDays(2)->subMinute(),
        'completed_at' => now()->subDays(2),
    ]);

    $series = (new SurveyStatistics($survey))->timeSeries(30);

    expect($series)->toHaveCount(30);
    expect($series[now()->format('Y-m-d')])->toBe(1);
    expect($series[now()->subDays(2)->format('Y-m-d')])->toBe(1);
});

test('numeric analysis computes mean median mode std dev', function () {
    $survey = Survey::factory()->create();
    $q = SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Number,
        'label' => 'How many?',
        'order' => 1,
    ]);

    seedAnswers($q, [2, 4, 4, 4, 5, 5, 7, 9]);

    $analyses = (new SurveyStatistics($survey))->questionAnalyses();
    $a = $analyses[0];

    expect($a['kind'])->toBe('numeric');
    expect($a['numbers'])->toBe(8);
    expect($a['mean'])->toBe(5.0);
    expect($a['median'])->toEqualWithDelta(4.5, 0.001);
    expect($a['mode'])->toBe('4');
    expect($a['min'])->toBe(2.0);
    expect($a['max'])->toBe(9.0);
    expect((float) $a['std_dev'])->toEqualWithDelta(2.138, 0.05);
});

test('choice analysis counts and percentages', function () {
    $survey = Survey::factory()->create();
    $q = SurveyQuestion::factory()->for($survey)->withChoices(['a', 'b', 'c'])->create([
        'type' => SurveyQuestionType::Radio,
        'label' => 'Pick one',
        'order' => 1,
    ]);

    seedAnswers($q, ['a', 'a', 'a', 'b', 'b', 'c']);

    $a = (new SurveyStatistics($survey))->questionAnalyses()[0];

    expect($a['kind'])->toBe('choice');
    expect($a['mode'])->toBe('a');
    $rows = collect($a['rows'])->keyBy('value');
    expect($rows['a']['count'])->toBe(3);
    expect($rows['a']['percent'])->toBe(50.0);
    expect($rows['b']['count'])->toBe(2);
    expect($rows['c']['count'])->toBe(1);
});

test('multi-choice analysis tracks avg selections per respondent', function () {
    $survey = Survey::factory()->create();
    $q = SurveyQuestion::factory()->for($survey)->withChoices(['x', 'y', 'z'])->create([
        'type' => SurveyQuestionType::Checkbox,
        'label' => 'Pick any',
        'order' => 1,
    ]);

    seedAnswers($q, [
        ['x', 'y'],
        ['x'],
        ['x', 'y', 'z'],
    ]);

    $a = (new SurveyStatistics($survey))->questionAnalyses()[0];

    expect($a['kind'])->toBe('multi_choice');
    expect($a['avg_selections'])->toBe(2.0);
    expect($a['total_respondents'])->toBe(3);

    $rows = collect($a['rows'])->keyBy('value');
    expect($rows['x']['count'])->toBe(3);
    expect($rows['y']['count'])->toBe(2);
    expect($rows['z']['count'])->toBe(1);
});

test('email analysis groups by domain', function () {
    $survey = Survey::factory()->create();
    $q = SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Email,
        'label' => 'Email',
        'order' => 1,
    ]);

    seedAnswers($q, [
        'a@gmail.com',
        'b@gmail.com',
        'c@gmail.com',
        'd@yahoo.com',
        'e@example.com',
    ]);

    $a = (new SurveyStatistics($survey))->questionAnalyses()[0];

    expect($a['kind'])->toBe('email');
    $domains = collect($a['domains'])->keyBy('value');
    expect($domains['gmail.com']['count'])->toBe(3);
    expect($domains['gmail.com']['percent'])->toBe(60.0);
    expect($domains['yahoo.com']['count'])->toBe(1);
});

test('text analysis surfaces top words and sample', function () {
    $survey = Survey::factory()->create();
    $q = SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Textarea,
        'label' => 'Comments',
        'order' => 1,
    ]);

    seedAnswers($q, [
        'pricing was the main blocker for us',
        'pricing seemed expensive but pipeline grew',
        'integration with salesforce is critical',
    ]);

    $a = (new SurveyStatistics($survey))->questionAnalyses()[0];

    expect($a['kind'])->toBe('text');
    expect(array_keys($a['top_words']))->toContain('pricing');
    expect($a['top_words']['pricing'])->toBe(2);
    expect($a['sample'])->toHaveCount(3);
});

test('drop off counts skipped questions per completed response', function () {
    $survey = Survey::factory()->create();
    $q1 = SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Text, 'label' => 'Required', 'order' => 1]);
    $q2 = SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Text, 'label' => 'Optional', 'order' => 2]);

    // 3 completed responses, only the first answers q2
    foreach (range(1, 3) as $i) {
        $r = SurveyResponse::factory()->for($survey)->completed()->create();
        SurveyAnswer::create([
            'survey_response_id' => $r->id,
            'survey_question_id' => $q1->id,
            'value' => ['answer' => 'value'.$i],
        ]);
    }

    $rOnly = SurveyResponse::query()->where('survey_id', $survey->id)->first();
    SurveyAnswer::create([
        'survey_response_id' => $rOnly->id,
        'survey_question_id' => $q2->id,
        'value' => ['answer' => 'extra'],
    ]);

    $drop = (new SurveyStatistics($survey))->dropOff();

    expect($drop)->toHaveCount(2);
    expect($drop[0]['answered'])->toBe(3);
    expect($drop[0]['skipped'])->toBe(0);
    expect($drop[1]['answered'])->toBe(1);
    expect($drop[1]['skipped'])->toBe(2);
    expect($drop[1]['skip_rate'])->toEqualWithDelta(66.7, 0.1);
});

test('numeric histogram bins scale 1-5 correctly', function () {
    $survey = Survey::factory()->create();
    $q = SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Scale,
        'label' => 'How satisfied',
        'options' => ['max' => 5],
        'order' => 1,
    ]);

    seedAnswers($q, [1, 2, 3, 3, 3, 4, 4, 5]);

    $a = (new SurveyStatistics($survey))->questionAnalyses()[0];
    $bins = collect($a['distribution'])->keyBy('label');

    expect($bins['1']['count'])->toBe(1);
    expect($bins['2']['count'])->toBe(1);
    expect($bins['3']['count'])->toBe(3);
    expect($bins['4']['count'])->toBe(2);
    expect($bins['5']['count'])->toBe(1);
});
