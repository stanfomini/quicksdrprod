<?php

use App\Enums\SurveyAudience;
use App\Enums\SurveyQuestionType;
use App\Enums\SurveyStatus;
use App\Events\SurveyResponseSubmitted;
use App\Listeners\NotifyOnSurveyResponse;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('slug url 404s for unpublished surveys', function () {
    $survey = Survey::factory()->draft()->create(['slug' => 'private-survey']);

    $this->get('/s/'.$survey->slug)->assertNotFound();
});

test('slug url renders for published surveys', function () {
    $survey = Survey::factory()->create(['slug' => 'open-survey', 'title' => 'Public Survey']);
    SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Text,
        'label' => 'Hello?',
        'order' => 1,
    ]);

    $this->get('/s/'.$survey->slug)
        ->assertOk()
        ->assertSee('Public Survey');
});

test('embed url renders without nav chrome', function () {
    $survey = Survey::factory()->create(['slug' => 'embedded']);

    $response = $this->get('/embed/'.$survey->slug)->assertOk();

    expect($response->getContent())->toContain('background: transparent');
});

test('all answerable question types validate appropriately', function () {
    $survey = Survey::factory()->create();

    $email = SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Email,
        'label' => 'Email',
        'required' => true,
        'order' => 1,
    ]);
    $phone = SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Phone,
        'label' => 'Phone',
        'order' => 2,
    ]);
    $url = SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Url,
        'label' => 'Site',
        'order' => 3,
    ]);
    $number = SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Number,
        'label' => 'How many',
        'order' => 4,
    ]);
    $yesno = SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::YesNo,
        'label' => 'Decision maker?',
        'order' => 5,
    ]);

    Livewire::test('survey-form', ['survey' => $survey])
        ->set('answers.'.$email->id, 'not-an-email')
        ->set('answers.'.$url->id, 'not-a-url')
        ->set('answers.'.$number->id, 'abc')
        ->set('answers.'.$yesno->id, 'maybe')
        ->call('submit')
        ->assertHasErrors([
            'answers.'.$email->id,
            'answers.'.$url->id,
            'answers.'.$number->id,
            'answers.'.$yesno->id,
        ]);
});

test('email is captured on the response from the email question', function () {
    $survey = Survey::factory()->create();
    $emailQ = SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Email,
        'label' => 'Your email',
        'required' => true,
        'order' => 1,
    ]);

    Livewire::test('survey-form', ['survey' => $survey])
        ->set('answers.'.$emailQ->id, 'jane@example.com')
        ->call('submit')
        ->assertSet('submitted', true);

    expect(SurveyResponse::first()->email)->toBe('jane@example.com');
});

test('section and description blocks are not validated', function () {
    $survey = Survey::factory()->create();
    SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Section,
        'label' => 'Personal info',
        'order' => 1,
    ]);
    SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Description,
        'label' => 'Please answer truthfully.',
        'order' => 2,
    ]);
    $textQ = SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Text,
        'label' => 'Your name',
        'required' => true,
        'order' => 3,
    ]);

    Livewire::test('survey-form', ['survey' => $survey])
        ->set('answers.'.$textQ->id, 'Jane')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);
});

test('honeypot field silently passes when filled', function () {
    $survey = Survey::factory()->create();
    SurveyQuestion::factory()->for($survey)->required()->create([
        'type' => SurveyQuestionType::Text,
        'label' => 'Real question',
        'order' => 1,
    ]);

    Livewire::test('survey-form', ['survey' => $survey])
        ->set('hp_field', 'bot caught')
        ->call('submit')
        ->assertSet('submitted', true);

    // Bot's response is not actually saved
    expect(SurveyResponse::count())->toBe(0);
    expect(SurveyAnswer::count())->toBe(0);
});

test('thank-you message renders the configured text', function () {
    $survey = Survey::factory()->create([
        'thank_you_message' => 'You are awesome — we will reach out soon.',
    ]);
    SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Text,
        'label' => 'Name',
        'order' => 1,
    ]);

    Livewire::test('survey-form', ['survey' => $survey])
        ->set('answers.'.$survey->questions->first()->id, 'Jane')
        ->call('submit')
        ->assertSee('You are awesome');
});

test('success_redirect_url redirects after submit', function () {
    $survey = Survey::factory()->create([
        'success_redirect_url' => 'https://example.com/thanks',
    ]);
    SurveyQuestion::factory()->for($survey)->create(['order' => 1]);

    Livewire::test('survey-form', ['survey' => $survey])
        ->set('answers.'.$survey->questions->first()->id, 'Hi')
        ->call('submit')
        ->assertRedirect('https://example.com/thanks');
});

test('survey response submitted event fires on completion', function () {
    Event::fake([SurveyResponseSubmitted::class]);

    $survey = Survey::factory()->create();
    SurveyQuestion::factory()->for($survey)->create(['order' => 1]);

    Livewire::test('survey-form', ['survey' => $survey])
        ->set('answers.'.$survey->questions->first()->id, 'Yo')
        ->call('submit');

    Event::assertDispatched(SurveyResponseSubmitted::class);
});

test('webhook fires when configured', function () {
    Http::fake();

    $survey = Survey::factory()->create([
        'webhook_url' => 'https://hooks.example.com/test',
    ]);
    $question = SurveyQuestion::factory()->for($survey)->create(['order' => 1]);

    $response = SurveyResponse::factory()->for($survey)->completed()->create();
    SurveyAnswer::factory()->for($response, 'response')->for($question, 'question')->create();

    (new NotifyOnSurveyResponse)->handle(
        new SurveyResponseSubmitted($response)
    );

    Http::assertSent(fn ($request) => $request->url() === 'https://hooks.example.com/test'
        && data_get($request->data(), 'event') === 'survey_response_submitted'
    );
});

test('conditional question hides when its visible_when does not match', function () {
    $survey = Survey::factory()->create();
    $trigger = SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::YesNo,
        'label' => 'Are you the decision maker?',
        'required' => true,
        'order' => 1,
    ]);
    $follow = SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Text,
        'label' => 'Who is?',
        'required' => true,
        'order' => 2,
        'visible_when' => [
            'question_id' => $trigger->id,
            'operator' => 'equals',
            'value' => 'no',
        ],
    ]);

    // When trigger = 'yes', the follow-up should be hidden and not validated
    Livewire::test('survey-form', ['survey' => $survey])
        ->set('answers.'.$trigger->id, 'yes')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    // When trigger = 'no' and follow-up empty, validation kicks in
    SurveyResponse::query()->delete();
    SurveyAnswer::query()->delete();

    Livewire::test('survey-form', ['survey' => $survey])
        ->set('answers.'.$trigger->id, 'no')
        ->call('submit')
        ->assertHasErrors(['answers.'.$follow->id]);
});

test('duplicate clones survey with all questions', function () {
    $survey = Survey::factory()->create(['title' => 'Original']);
    SurveyQuestion::factory()->for($survey)->count(3)->sequence(
        ['order' => 1, 'label' => 'Q1'],
        ['order' => 2, 'label' => 'Q2'],
        ['order' => 3, 'label' => 'Q3'],
    )->create();

    $copy = $survey->duplicate();

    expect($copy->id)->not->toBe($survey->id);
    expect($copy->title)->toBe('Original (copy)');
    expect($copy->status)->toBe(SurveyStatus::Draft);
    expect($copy->questions()->count())->toBe(3);
});

test('survey with audience can be created via factory', function () {
    $survey = Survey::factory()->audience(SurveyAudience::Tenant)->create();

    expect($survey->audience)->toBe(SurveyAudience::Tenant);
});

test('progress count updates as answers come in', function () {
    $survey = Survey::factory()->create();
    $q1 = SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Text,
        'label' => 'Name',
        'order' => 1,
    ]);
    $q2 = SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Email,
        'label' => 'Email',
        'order' => 2,
    ]);

    $component = Livewire::test('survey-form', ['survey' => $survey]);

    // Before any answers are filled, the answered count should be 0.
    $component->call('$refresh');
    $payload = $component->viewData('answeredCount');
    expect($payload)->toBe(0);

    // After setting q1, answered count should be 1.
    $component->set('answers.'.$q1->id, 'Acme');
    expect($component->viewData('answeredCount'))->toBe(1);

    // After setting q2 too, answered count should be 2.
    $component->set('answers.'.$q2->id, 'a@b.com');
    expect($component->viewData('answeredCount'))->toBe(2);
});

test('multi-step splits questions on section dividers', function () {
    $survey = Survey::factory()->create(['multi_step' => true]);
    SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Text, 'label' => 'Name', 'order' => 1]);
    SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Section, 'label' => 'Contact info', 'order' => 2]);
    SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Email, 'label' => 'Email', 'order' => 3]);
    SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Section, 'label' => 'Feedback', 'order' => 4]);
    SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Textarea, 'label' => 'Comments', 'order' => 5]);

    $component = Livewire::test('survey-form', ['survey' => $survey]);

    expect($component->viewData('totalSteps'))->toBe(3);
    expect($component->viewData('isMultiStep'))->toBeTrue();
    expect($component->viewData('isFirstStep'))->toBeTrue();
    expect($component->viewData('isLastStep'))->toBeFalse();
});

test('next step validates current step before advancing', function () {
    $survey = Survey::factory()->create(['multi_step' => true]);
    $q1 = SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Text,
        'label' => 'Required field',
        'required' => true,
        'order' => 1,
    ]);
    SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Section, 'label' => 'Step 2', 'order' => 2]);
    SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Text, 'label' => 'Field 2', 'order' => 3]);

    // Cannot advance without filling required field
    Livewire::test('survey-form', ['survey' => $survey])
        ->call('nextStep')
        ->assertHasErrors(['answers.'.$q1->id])
        ->assertSet('currentStep', 0);

    // Can advance after filling
    Livewire::test('survey-form', ['survey' => $survey])
        ->set('answers.'.$q1->id, 'filled')
        ->call('nextStep')
        ->assertHasNoErrors()
        ->assertSet('currentStep', 1);
});

test('prev step preserves answers and goes back', function () {
    $survey = Survey::factory()->create(['multi_step' => true]);
    $q1 = SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Text, 'label' => 'Q1', 'order' => 1]);
    SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Section, 'label' => 'Step 2', 'order' => 2]);
    $q2 = SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Text, 'label' => 'Q2', 'order' => 3]);

    $component = Livewire::test('survey-form', ['survey' => $survey])
        ->set('answers.'.$q1->id, 'first')
        ->call('nextStep')
        ->set('answers.'.$q2->id, 'second')
        ->call('prevStep');

    expect($component->get('currentStep'))->toBe(0);
    expect($component->get('answers.'.$q1->id))->toBe('first');
    expect($component->get('answers.'.$q2->id))->toBe('second');
});

test('submit on intermediate step advances rather than finishing', function () {
    $survey = Survey::factory()->create(['multi_step' => true]);
    $q1 = SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Text, 'label' => 'Q1', 'order' => 1]);
    SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Section, 'label' => 'Step 2', 'order' => 2]);
    SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Text, 'label' => 'Q2', 'order' => 3]);

    Livewire::test('survey-form', ['survey' => $survey])
        ->set('answers.'.$q1->id, 'first')
        ->call('submit')
        ->assertSet('submitted', false)
        ->assertSet('currentStep', 1);

    expect(SurveyResponse::count())->toBe(0);
});

test('submit on last step persists the response', function () {
    $survey = Survey::factory()->create(['multi_step' => true]);
    $q1 = SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Text, 'label' => 'Q1', 'order' => 1]);
    SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Section, 'label' => 'Step 2', 'order' => 2]);
    $q2 = SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Text, 'label' => 'Q2', 'order' => 3]);

    Livewire::test('survey-form', ['survey' => $survey])
        ->set('answers.'.$q1->id, 'first')
        ->call('nextStep')
        ->set('answers.'.$q2->id, 'second')
        ->call('submit')
        ->assertSet('submitted', true);

    expect(SurveyResponse::count())->toBe(1);
    expect(SurveyResponse::first()->isCompleted())->toBeTrue();
});

test('single-page mode submits in one shot regardless of sections', function () {
    $survey = Survey::factory()->create(['multi_step' => false]);
    $q1 = SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Text, 'label' => 'Q1', 'order' => 1]);
    SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Section, 'label' => 'Sec', 'order' => 2]);
    $q2 = SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Text, 'label' => 'Q2', 'order' => 3]);

    $component = Livewire::test('survey-form', ['survey' => $survey]);
    expect($component->viewData('isMultiStep'))->toBeFalse();
    expect($component->viewData('totalSteps'))->toBe(2); // steps still computed but not used

    $component
        ->set('answers.'.$q1->id, 'a')
        ->set('answers.'.$q2->id, 'b')
        ->call('submit')
        ->assertSet('submitted', true);

    expect(SurveyResponse::count())->toBe(1);
});

test('starts_new_step flag breaks a question into its own step', function () {
    $survey = Survey::factory()->create(['multi_step' => true]);
    $q1 = SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Text,
        'label' => 'Name',
        'order' => 1,
    ]);
    $q2 = SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Email,
        'label' => 'Email',
        'starts_new_step' => true,
        'order' => 2,
    ]);
    $q3 = SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Phone,
        'label' => 'Phone',
        'order' => 3,
    ]);

    $component = Livewire::test('survey-form', ['survey' => $survey]);

    expect($component->viewData('totalSteps'))->toBe(2);
    expect($component->viewData('isMultiStep'))->toBeTrue();

    // Advance after filling first step
    $component->set('answers.'.$q1->id, 'Jane')->call('nextStep');
    expect($component->get('currentStep'))->toBe(1);
});

test('starts_new_step plus section dividers can coexist', function () {
    $survey = Survey::factory()->create(['multi_step' => true]);
    SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Text, 'label' => 'Q1', 'order' => 1]);
    SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Email,
        'label' => 'Q2',
        'starts_new_step' => true,
        'order' => 2,
    ]);
    SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Section, 'label' => 'Section A', 'order' => 3]);
    SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Phone, 'label' => 'Q3', 'order' => 4]);

    $component = Livewire::test('survey-form', ['survey' => $survey]);
    expect($component->viewData('totalSteps'))->toBe(3);
});

test('starts_new_step is ignored when multi_step is off', function () {
    $survey = Survey::factory()->create(['multi_step' => false]);
    SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Text, 'label' => 'Q1', 'order' => 1]);
    SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Email,
        'label' => 'Q2',
        'starts_new_step' => true,
        'order' => 2,
    ]);

    $component = Livewire::test('survey-form', ['survey' => $survey]);
    expect($component->viewData('isMultiStep'))->toBeFalse();
});

test('coverVideoEmbed returns YouTube embed for a watch URL', function () {
    $survey = Survey::factory()->make(['cover_video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);
    expect($survey->coverVideoEmbed())->toMatchArray([
        'kind' => 'youtube',
        'embed' => 'https://www.youtube.com/embed/dQw4w9WgXcQ?rel=0',
    ]);
});

test('coverVideoEmbed returns YouTube embed for a youtu.be short URL', function () {
    $survey = Survey::factory()->make(['cover_video_url' => 'https://youtu.be/dQw4w9WgXcQ']);
    expect($survey->coverVideoEmbed()['kind'])->toBe('youtube');
    expect($survey->coverVideoEmbed()['embed'])->toBe('https://www.youtube.com/embed/dQw4w9WgXcQ?rel=0');
});

test('coverVideoEmbed returns Vimeo embed', function () {
    $survey = Survey::factory()->make(['cover_video_url' => 'https://vimeo.com/123456789']);
    expect($survey->coverVideoEmbed())->toMatchArray([
        'kind' => 'vimeo',
        'embed' => 'https://player.vimeo.com/video/123456789',
    ]);
});

test('coverVideoEmbed returns file kind for direct .mp4 URLs', function () {
    $survey = Survey::factory()->make(['cover_video_url' => 'https://cdn.example.com/intro.mp4']);
    expect($survey->coverVideoEmbed()['kind'])->toBe('file');
});

test('coverVideoEmbed returns iframe kind for unknown providers', function () {
    $survey = Survey::factory()->make(['cover_video_url' => 'https://www.loom.com/share/abc123']);
    expect($survey->coverVideoEmbed()['kind'])->toBe('iframe');
});

test('coverVideoEmbed returns none when no video URL is set', function () {
    $survey = Survey::factory()->make(['cover_video_url' => null]);
    expect($survey->coverVideoEmbed()['kind'])->toBe('none');
});

test('intro screen renders the YouTube iframe when video URL is set', function () {
    $survey = Survey::factory()->create([
        'cover_video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'slug' => 'video-survey',
    ]);
    SurveyQuestion::factory()->for($survey)->create(['type' => SurveyQuestionType::Text, 'order' => 1]);

    $this->get('/s/'.$survey->slug)
        ->assertOk()
        ->assertSee('youtube.com/embed/dQw4w9WgXcQ', false);
});
