<?php

use App\Enums\CrmField;
use App\Enums\SurveyQuestionType;
use App\Events\SurveyResponseSubmitted;
use App\Listeners\LinkResponseToLead;
use App\Models\Lead;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\Tenant;

test('phone normalization handles common US formats', function () {
    expect(Lead::normalizePhone('(817) 555-0123'))->toBe('+18175550123');
    expect(Lead::normalizePhone('817-555-0123'))->toBe('+18175550123');
    expect(Lead::normalizePhone('+1 817 555 0123'))->toBe('+18175550123');
    expect(Lead::normalizePhone('44 20 7946 0958'))->toBe('+442079460958');
    expect(Lead::normalizePhone(''))->toBeNull();
    expect(Lead::normalizePhone(null))->toBeNull();
});

test('email normalization lowercases and trims', function () {
    expect(Lead::normalizeEmail('  Jane@Example.COM '))->toBe('jane@example.com');
    expect(Lead::normalizeEmail(''))->toBeNull();
    expect(Lead::normalizeEmail(null))->toBeNull();
});

test('findOrCreateMatching creates a new lead when none exists', function () {
    $lead = Lead::findOrCreateMatching([
        'email' => 'New@Example.com',
        'name' => 'New Person',
    ]);

    expect(Lead::count())->toBe(1);
    expect($lead->email)->toBe('new@example.com');
    expect($lead->name)->toBe('New Person');
    expect($lead->last_seen_at)->not->toBeNull();
});

test('findOrCreateMatching matches by email and updates last_seen without overwriting fields', function () {
    Lead::factory()->create([
        'email' => 'jane@example.com',
        'name' => 'Existing Name',
        'last_seen_at' => now()->subDays(5),
    ]);

    $lead = Lead::findOrCreateMatching([
        'email' => 'jane@example.com',
        'name' => 'Different Name',
    ]);

    expect(Lead::count())->toBe(1);
    expect($lead->name)->toBe('Existing Name');
    expect($lead->last_seen_at->isToday())->toBeTrue();
});

test('findOrCreateMatching falls back to phone when email missing', function () {
    Lead::factory()->create([
        'email' => null,
        'phone' => '+18175550123',
        'name' => 'Phone Person',
        'company' => null,
    ]);

    $lead = Lead::findOrCreateMatching([
        'phone' => '(817) 555-0123',
        'company' => 'Acme',
    ]);

    expect(Lead::count())->toBe(1);
    expect($lead->company)->toBe('Acme');
});

test('linkToTenantIfPossible sets tenant_id when emails match', function () {
    $tenant = Tenant::factory()->create(['admin_email' => 'tenant@example.com']);
    $lead = Lead::factory()->create([
        'email' => 'tenant@example.com',
        'tenant_id' => null,
    ]);

    $lead->linkToTenantIfPossible();

    expect($lead->fresh()->tenant_id)->toBe($tenant->id);
});

test('LinkResponseToLead listener creates a lead and sets lead_id on the response', function () {
    $survey = Survey::factory()->create();
    $emailQ = SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Email,
        'label' => 'Email',
        'order' => 1,
    ]);
    $nameQ = SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Text,
        'label' => 'Their name',
        'crm_field' => CrmField::Name,
        'order' => 2,
    ]);

    $response = SurveyResponse::factory()->for($survey)->completed()->create();
    SurveyAnswer::create([
        'survey_response_id' => $response->id,
        'survey_question_id' => $emailQ->id,
        'value' => ['answer' => 'lead@example.com'],
    ]);
    SurveyAnswer::create([
        'survey_response_id' => $response->id,
        'survey_question_id' => $nameQ->id,
        'value' => ['answer' => 'Lead Person'],
    ]);

    (new LinkResponseToLead)->handle(new SurveyResponseSubmitted($response));

    $lead = Lead::first();
    expect($lead)->not->toBeNull();
    expect($lead->email)->toBe('lead@example.com');
    expect($lead->name)->toBe('Lead Person');
    expect($response->fresh()->lead_id)->toBe($lead->id);
});

test('LinkResponseToLead skips when no email or phone is present', function () {
    $survey = Survey::factory()->create();
    $textQ = SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Text,
        'label' => 'Comments',
        'order' => 1,
    ]);

    $response = SurveyResponse::factory()->for($survey)->completed()->create();
    SurveyAnswer::create([
        'survey_response_id' => $response->id,
        'survey_question_id' => $textQ->id,
        'value' => ['answer' => 'Just a comment, no contact info'],
    ]);

    (new LinkResponseToLead)->handle(new SurveyResponseSubmitted($response));

    expect(Lead::count())->toBe(0);
    expect($response->fresh()->lead_id)->toBeNull();
});

test('TenantObserver backfills lead.tenant_id when a tenant is created with a matching email', function () {
    $lead = Lead::factory()->create([
        'email' => 'converted@example.com',
        'tenant_id' => null,
    ]);

    $tenant = Tenant::factory()->create(['admin_email' => 'converted@example.com']);

    expect($lead->fresh()->tenant_id)->toBe($tenant->id);
});

test('TenantObserver does not overwrite a lead already linked to a different tenant', function () {
    $existingTenant = Tenant::factory()->create();
    $lead = Lead::factory()->create([
        'email' => 'shared@example.com',
        'tenant_id' => $existingTenant->id,
    ]);

    $newTenant = Tenant::factory()->create(['admin_email' => 'shared@example.com']);

    expect($lead->fresh()->tenant_id)->toBe($existingTenant->id);
});

test('crm_field on a question takes precedence over the type-based default', function () {
    $survey = Survey::factory()->create();
    $q = SurveyQuestion::factory()->for($survey)->create([
        'type' => SurveyQuestionType::Text,
        'label' => 'Best contact number',
        'crm_field' => CrmField::Phone,
        'order' => 1,
    ]);

    $response = SurveyResponse::factory()->for($survey)->completed()->create();
    SurveyAnswer::create([
        'survey_response_id' => $response->id,
        'survey_question_id' => $q->id,
        'value' => ['answer' => '8175550123'],
    ]);

    (new LinkResponseToLead)->handle(new SurveyResponseSubmitted($response));

    $lead = Lead::first();
    expect($lead)->not->toBeNull();
    expect($lead->phone)->toBe('+18175550123');
});
