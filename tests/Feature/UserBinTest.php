<?php

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

// --- Deleting (moving to the bin) ---

test('admins can move employees and managers to the bin', function (string $role) {
    $target = User::factory()->{$role}()->create();

    $this->actingAs($this->admin)
        ->from(route('users.index'))
        ->delete(route('users.destroy', $target))
        ->assertRedirect(route('users.index'));

    expect($target->fresh()->trashed())->toBeTrue();
    $this->assertSoftDeleted($target);
})->with(['employee', 'manager']);

test('a deleted user disappears from the users list', function () {
    $target = User::factory()->create(['name' => 'Asha Rao']);

    $this->actingAs($this->admin)->delete(route('users.destroy', $target));

    $this->actingAs($this->admin)
        ->get(route('users.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('users.data', fn ($rows) => collect($rows)->pluck('name')->doesntContain('Asha Rao'))
        );
});

test('deleting shows a confirmation message', function () {
    $target = User::factory()->create(['name' => 'Asha Rao']);

    $this->actingAs($this->admin)->delete(route('users.destroy', $target));

    $flash = $this->actingAs($this->admin)->get(route('users.index'))->viewData('page')['flash'];

    expect($flash['status'])->toBe('Asha Rao was moved to the bin.');
});

test('admins cannot delete another admin or themselves', function () {
    $otherAdmin = User::factory()->admin()->create();

    $this->actingAs($this->admin)->delete(route('users.destroy', $otherAdmin))->assertForbidden();
    $this->actingAs($this->admin)->delete(route('users.destroy', $this->admin))->assertForbidden();

    expect($otherAdmin->fresh()->trashed())->toBeFalse()
        ->and($this->admin->fresh()->trashed())->toBeFalse();
});

test('non-admins cannot delete anyone', function (string $role) {
    $target = User::factory()->employee()->create();

    $this->actingAs(User::factory()->{$role}()->create())
        ->delete(route('users.destroy', $target))
        ->assertForbidden();

    expect($target->fresh()->trashed())->toBeFalse();
})->with(['employee', 'manager']);

test('guests cannot delete users', function () {
    $target = User::factory()->create();

    $this->delete(route('users.destroy', $target))->assertRedirect(route('login'));

    expect($target->fresh()->trashed())->toBeFalse();
});

test('a deleted user cannot sign in', function () {
    $target = User::factory()->create(['email' => 'asha@example.com']);
    $target->delete();

    $this->post(route('login.store'), ['email' => 'asha@example.com', 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('a deleted user who is already signed in is signed out', function () {
    $target = User::factory()->create(['email' => 'asha@example.com']);
    $this->post(route('login.store'), ['email' => 'asha@example.com', 'password' => 'password']);

    $target->delete();
    Auth::forgetGuards(); // Simulate the next request resolving the user from the session again.

    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('a new user does not reuse the email of a user in the bin', function () {
    $this->actingAs($this->admin)->post(route('users.store'), ['name' => 'John Doe', 'role' => 'employee']);
    User::where('email', 'johndoe@fakemail.com')->sole()->delete();

    $this->actingAs($this->admin)->post(route('users.store'), ['name' => 'John Doe', 'role' => 'employee']);

    expect(User::where('email', 'johndoe1@fakemail.com')->exists())->toBeTrue();
});

// --- The bin ---

test('only admins see the bin', function (string $role, bool $allowed) {
    $response = $this->actingAs(User::factory()->{$role}()->create())->get(route('bin.index'));

    $allowed
        ? $response->assertOk()->assertInertia(fn (Assert $page) => $page->component('bin/index'))
        : $response->assertForbidden();
})->with([
    'employee' => ['employee', false],
    'manager' => ['manager', false],
    'admin' => ['admin', true],
]);

test('only admins are told they can open the bin', function (string $role, bool $can) {
    $this->actingAs(User::factory()->{$role}()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('auth.can.viewBin', $can));
})->with([
    'employee' => ['employee', false],
    'manager' => ['manager', false],
    'admin' => ['admin', true],
]);

test('guests cannot see the bin', function () {
    $this->get(route('bin.index'))->assertRedirect(route('login'));
});

test('the bin lists only deleted users with safe fields', function () {
    User::factory()->create(['name' => 'Still Here']);
    $deleted = User::factory()->manager()->create(['name' => 'Asha Rao', 'email' => 'asha@example.com']);
    $deleted->delete();

    $response = $this->actingAs($this->admin)->get(route('bin.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('users.data', 1)
        ->where('users.data.0', [
            'id' => $deleted->id,
            'name' => 'Asha Rao',
            'email' => 'asha@example.com',
            'role' => 'manager',
            'deleted' => $deleted->fresh()->deleted_at->toDateString(),
            'can' => ['restore' => true, 'forceDelete' => true],
        ])
    );

    $keys = array_keys(Arr::dot($response->viewData('page')['props']['users']));
    foreach (['password', 'remember_token', 'must_change_password', 'role_id'] as $sensitive) {
        expect($keys)->each->not->toContain($sensitive);
    }
});

// --- Restoring ---

test('admins can restore a user from the bin', function () {
    $target = User::factory()->create(['email' => 'asha@example.com']);
    $target->delete();

    $this->actingAs($this->admin)
        ->from(route('bin.index'))
        ->patch(route('bin.restore', $target))
        ->assertRedirect(route('bin.index'));

    expect($target->fresh()->trashed())->toBeFalse();

    // They can sign in again.
    Auth::logout();
    $this->post(route('login.store'), ['email' => 'asha@example.com', 'password' => 'password']);
    $this->assertAuthenticatedAs($target->fresh());
});

test('non-admins cannot restore users', function (string $role) {
    $target = User::factory()->create();
    $target->delete();

    $this->actingAs(User::factory()->{$role}()->create())
        ->patch(route('bin.restore', $target))
        ->assertForbidden();

    expect($target->fresh()->trashed())->toBeTrue();
})->with(['employee', 'manager']);

test('a user who is not in the bin cannot be restored', function () {
    $target = User::factory()->create();

    $this->actingAs($this->admin)->patch(route('bin.restore', $target))->assertForbidden();
});

// --- Deleting forever ---

test('admins can permanently delete a user from the bin', function () {
    $target = User::factory()->create();
    $target->delete();

    $this->actingAs($this->admin)
        ->from(route('bin.index'))
        ->delete(route('bin.destroy', $target))
        ->assertRedirect(route('bin.index'));

    $this->assertModelMissing($target);
});

test('non-admins cannot permanently delete users', function (string $role) {
    $target = User::factory()->create();
    $target->delete();

    $this->actingAs(User::factory()->{$role}()->create())
        ->delete(route('bin.destroy', $target))
        ->assertForbidden();

    $this->assertSoftDeleted($target);
})->with(['employee', 'manager']);

test('a user who is not in the bin cannot be permanently deleted', function () {
    $target = User::factory()->create();

    $this->actingAs($this->admin)->delete(route('bin.destroy', $target))->assertForbidden();

    $this->assertNotSoftDeleted($target);
});
