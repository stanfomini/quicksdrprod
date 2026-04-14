<?php

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
    // Stats ingest route is wired up by stats-ingest-builder
    // Signup status polling is wired up by signup-builder
});
