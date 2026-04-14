<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * On tenant servers (APP_MODE=tenant), if the local Tenant record shows
 * we are in export-only mode, redirect every authenticated user to the
 * /export page where they can download their data. Active only when a
 * tenant has cancelled and is inside the 7-day grace period.
 *
 * Implementation note: the tenant's OWN database has a small tenants
 * meta table that mirrors the orchestrator's status (synced by the
 * orchestrator on cancel via a one-shot webhook). For the MVP we read
 * a config flag instead — orchestrator pushes TENANT_EXPORT_ONLY_UNTIL
 * into the tenant's .env on cancel and the next deploy picks it up.
 */
class EnforceExportOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.mode') !== 'tenant') {
            return $next($request);
        }

        $cutoff = config('app.export_only_until');

        if ($cutoff && now()->lt($cutoff)) {
            $exportRoute = '/export';

            // Allow logout + the export page itself, block everything else.
            if (! $request->is('export', 'export/*', 'logout', 'login')) {
                return redirect($exportRoute);
            }
        }

        return $next($request);
    }
}
