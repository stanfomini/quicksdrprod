<?php

use App\Models\AdminSignupToken;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

test('the artisan command outputs a valid signup URL and persists the token', function () {
    $exit = Artisan::call('claimstaker:generate-admin-signup', [
        '--email' => 'client@example.com',
        '--hours' => 24,
    ]);
    $output = trim(Artisan::output());

    expect($exit)->toBe(0);
    expect($output)->toMatch('#^https?://.+/admin-signup/[a-f0-9]{64}$#');

    $token = AdminSignupToken::query()->latest('id')->first();
    expect($token)->not->toBeNull();
    expect($token->email_hint)->toBe('client@example.com');
    expect($output)->toContain($token->token);
    expect($token->expires_at->diffInHours(now(), true))->toBeLessThan(25);
});

test('GET /admin-signup with an unknown token returns 410', function () {
    $this->get(route('admin-signup.show', 'totally-not-real'))->assertStatus(410);
});

test('GET /admin-signup with an expired token returns 410', function () {
    $token = AdminSignupToken::generate(emailHint: null, expiresInHours: 1);
    $token->forceFill(['expires_at' => now()->subHour()])->save();

    $this->get(route('admin-signup.show', $token->token))->assertStatus(410);
});

test('GET /admin-signup with an already-used token returns 410', function () {
    $token = AdminSignupToken::generate();
    $token->forceFill(['used_at' => now()])->save();

    $this->get(route('admin-signup.show', $token->token))->assertStatus(410);
});

test('GET /admin-signup with a valid token renders the form', function () {
    $token = AdminSignupToken::generate(emailHint: 'jane@acme.com');

    $this->get(route('admin-signup.show', $token->token))
        ->assertOk()
        ->assertSee('Welcome — set up your admin account', false)
        ->assertSee('jane@acme.com');
});

test('POST /admin-signup with valid data creates an internal user, claims the token, logs in, redirects', function () {
    $token = AdminSignupToken::generate();

    $response = $this->post(route('admin-signup.store', $token->token), [
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'password' => 'CorrectHorseBatteryStaple1!',
        'password_confirmation' => 'CorrectHorseBatteryStaple1!',
    ]);

    $response->assertRedirect(config('fortify.home'));
    $this->assertAuthenticated();

    $user = User::where('email', 'jane@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->type)->toBe('internal');
    expect($user->isInternal())->toBeTrue();

    expect($token->fresh()->used_at)->not->toBeNull();
});

test('POST /admin-signup with mismatched password confirmation returns validation error', function () {
    $token = AdminSignupToken::generate();

    $this->post(route('admin-signup.store', $token->token), [
        'name' => 'Jane Smith',
        'email' => 'jane@example.com',
        'password' => 'one-password',
        'password_confirmation' => 'a-different-password',
    ])->assertSessionHasErrors('password');

    expect(User::where('email', 'jane@example.com')->exists())->toBeFalse();
    expect($token->fresh()->used_at)->toBeNull();
});

test('the same token cannot be claimed twice (race-safety contract)', function () {
    $token = AdminSignupToken::generate();

    $first = AdminSignupToken::claim($token->token);
    $second = AdminSignupToken::claim($token->token);

    expect($first)->toBeTrue();
    expect($second)->toBeFalse();
    expect($token->fresh()->used_at)->not->toBeNull();
});

test('claiming an expired token returns false', function () {
    $token = AdminSignupToken::generate(expiresInHours: 1);
    $token->forceFill(['expires_at' => now()->subHour()])->save();

    expect(AdminSignupToken::claim($token->token))->toBeFalse();
});
