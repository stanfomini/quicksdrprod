<?php

use App\Http\Controllers\Api\StatsIngestController;
use App\Http\Controllers\Api\TenantSubscriptionApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Orchestrator API surface. All routes are behind the `orchestrator`
| middleware alias so they 404 on tenant servers (APP_MODE=tenant).
|
| Individual feature routes (stats ingest, signup status, etc.) are added
| by the teammates who own those features.
|
*/

Route::middleware(['orchestrator'])->group(function () {
    Route::post('/stats/ingest', [StatsIngestController::class, 'store'])
        ->name('api.stats.ingest');

    // Tenant-side billing page pulls live Stripe state from here (HMAC-signed
    // with the per-tenant stats_hmac_key). Fixes the stale-.env problem where
    // TIER/STRIPE_SUBSCRIPTION_STATUS etc. are frozen at provisioning time.
    Route::get('/tenant/{subdomain}/subscription', [TenantSubscriptionApiController::class, 'show'])
        ->name('api.tenant.subscription');

    // Signup status polling is wired up by signup-builder
});
