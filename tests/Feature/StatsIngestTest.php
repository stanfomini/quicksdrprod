<?php

use App\Models\Tenant;
use App\Models\TenantStat;
use Illuminate\Support\Str;

beforeEach(function () {
    config(['app.mode' => 'orchestrator']);
});

function validStatsPayload(Tenant $tenant): array
{
    return [
        'tenant_id' => $tenant->id,
        'period_start' => '2026-04-13T00:00:00+00:00',
        'period_end' => '2026-04-13T01:00:00+00:00',
        'agent_count' => 4,
        'call_count' => 120,
        'total_talk_seconds' => 3600,
        'avg_call_duration_seconds' => 30,
        'disposition_counts' => ['voicemail' => 40, 'connected' => 80],
    ];
}

test('401 when X-Tenant-Signature header is missing', function () {
    $tenant = Tenant::factory()->create();

    $this->postJson(route('api.stats.ingest'), validStatsPayload($tenant))
        ->assertStatus(401);

    expect(TenantStat::count())->toBe(0);
});

test('401 when signature does not match HMAC of body', function () {
    $tenant = Tenant::factory()->create([
        'stats_hmac_key' => Str::random(64),
    ]);

    $payload = validStatsPayload($tenant);
    $body = json_encode($payload);

    $this->call(
        'POST',
        route('api.stats.ingest'),
        [], [], [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_TENANT_SIGNATURE' => 'deadbeef-not-a-real-hmac',
        ],
        $body,
    )->assertStatus(401);

    expect(TenantStat::count())->toBe(0);
});

test('200 + row upserted when signature matches', function () {
    $hmacKey = Str::random(64);
    $tenant = Tenant::factory()->create([
        'stats_hmac_key' => $hmacKey,
    ]);

    $payload = validStatsPayload($tenant);
    $body = json_encode($payload);
    $signature = hash_hmac('sha256', $body, $hmacKey);

    $this->call(
        'POST',
        route('api.stats.ingest'),
        [], [], [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_TENANT_SIGNATURE' => $signature,
        ],
        $body,
    )->assertOk()->assertJson(['status' => 'ok']);

    expect(TenantStat::count())->toBe(1);

    $stat = TenantStat::first();
    expect($stat->tenant_id)->toBe($tenant->id);
    expect($stat->agent_count)->toBe(4);
    expect($stat->call_count)->toBe(120);
    expect($stat->total_talk_seconds)->toBe(3600);
    expect($stat->avg_call_duration_seconds)->toBe(30);
    expect($stat->disposition_counts)->toBe(['voicemail' => 40, 'connected' => 80]);
    expect($stat->ingested_at)->not->toBeNull();

    // Upsert on duplicate (tenant_id, period_start) — updates the row, no duplicate.
    $updated = array_merge($payload, ['call_count' => 150]);
    $updatedBody = json_encode($updated);
    $updatedSig = hash_hmac('sha256', $updatedBody, $hmacKey);

    $this->call(
        'POST',
        route('api.stats.ingest'),
        [], [], [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_TENANT_SIGNATURE' => $updatedSig,
        ],
        $updatedBody,
    )->assertOk();

    expect(TenantStat::count())->toBe(1);
    expect(TenantStat::first()->call_count)->toBe(150);
});
