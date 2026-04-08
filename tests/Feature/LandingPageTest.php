<?php

test('the home route resolves to /', function () {
    expect(route('home', absolute: false))->toBe('/');
});

test('the landing page returns 200', function () {
    $this->get(route('home'))->assertOk();
});

test('the landing page renders the marketing headline', function () {
    $this->get(route('home'))
        ->assertSeeText('Dial faster.')
        ->assertSeeText('Close more.');
});

test('the landing page embeds a login form posting to login.store', function () {
    $this->get(route('home'))
        ->assertSee('action="'.route('login.store').'"', false)
        ->assertSee('method="POST"', false)
        ->assertSee('name="email"', false)
        ->assertSee('name="password"', false);
});
