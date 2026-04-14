<?php

use App\Http\Middleware\EnforceExportOnly;
use App\Http\Middleware\EnsureInternalUser;
use App\Http\Middleware\EnsureOrchestrator;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'orchestrator' => EnsureOrchestrator::class,
            'internal-user' => EnsureInternalUser::class,
            'export-only' => EnforceExportOnly::class,
        ]);

        // Always apply EnforceExportOnly on tenant web requests so cancelled
        // tenants get redirected to /export. On orchestrator it's a no-op
        // (config('app.mode') !== 'tenant' short-circuits in the middleware).
        $middleware->web(append: [
            EnforceExportOnly::class,
        ]);

        // Stripe webhook must be CSRF-exempt.
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
