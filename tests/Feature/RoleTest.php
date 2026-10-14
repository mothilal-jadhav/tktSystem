<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;

test('the employee, manager and admin roles exist', function () {
    expect(Role::orderBy('id')->pluck('name')->all())
        ->toBe([Role::EMPLOYEE, Role::MANAGER, Role::ADMIN]);
});

test('role names are unique', function () {
    Role::create(['name' => Role::MANAGER]);
})->throws(QueryException::class);

test('the old requester and agent roles no longer exist', function () {
    expect(Role::whereIn('name', ['requester', 'agent'])->exists())->toBeFalse();
});

test('a raw role_id sent by the client is ignored', function () {
    $adminId = Role::where('name', Role::ADMIN)->value('id');

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('users.store'), [
            'name' => 'Asha Rao',
            'role' => Role::EMPLOYEE,
            'role_id' => $adminId,
        ]);

    expect(User::where('email', 'asharao@fakemail.com')->sole()->role->name)->toBe(Role::EMPLOYEE);
});

test('the role cannot be mass assigned', function () {
    $user = new User(['name' => 'Asha Rao', 'role_id' => Role::where('name', Role::ADMIN)->value('id')]);

    expect($user->role_id)->toBeNull();
});

test('users belong to a role and roles have users', function () {
    $employee = User::factory()->create();
    $manager = User::factory()->manager()->create();
    $admin = User::factory()->admin()->create();

    expect($employee->role->name)->toBe(Role::EMPLOYEE)
        ->and($manager->role->name)->toBe(Role::MANAGER)
        ->and($admin->role->name)->toBe(Role::ADMIN)
        ->and(Role::where('name', Role::MANAGER)->sole()->users->modelKeys())->toBe([$manager->id]);
});

test('a role still in use cannot be deleted', function () {
    $manager = User::factory()->manager()->create();

    $manager->role->delete();
})->throws(QueryException::class);
