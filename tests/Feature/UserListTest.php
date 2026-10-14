<?php

use App\Models\User;
use Illuminate\Support\Arr;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot see the user list', function () {
    $this->get(route('users.index'))->assertRedirect(route('login'));
});

test('every role can see the user list', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->create())
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('users/index'));
})->with(['requester', 'agent', 'admin']);

test('each row has only the name, email, role and joined date', function () {
    $agent = User::factory()->agent()->create([
        'name' => 'Asha Rao',
        'email' => 'asha@example.com',
        'created_at' => '2026-10-01 09:30:00',
    ]);

    $this->actingAs($agent)
        ->get(route('users.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0', [
                'id' => $agent->id,
                'name' => 'Asha Rao',
                'email' => 'asha@example.com',
                'role' => 'agent',
                'joined' => '2026-10-01',
            ])
        );
});

test('the user list does not expose sensitive fields', function () {
    $user = User::factory()->create();

    $keys = array_keys(Arr::dot(
        $this->actingAs($user)->get(route('users.index'))->viewData('page')['props']['users']
    ));

    foreach (['password', 'remember_token', 'must_change_password', 'role_id'] as $sensitive) {
        expect($keys)->each->not->toContain($sensitive);
    }
});

test('users are sorted by name and paginated 15 per page', function () {
    $viewer = User::factory()->create(['name' => 'Aaron First']);
    User::factory()->count(16)->sequence(fn ($sequence) => ['name' => sprintf('User %02d', $sequence->index)])->create();

    $this->actingAs($viewer)
        ->get(route('users.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 15)
            ->where('users.data.0.name', 'Aaron First')
            ->where('users.data.14.name', 'User 13')
            ->where('users.total', 17)
            ->where('users.last_page', 2)
            ->where('users.prev_page_url', null)
        );

    $this->actingAs($viewer)
        ->get(route('users.index', ['page' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 2)
            ->where('users.data.0.name', 'User 14')
            ->where('users.next_page_url', null)
        );
});

test('users with a temporary password are sent to change it first', function () {
    $this->actingAs(User::factory()->create(['must_change_password' => true]))
        ->get(route('users.index'))
        ->assertRedirect(route('password.edit'));
});
