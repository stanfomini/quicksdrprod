<?php

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Services\Provisioning\ForgeClient;
use Illuminate\Support\Str;

beforeEach(function () {
    config(['app.mode' => 'orchestrator', 'app.url' => 'https://quicksdr.com']);
    $this->forge = Mockery::mock(ForgeClient::class);
    $this->app->instance(ForgeClient::class, $this->forge);
});

test('dry-run reports what would change without calling updateSiteEnvironment', function () {
    Tenant::factory()->create([
        'subdomain' => 'acme',
        'status' => TenantStatus::Ready,
        'forge_server_id' => 10,
        'forge_site_id' => 20,
        'stats_hmac_key' => Str::random(64),
    ]);

    $this->forge->shouldReceive('siteEnvironment')->once()->andReturn("APP_NAME=QuickSDR\nAPP_URL=https://acme.quicksdr.com\n");
    $this->forge->shouldNotReceive('updateSiteEnvironment');

    $this->artisan('tenants:backfill-hmac')
        ->assertExitCode(0)
        ->expectsOutputToContain('[DRY-RUN] Scanning 1 tenant(s)')
        ->expectsOutputToContain('[acme] STATS_HMAC_KEY MISSING');
});

test('apply writes env with HMAC injected and queues config:clear', function () {
    $hmac = Str::random(64);
    $tenant = Tenant::factory()->create([
        'subdomain' => 'beta',
        'status' => TenantStatus::Ready,
        'forge_server_id' => 11,
        'forge_site_id' => 21,
        'stats_hmac_key' => $hmac,
    ]);

    $this->forge->shouldReceive('siteEnvironment')
        ->with(11, 21)
        ->andReturn("APP_NAME=QuickSDR\nAPP_URL=https://beta.quicksdr.com\n");

    $this->forge->shouldReceive('updateSiteEnvironment')
        ->once()
        ->with(11, 21, Mockery::on(function (string $env) use ($hmac) {
            return str_contains($env, "STATS_HMAC_KEY={$hmac}")
                && str_contains($env, 'ORCHESTRATOR_URL=https://quicksdr.com')
                && str_contains($env, 'APP_MODE=tenant')
                && str_contains($env, 'APP_NAME=QuickSDR');
        }))
        ->andReturnNull();

    $this->forge->shouldReceive('executeSiteCommand')
        ->once()
        ->with(11, 21, 'php artisan config:clear')
        ->andReturn(999);

    $this->artisan('tenants:backfill-hmac', ['--apply' => true])
        ->assertExitCode(0)
        ->expectsOutputToContain('[beta] wrote env');
});

test('skips tenants whose env already has the matching STATS_HMAC_KEY', function () {
    $hmac = Str::random(64);
    Tenant::factory()->create([
        'subdomain' => 'gamma',
        'status' => TenantStatus::Ready,
        'forge_server_id' => 12,
        'forge_site_id' => 22,
        'stats_hmac_key' => $hmac,
    ]);

    $this->forge->shouldReceive('siteEnvironment')
        ->andReturn("APP_NAME=QuickSDR\nSTATS_HMAC_KEY={$hmac}\n");
    $this->forge->shouldNotReceive('updateSiteEnvironment');

    $this->artisan('tenants:backfill-hmac', ['--apply' => true])
        ->assertExitCode(0)
        ->expectsOutputToContain('[gamma] already correct');
});

test('generates stats_hmac_key on Tenant row when NULL and --apply', function () {
    $tenant = Tenant::factory()->create([
        'subdomain' => 'delta',
        'status' => TenantStatus::Ready,
        'forge_server_id' => 13,
        'forge_site_id' => 23,
        'stats_hmac_key' => null,
    ]);

    $this->forge->shouldReceive('siteEnvironment')->andReturn('');
    $this->forge->shouldReceive('updateSiteEnvironment')->once();
    $this->forge->shouldReceive('executeSiteCommand')->once()->andReturn(1);

    $this->artisan('tenants:backfill-hmac', ['--apply' => true])
        ->assertExitCode(0);

    expect($tenant->fresh()->stats_hmac_key)->not->toBeNull()->not->toBe('');
});

test('skips destroyed tenants', function () {
    Tenant::factory()->create([
        'subdomain' => 'dead',
        'status' => TenantStatus::Destroyed,
        'forge_server_id' => 14,
        'forge_site_id' => 24,
        'stats_hmac_key' => Str::random(64),
    ]);

    $this->forge->shouldNotReceive('siteEnvironment');
    $this->forge->shouldNotReceive('updateSiteEnvironment');

    $this->artisan('tenants:backfill-hmac', ['--apply' => true])
        ->assertExitCode(0)
        ->expectsOutputToContain('No tenants with Forge sites found');
});

test('--tenant filter targets a single subdomain', function () {
    Tenant::factory()->create([
        'subdomain' => 'one',
        'status' => TenantStatus::Ready,
        'forge_server_id' => 15,
        'forge_site_id' => 25,
        'stats_hmac_key' => Str::random(64),
    ]);
    Tenant::factory()->create([
        'subdomain' => 'two',
        'status' => TenantStatus::Ready,
        'forge_server_id' => 16,
        'forge_site_id' => 26,
        'stats_hmac_key' => Str::random(64),
    ]);

    $this->forge->shouldReceive('siteEnvironment')->once()->andReturn('');
    $this->forge->shouldNotReceive('updateSiteEnvironment');

    $this->artisan('tenants:backfill-hmac', ['--tenant' => 'one'])
        ->assertExitCode(0)
        ->expectsOutputToContain('Scanning 1 tenant(s)');
});

test('--no-refresh-config skips config:clear even when applying', function () {
    Tenant::factory()->create([
        'subdomain' => 'no-refresh',
        'status' => TenantStatus::Ready,
        'forge_server_id' => 17,
        'forge_site_id' => 27,
        'stats_hmac_key' => Str::random(64),
    ]);

    $this->forge->shouldReceive('siteEnvironment')->andReturn('');
    $this->forge->shouldReceive('updateSiteEnvironment')->once();
    $this->forge->shouldNotReceive('executeSiteCommand');

    $this->artisan('tenants:backfill-hmac', ['--apply' => true, '--no-refresh-config' => true])
        ->assertExitCode(0);
});
