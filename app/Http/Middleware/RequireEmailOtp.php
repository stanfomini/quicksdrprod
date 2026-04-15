<?php

namespace App\Http\Middleware;

use Closure;
use DateTimeInterface;
use Illuminate\Http\Request;
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

    /**
     * Public-facing routes that must remain reachable even for logged-in
     * users without OTP — marketing pages, signup funnel, contact forms,
     * webhooks, OTP / logout flow itself, and Livewire's internal endpoints.
     *
     * @var array<int, string>
     */
    private const PUBLIC_PATTERNS = [
        '/',
        'about',
        'contact',
        'contact-sales',
        'signup',
        'signup/*',
        'admin-signup/*',
        'stripe/webhook',
        'up',
        'login',
        'logout',
        'register',
        'forgot-password',
        'reset-password/*',
        'email/verify',
        'email/verify/*',
        'verify-email',
        'email-code',
        'email-code/*',
        'livewire/*',
        // Livewire 4 randomizes the update endpoint prefix per build
        // (e.g. /livewire-5b92011c/update) to defeat naive scanners. Match
        // any livewire-*/update or livewire-*/livewire.js too.
        'livewire-*',
    ];

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

        // Marketing + signup + auth-flow routes: always allowed, even when
        // the user is logged in. OTP only gates post-login app routes.
        if ($request->is(...self::PUBLIC_PATTERNS)) {
            return $next($request);
        }

        $verifiedAt = $request->session()->get('email_otp_verified_at');

        // Stored as unix int by EmailOtp::verify(). Older sessions may still
        // hold a Date instance from before that change — accept both.
        $verifiedTs = match (true) {
            is_int($verifiedAt) => $verifiedAt,
            $verifiedAt instanceof DateTimeInterface => $verifiedAt->getTimestamp(),
            default => null,
        };

        if ($verifiedTs !== null && (time() - $verifiedTs) < self::TTL_SECONDS) {
            return $next($request);
        }

        $request->session()->put('email_otp_intended', $request->fullUrl());

        return redirect('/email-code');
    }
}
