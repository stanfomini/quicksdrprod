<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('claimstaker:process-callbacks')->everyMinute()->withoutOverlapping();
Schedule::command('claimstaker:execute-sequences')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('claimstaker:clean-stale-sessions')->daily();
