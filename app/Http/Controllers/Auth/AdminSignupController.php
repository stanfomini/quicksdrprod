<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminSignupToken;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminSignupController extends Controller
{
    /**
     * Render the signup form for a one-time admin signup token.
     */
    public function show(string $token): View
    {
        $row = AdminSignupToken::valid()
            ->where('token', $token)
            ->firstOr(fn () => abort(410, 'This signup link is invalid, expired, or already used.'));

        return view('admin-signup', [
            'token' => $row,
            'emailHint' => $row->email_hint,
        ]);
    }

    /**
     * Process the signup form: atomically claim the token, create the
     * internal admin user, log them in, redirect to the dashboard.
     */
    public function store(Request $request, string $token): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        if (! AdminSignupToken::claim($token)) {
            abort(410, 'This signup link is invalid, expired, or already used.');
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);
        $user->forceFill(['type' => 'internal'])->save();

        Auth::login($user);

        return redirect()->intended(config('fortify.home'));
    }
}
