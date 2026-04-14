<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate admin routes behind `users.type = 'internal'`. Used together with
 * the `auth` and `orchestrator` middlewares for /admin/* routes.
 */
class EnsureInternalUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ($user->type ?? null) !== 'internal') {
            abort(403);
        }

        return $next($request);
    }
}
