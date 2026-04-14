<?php

use App\Enums\TenantStatus;
use App\Jobs\DestroyTenantServer;
use App\Models\Tenant;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

if (config('app.mode') === 'tenant') {
    Schedule::command('claimstaker:process-callbacks')->everyMinute()->withoutOverlapping();
    Schedule::command('claimstaker:execute-sequences')->everyFiveMinutes()->withoutOverlapping();
    Schedule::command('claimstaker:clean-stale-sessions')->daily();
}

if (config('app.mode') === 'orchestrator') {
    Schedule::call(function () {
        Tenant::where('status', TenantStatus::ExportOnly)
            ->whereNotNull('export_only_until')
            ->where('export_only_until', '<', now())
            ->each(fn (Tenant $t) => DestroyTenantServer::dispatch($t));
    })->name('tenants:destroy-expired')->daily();
}
