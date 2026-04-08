<?php

use Illuminate\Support\Facades\Route;

test('public registration is disabled', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();
});

test('the register named route does not exist', function () {
    expect(Route::has('register'))->toBeFalse();
});
