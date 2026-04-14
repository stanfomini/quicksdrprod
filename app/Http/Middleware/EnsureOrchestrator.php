<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate orchestrator-only routes (signup, admin tenants, stripe webhook,
 * stats ingest). On tenant servers APP_MODE=tenant, these routes 404.
 */
class EnsureOrchestrator
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.mode') !== 'orchestrator') {
            abort(404);
        }

        return $next($request);
    }
}
