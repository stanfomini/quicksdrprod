<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class NotifyOwnerOnLogin
{
    public function handle(Login $event): void
    {
        // Clear any stale OTP code + verify rate-limits from a previous
        // session so a fresh login always issues a fresh email. Also drop
        // any leftover email_otp_intended (could be a Livewire POST URL
        // from before the middleware whitelist fix — would 405 on GET).
        Cache::forget('email-otp:'.$event->user->id);
        RateLimiter::clear('email-otp-verify:'.$event->user->id);
        RateLimiter::clear('email-otp-resend:'.$event->user->id);
        session()->forget(['email_otp_intended', 'email_otp_verified_at']);

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
