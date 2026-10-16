<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot see the tickets page', function () {
    $this->get(route('tickets.index'))->assertRedirect(route('login'));
});

test('every role can see the tickets page', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->create())
        ->get(route('tickets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('tickets/index'));
})->with(['employee', 'manager', 'admin']);

test('users with a temporary password are sent to change it first', function () {
    $this->actingAs(User::factory()->create(['must_change_password' => true]))
        ->get(route('tickets.index'))
        ->assertRedirect(route('password.edit'));
});
