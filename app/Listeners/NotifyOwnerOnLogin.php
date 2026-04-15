<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Mail;

class NotifyOwnerOnLogin
{
    public function handle(Login $event): void
    {
        if (! app()->environment('production')) {
            return;
        }

        $request = request();

        Mail::raw(
            "QuickSDR production login\n\n"
            ."User: {$event->user->email}\n"
            .'Time: '.now()->toDayDateTimeString()."\n"
            .'IP: '.$request->ip()."\n"
            .'User-Agent: '.$request->userAgent()."\n",
            fn ($m) => $m
                ->to($event->user->email)
                ->subject('[QuickSDR] Production login'),
        );
    }
}
