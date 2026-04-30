<?php

use App\Livewire\Admin\Leads\Create;
use App\Livewire\Admin\Leads\Edit;
use App\Models\Lead;
use App\Models\User;

beforeEach(function () {
    config(['app.mode' => 'orchestrator']);

    $this->user = User::factory()->create(['type' => 'internal']);
    $this->actingAs($this->user);
});

test('the leads index renders for an internal user', function () {
    Lead::factory()->count(3)->create();

    $this->get(route('admin.leads.index'))
        ->assertOk()
        ->assertSee('Leads')
        ->assertSee('Search');
});

test('a non-internal user cannot access leads', function () {
    $this->actingAs(User::factory()->create(['type' => 'tenant']));

    $this->get(route('admin.leads.index'))->assertForbidden();
});

test('the leads index supports searching by email', function () {
    Lead::factory()->create(['email' => 'alice@example.com', 'name' => 'Alice']);
    Lead::factory()->create(['email' => 'bob@example.com', 'name' => 'Bob']);

    $this->get(route('admin.leads.index', ['q' => 'alice']))
        ->assertOk()
        ->assertSee('alice@example.com')
        ->assertDontSee('bob@example.com');
});

test('the lead show page loads', function () {
    $lead = Lead::factory()->create(['name' => 'Show Me', 'email' => 'show@example.com']);

    $this->get(route('admin.leads.show', $lead))
        ->assertOk()
        ->assertSee('Show Me')
        ->assertSee('show@example.com');
});

test('the create page persists a new lead', function () {
    Livewire\Livewire::test(Create::class)
        ->set('name', 'New Lead')
        ->set('email', 'new@example.com')
        ->set('phone', '8175550123')
        ->set('company', 'Acme')
        ->call('save');

    $lead = Lead::first();
    expect($lead)->not->toBeNull();
    expect($lead->name)->toBe('New Lead');
    expect($lead->email)->toBe('new@example.com');
    expect($lead->phone)->toBe('+18175550123');
});

test('create requires email or phone', function () {
    Livewire\Livewire::test(Create::class)
        ->set('name', 'No Contact Info')
        ->call('save')
        ->assertHasErrors(['email']);

    expect(Lead::count())->toBe(0);
});

test('the edit page updates a lead', function () {
    $lead = Lead::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);

    Livewire\Livewire::test(Edit::class, ['lead' => $lead])
        ->set('name', 'New Name')
        ->call('save');

    expect($lead->fresh()->name)->toBe('New Name');
});
