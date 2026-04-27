<?php

use App\Http\Middleware\EnforceExportOnly;
use App\Http\Middleware\EnsureInternalUser;
use App\Http\Middleware\EnsureOrchestrator;
use App\Http\Middleware\RequireEmailOtp;
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
            'email-otp' => RequireEmailOtp::class,
        ]);

        // Global web middleware:
        // - EnforceExportOnly redirects cancelled tenants to /export
        // - RequireEmailOtp (production only) redirects authenticated users
        //   to /email-code until they've completed the 6-digit email OTP.
        //   Marketing pages are whitelisted inside the middleware itself.
        $middleware->web(append: [
            EnforceExportOnly::class,
            RequireEmailOtp::class,
        ]);

        // Stripe webhook must be CSRF-exempt.
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Log every 419 with the offending URL, method, and stack so we can
        // identify whether it comes from CSRF, Livewire snapshot, or TypeError.
        $exceptions->report(function (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            if ($e->getStatusCode() === 419) {
                \Illuminate\Support\Facades\Log::warning('419 captured', [
                    'url' => request()->fullUrl(),
                    'method' => request()->method(),
                    'livewire_call' => request()->input('components.0.calls.0.method'),
                    'exception_class' => get_class($e),
                    'message' => $e->getMessage(),
                    'trace' => collect($e->getTrace())->take(6)->map(fn ($f) => ($f['class'] ?? '').'@'.($f['function'] ?? ''))->join(' → '),
                ]);
            }
        });
    })->create();
