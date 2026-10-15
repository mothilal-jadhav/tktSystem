<?php

use App\Models\Role;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->target = User::factory()->employee()->create([
        'name' => 'Asha Rao',
        'email' => 'asharao@fakemail.com',
    ]);
});

test('admins see the edit form with the user and role options', function () {
    $this->actingAs($this->admin)
        ->get(route('users.edit', $this->target))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('users/edit')
            ->where('user', [
                'id' => $this->target->id,
                'name' => 'Asha Rao',
                'email' => 'asharao@fakemail.com',
                'role' => 'employee',
                'department' => null,
            ])
            ->where('roles', ['employee', 'manager', 'admin'])
            ->where('departments', ['Claims', 'Endorsement', 'HR', 'IT', 'Sales', 'Wellness'])
        );
});

test('admins can change a user\'s role', function (string $role) {
    $this->actingAs($this->admin)
        ->put(route('users.update', $this->target), ['role' => $role, 'department' => 'IT'])
        ->assertRedirect(route('users.index'));

    expect($this->target->fresh()->role->name)->toBe($role);
})->with(['manager', 'admin', 'employee']);

test('changing the role shows a confirmation message', function () {
    $this->actingAs($this->admin)->put(route('users.update', $this->target), ['role' => 'manager', 'department' => 'IT']);

    $flash = $this->actingAs($this->admin)->get(route('users.index'))->viewData('page')['flash'];

    expect($flash['status'])->toBe('Asha Rao is now manager in IT.');
});

test('the name and email cannot be changed', function () {
    $this->actingAs($this->admin)->put(route('users.update', $this->target), [
        'role' => 'manager',
        'department' => 'IT',
        'name' => 'Someone Else',
        'email' => 'other@example.com',
        'role_id' => Role::where('name', Role::ADMIN)->value('id'),
        'must_change_password' => true,
    ]);

    $user = $this->target->fresh();
    expect($user->name)->toBe('Asha Rao')
        ->and($user->email)->toBe('asharao@fakemail.com')
        ->and($user->role->name)->toBe('manager')
        ->and($user->must_change_password)->toBeFalse();
});

test('an invalid role is rejected', function (array $data) {
    $this->actingAs($this->admin)
        ->put(route('users.update', $this->target), ['department' => 'IT', ...$data])
        ->assertSessionHasErrors('role');

    expect($this->target->fresh()->role->name)->toBe('employee');
})->with([
    'missing' => [[]],
    'empty' => [['role' => '']],
    'unknown' => [['role' => 'superuser']],
    'old requester role' => [['role' => 'requester']],
]);

test('admins cannot edit another admin or themselves', function () {
    $otherAdmin = User::factory()->admin()->create();

    foreach ([$otherAdmin, $this->admin] as $target) {
        $this->actingAs($this->admin)->get(route('users.edit', $target))->assertForbidden();
        $this->actingAs($this->admin)
            ->put(route('users.update', $target), ['role' => 'employee', 'department' => 'IT'])
            ->assertForbidden();

        expect($target->fresh()->role->name)->toBe('admin');
    }
});

test('non-admins cannot edit users', function (string $role) {
    $viewer = User::factory()->{$role}()->create();

    $this->actingAs($viewer)->get(route('users.edit', $this->target))->assertForbidden();
    $this->actingAs($viewer)
        ->put(route('users.update', $this->target), ['role' => 'admin', 'department' => 'IT'])
        ->assertForbidden();

    expect($this->target->fresh()->role->name)->toBe('employee');
})->with(['employee', 'manager']);

test('guests cannot edit users', function () {
    $this->get(route('users.edit', $this->target))->assertRedirect(route('login'));
    $this->put(route('users.update', $this->target), ['role' => 'admin', 'department' => 'IT'])->assertRedirect(route('login'));

    expect($this->target->fresh()->role->name)->toBe('employee');
});

test('users in the bin cannot be edited', function () {
    $this->target->delete();

    $this->actingAs($this->admin)->get(route('users.edit', $this->target))->assertNotFound();
    $this->actingAs($this->admin)
        ->put(route('users.update', $this->target), ['role' => 'manager', 'department' => 'IT'])
        ->assertNotFound();
});
