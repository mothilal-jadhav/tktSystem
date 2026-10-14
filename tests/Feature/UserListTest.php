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
})->with(['employee', 'manager', 'admin']);

test('each row has only the name, email, role, joined date and permissions', function () {
    $manager = User::factory()->manager()->create([
        'name' => 'Asha Rao',
        'email' => 'asha@example.com',
        'created_at' => '2026-10-01 09:30:00',
    ]);

    $this->actingAs($manager)
        ->get(route('users.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0', [
                'id' => $manager->id,
                'name' => 'Asha Rao',
                'email' => 'asha@example.com',
                'role' => 'manager',
                'joined' => '2026-10-01',
                'can' => ['update' => false, 'delete' => false],
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

test('only admins get the manage users view', function (string $role, bool $canManage) {
    $this->actingAs(User::factory()->{$role}()->create())
        ->get(route('users.index'))
        ->assertInertia(fn (Assert $page) => $page->where('canManageUsers', $canManage));
})->with([
    'employee' => ['employee', false],
    'manager' => ['manager', false],
    'admin' => ['admin', true],
]);

test('admins may edit and delete employees and managers but not admins', function () {
    $admin = User::factory()->admin()->create(['name' => 'A Admin']);
    User::factory()->admin()->create(['name' => 'B Admin']);
    User::factory()->manager()->create(['name' => 'C Manager']);
    User::factory()->employee()->create(['name' => 'D Employee']);

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('users.data.0.can', ['update' => false, 'delete' => false]) // themselves
            ->where('users.data.1.can', ['update' => false, 'delete' => false]) // another admin
            ->where('users.data.2.can', ['update' => true, 'delete' => true])
            ->where('users.data.3.can', ['update' => true, 'delete' => true])
        );
});

test('non-admins may not edit or delete anyone', function (string $role) {
    $viewer = User::factory()->{$role}()->create();
    User::factory()->employee()->create();
    User::factory()->manager()->create();
    User::factory()->admin()->create();

    $rows = $this->actingAs($viewer)->get(route('users.index'))->viewData('page')['props']['users']['data'];

    foreach ($rows as $row) {
        expect($row['can'])->toBe(['update' => false, 'delete' => false]);
    }
})->with(['employee', 'manager']);

test('the user policy only lets admins edit or delete non-admins', function (string $viewerRole, string $targetRole, bool $allowed) {
    $viewer = User::factory()->{$viewerRole}()->create();
    $target = User::factory()->{$targetRole}()->create();

    expect($viewer->can('update', $target))->toBe($allowed)
        ->and($viewer->can('delete', $target))->toBe($allowed);
})->with([
    'admin → employee' => ['admin', 'employee', true],
    'admin → manager' => ['admin', 'manager', true],
    'admin → admin' => ['admin', 'admin', false],
    'manager → employee' => ['manager', 'employee', false],
    'manager → manager' => ['manager', 'manager', false],
    'employee → employee' => ['employee', 'employee', false],
    'employee → manager' => ['employee', 'manager', false],
]);

test('admins cannot edit or delete themselves', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('update', $admin))->toBeFalse()
        ->and($admin->can('delete', $admin))->toBeFalse();
});
