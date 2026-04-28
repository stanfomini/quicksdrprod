<?php

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Services\Provisioning\TenantBilling;

beforeEach(function () {
    config(['app.mode' => 'orchestrator']);
});

function fakeBillingService(array $subscriptionByTenantId, array &$cancelled): TenantBilling
{
    return new class($subscriptionByTenantId, $cancelled) extends TenantBilling
    {
        /** @var array<int, array<string, mixed>|null> */
        private array $subs;

        /** @var array<int, int> */
        private array $cancelledRef;

        public function __construct(array $subs, array &$cancelled)
        {
            $this->subs = $subs;
            $this->cancelledRef = &$cancelled;
        }

        public function subscription(Tenant $tenant): ?array
        {
            return $this->subs[$tenant->id] ?? null;
        }

        public function cancelSubscription(Tenant $tenant): void
        {
            $this->cancelledRef[] = $tenant->id;
        }
    };
}

test('cancels past_due subscription beyond grace window', function () {
    $tenant = Tenant::factory()->create([
        'status' => TenantStatus::Ready,
        'stripe_customer_id' => 'cus_test',
        'stripe_subscription_id' => 'sub_test',
    ]);

    $cancelled = [];
    $this->app->instance(TenantBilling::class, fakeBillingService([
        $tenant->id => [
            'status' => 'past_due',
            'current_period_end' => now()->subDays(10)->timestamp,
        ],
    ], $cancelled));

    $this->artisan('tenants:enforce-billing')->assertSuccessful();

    expect($cancelled)->toBe([$tenant->id]);
});

test('skips past_due subscription within grace window', function () {
    $tenant = Tenant::factory()->create([
        'status' => TenantStatus::Ready,
        'stripe_customer_id' => 'cus_test',
        'stripe_subscription_id' => 'sub_test',
    ]);

    $cancelled = [];
    $this->app->instance(TenantBilling::class, fakeBillingService([
        $tenant->id => [
            'status' => 'past_due',
            'current_period_end' => now()->subDays(2)->timestamp,
        ],
    ], $cancelled));

    $this->artisan('tenants:enforce-billing')->assertSuccessful();

    expect($cancelled)->toBe([]);
});

test('cancels unpaid subscription beyond grace window', function () {
    $tenant = Tenant::factory()->create([
        'status' => TenantStatus::Ready,
        'stripe_customer_id' => 'cus_test',
        'stripe_subscription_id' => 'sub_test',
    ]);

    $cancelled = [];
    $this->app->instance(TenantBilling::class, fakeBillingService([
        $tenant->id => [
            'status' => 'unpaid',
            'current_period_end' => now()->subDays(15)->timestamp,
        ],
    ], $cancelled));

    $this->artisan('tenants:enforce-billing')->assertSuccessful();

    expect($cancelled)->toBe([$tenant->id]);
});

test('does not cancel active subscription', function () {
    $tenant = Tenant::factory()->create([
        'status' => TenantStatus::Ready,
        'stripe_customer_id' => 'cus_test',
        'stripe_subscription_id' => 'sub_test',
    ]);

    $cancelled = [];
    $this->app->instance(TenantBilling::class, fakeBillingService([
        $tenant->id => [
            'status' => 'active',
            'current_period_end' => now()->addDays(20)->timestamp,
        ],
    ], $cancelled));

    $this->artisan('tenants:enforce-billing')->assertSuccessful();

    expect($cancelled)->toBe([]);
});

test('skips tenants without stripe_subscription_id', function () {
    Tenant::factory()->create([
        'status' => TenantStatus::Ready,
        'stripe_subscription_id' => null,
    ]);

    $cancelled = [];
    $this->app->instance(TenantBilling::class, fakeBillingService([], $cancelled));

    $this->artisan('tenants:enforce-billing')->assertSuccessful();

    expect($cancelled)->toBe([]);
});

test('skips tenants in ExportOnly or Destroyed status', function () {
    $exportOnly = Tenant::factory()->create([
        'status' => TenantStatus::ExportOnly,
        'stripe_customer_id' => 'cus_export',
        'stripe_subscription_id' => 'sub_export',
        'export_only_until' => now()->addDays(5),
    ]);

    $destroyed = Tenant::factory()->create([
        'status' => TenantStatus::Destroyed,
        'stripe_customer_id' => 'cus_dead',
        'stripe_subscription_id' => 'sub_dead',
    ]);

    $cancelled = [];
    $this->app->instance(TenantBilling::class, fakeBillingService([
        $exportOnly->id => ['status' => 'past_due', 'current_period_end' => now()->subDays(30)->timestamp],
        $destroyed->id => ['status' => 'past_due', 'current_period_end' => now()->subDays(30)->timestamp],
    ], $cancelled));

    $this->artisan('tenants:enforce-billing')->assertSuccessful();

    expect($cancelled)->toBe([]);
});

test('dry-run reports but does not cancel', function () {
    $tenant = Tenant::factory()->create([
        'status' => TenantStatus::Ready,
        'stripe_customer_id' => 'cus_test',
        'stripe_subscription_id' => 'sub_test',
    ]);

    $cancelled = [];
    $this->app->instance(TenantBilling::class, fakeBillingService([
        $tenant->id => [
            'status' => 'past_due',
            'current_period_end' => now()->subDays(10)->timestamp,
        ],
    ], $cancelled));

    $this->artisan('tenants:enforce-billing', ['--dry-run' => true])->assertSuccessful();

    expect($cancelled)->toBe([]);
});

test('grace-days option overrides default', function () {
    $tenant = Tenant::factory()->create([
        'status' => TenantStatus::Ready,
        'stripe_customer_id' => 'cus_test',
        'stripe_subscription_id' => 'sub_test',
    ]);

    $cancelled = [];
    $this->app->instance(TenantBilling::class, fakeBillingService([
        $tenant->id => [
            'status' => 'past_due',
            'current_period_end' => now()->subDays(2)->timestamp,
        ],
    ], $cancelled));

    $this->artisan('tenants:enforce-billing', ['--grace-days' => 1])->assertSuccessful();

    expect($cancelled)->toBe([$tenant->id]);
});
