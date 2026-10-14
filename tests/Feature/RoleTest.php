<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;

test('the requester, agent and admin roles exist', function () {
    expect(Role::orderBy('id')->pluck('name')->all())
        ->toBe([Role::REQUESTER, Role::AGENT, Role::ADMIN]);
});

test('role names are unique', function () {
    Role::create(['name' => Role::AGENT]);
})->throws(QueryException::class);

test('users created from the form are requesters', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('users.store'), ['name' => 'Asha Rao']);

    expect(User::where('email', 'asharao@gmail.com')->sole()->role->name)->toBe(Role::REQUESTER);
});

test('a role sent by the client is ignored', function () {
    $adminId = Role::where('name', Role::ADMIN)->value('id');

    $this->actingAs(User::factory()->create())
        ->post(route('users.store'), [
            'name' => 'Asha Rao',
            'role_id' => $adminId,
            'role' => Role::ADMIN,
        ]);

    expect(User::where('email', 'asharao@gmail.com')->sole()->role->name)->toBe(Role::REQUESTER);
});

test('the role cannot be mass assigned', function () {
    $user = new User(['name' => 'Asha Rao', 'role_id' => Role::where('name', Role::ADMIN)->value('id')]);

    expect($user->role_id)->toBeNull();
});

test('users belong to a role and roles have users', function () {
    $requester = User::factory()->create();
    $agent = User::factory()->agent()->create();
    $admin = User::factory()->admin()->create();

    expect($requester->role->name)->toBe(Role::REQUESTER)
        ->and($agent->role->name)->toBe(Role::AGENT)
        ->and($admin->role->name)->toBe(Role::ADMIN)
        ->and(Role::where('name', Role::AGENT)->sole()->users->modelKeys())->toBe([$agent->id]);
});

test('a role still in use cannot be deleted', function () {
    $agent = User::factory()->agent()->create();

    $agent->role->delete();
})->throws(QueryException::class);
