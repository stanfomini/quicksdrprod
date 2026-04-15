<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * On production: require the authenticated user to have completed an
 * email-based OTP step within the last 24 hours. If not, redirect to
 * /email-code to receive and enter a 6-digit code.
 *
 * Non-prod environments are unaffected (dev box, local testing).
 *
 * Paired with App\Livewire\EmailOtp which handles the code generation,
 * mail, and verification.
 */
class RequireEmailOtp
{
    /**
     * How long an OTP verification stays valid for the session.
     */
    private const TTL_SECONDS = 86400; // 24h

    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment('production') || ! $request->user()) {
            return $next($request);
        }

        // Opt-in via env: deploy with AUTH_EMAIL_OTP_ENABLED=false,
        // verify login works + Mailgun delivers, then flip to true.
        if (! config('auth.email_otp_enabled', false)) {
            return $next($request);
        }

        // Allow the OTP page itself + logout + Livewire internal requests
        // through so the flow can complete.
        if ($request->is('email-code', 'email-code/*', 'logout', 'livewire/*')) {
            return $next($request);
        }

        $verifiedAt = $request->session()->get('email_otp_verified_at');

        if ($verifiedAt instanceof Carbon && now()->diffInSeconds($verifiedAt) < self::TTL_SECONDS) {
            return $next($request);
        }

        $request->session()->put('email_otp_intended', $request->fullUrl());

        return redirect('/email-code');
    }
}
