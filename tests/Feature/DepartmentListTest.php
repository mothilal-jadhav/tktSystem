<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot see the department list', function () {
    $this->get(route('departments.index'))->assertRedirect(route('login'));
});

test('every role can see all departments sorted by name', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->create())
        ->get(route('departments.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('departments/index')
            ->where('departments', fn ($rows) => collect($rows)->pluck('name')->all() === [
                'Claims',
                'Endorsement',
                'HR',
                'IT',
                'Sales',
                'Wellness',
            ])
        );
})->with(['employee', 'manager', 'admin']);

test('each department has only an id and a name', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('departments.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('departments', 6)
            ->has('departments.0', fn (Assert $row) => $row
                ->whereType('id', 'integer')
                ->where('name', 'Claims')
            )
        );
});

test('users with a temporary password are sent to change it first', function () {
    $this->actingAs(User::factory()->create(['must_change_password' => true]))
        ->get(route('departments.index'))
        ->assertRedirect(route('password.edit'));
});
