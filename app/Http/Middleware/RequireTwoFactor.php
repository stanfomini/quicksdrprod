<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * On production only: if the authenticated user has NOT enabled 2FA,
 * log them out and redirect to login with an error. This is a
 * belt-and-suspenders layer on top of Fortify's native 2FA challenge
 * (which only fires if two_factor_secret is set on the user).
 */
class RequireTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment('production') || ! $request->user()) {
            return $next($request);
        }

        if (! $request->user()->hasEnabledTwoFactorAuthentication()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')
                ->withErrors(['email' => 'Two-factor authentication is required.']);
        }

        return $next($request);
    }
}
