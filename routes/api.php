<?php

use App\Http\Controllers\Api\StatsIngestController;
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

    // Signup status polling is wired up by signup-builder
});
