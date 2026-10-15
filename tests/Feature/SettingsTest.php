<?php

use App\Http\Controllers\SettingsController;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

const NEW_PASSWORD = ['password' => 'brand-new-secret', 'password_confirmation' => 'brand-new-secret'];

beforeEach(function () {
    // The factory's password is "password".
    $this->user = User::factory()->employee()->inDepartment(Department::IT)->create([
        'name' => 'Asha Rao',
        'email' => 'asharao@fakemail.com',
        'remember_token' => 'old-token',
    ]);
});

test('every signed-in user can open settings', function (string $role) {
    $user = User::factory()->{$role}()->create();

    $this->actingAs($user)
        ->get(route('settings.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('settings/edit'));
})->with(['employee', 'manager', 'admin']);

test('settings shows only the user\'s own account details', function () {
    $this->actingAs($this->user)
        ->get(route('settings.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('account', [
                'name' => 'Asha Rao',
                'email' => 'asharao@fakemail.com',
                'role' => 'employee',
                'department' => 'IT',
            ])
            ->where('passwordConfirmed', false));
});

test('a user without a department sees none', function () {
    $this->actingAs(User::factory()->employee()->create())
        ->get(route('settings.edit'))
        ->assertInertia(fn (Assert $page) => $page->where('account.department', null));
});

test('guests are sent to the login page', function (string $method, string $url) {
    $this->call($method, $url)->assertRedirect(route('login'));
})->with([
    'settings page' => ['GET', '/settings'],
    'confirm password' => ['POST', '/settings/password/confirm'],
    'cancel password change' => ['DELETE', '/settings/password/confirm'],
    'change password' => ['PUT', '/settings/password'],
]);

test('users with a temporary password must change it first', function () {
    $this->user->forceFill(['must_change_password' => true])->save();
    $this->actingAs($this->user);

    $this->get(route('settings.edit'))->assertRedirect(route('password.edit'));
    $this->post(route('settings.password.confirm'), ['current_password' => 'password'])
        ->assertRedirect(route('password.edit'));
    $this->withSession([SettingsController::PASSWORD_CONFIRMED_KEY => now()->unix()])
        ->put(route('settings.password.update'), NEW_PASSWORD)
        ->assertRedirect(route('password.edit'));

    expect(Hash::check('password', $this->user->fresh()->password))->toBeTrue();
});

test('the correct current password unlocks the new password step', function () {
    $this->actingAs($this->user)
        ->from(route('settings.edit'))
        ->post(route('settings.password.confirm'), ['current_password' => 'password'])
        ->assertRedirect(route('settings.edit'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas(SettingsController::PASSWORD_CONFIRMED_KEY);

    $this->get(route('settings.edit'))
        ->assertInertia(fn (Assert $page) => $page->where('passwordConfirmed', true));
});

test('a wrong or missing current password keeps the new password step locked', function (array $data) {
    $this->actingAs($this->user)
        ->post(route('settings.password.confirm'), $data)
        ->assertSessionHasErrors('current_password')
        ->assertSessionMissing(SettingsController::PASSWORD_CONFIRMED_KEY);

    $this->get(route('settings.edit'))
        ->assertInertia(fn (Assert $page) => $page->where('passwordConfirmed', false));
})->with([
    'wrong' => [['current_password' => 'wrong-password']],
    'missing' => [[]],
]);

test('the new password cannot be set without confirming the current one first', function () {
    $this->actingAs($this->user)
        ->put(route('settings.password.update'), NEW_PASSWORD)
        ->assertSessionHasErrors('current_password');

    expect(Hash::check('password', $this->user->fresh()->password))->toBeTrue();
});

test('the confirmation expires after five minutes', function () {
    $this->actingAs($this->user)
        ->post(route('settings.password.confirm'), ['current_password' => 'password']);

    $this->travel(SettingsController::PASSWORD_CONFIRM_SECONDS)->seconds();

    $this->get(route('settings.edit'))
        ->assertInertia(fn (Assert $page) => $page->where('passwordConfirmed', false));
    $this->put(route('settings.password.update'), NEW_PASSWORD)
        ->assertSessionHasErrors('current_password');

    expect(Hash::check('password', $this->user->fresh()->password))->toBeTrue();
});

test('cancelling at step 2 locks it again until the current password is confirmed', function () {
    $this->actingAs($this->user)
        ->post(route('settings.password.confirm'), ['current_password' => 'password']);

    $this->from(route('settings.edit'))
        ->delete(route('settings.password.cancel'))
        ->assertRedirect(route('settings.edit'))
        ->assertSessionMissing(SettingsController::PASSWORD_CONFIRMED_KEY);

    $this->get(route('settings.edit'))
        ->assertInertia(fn (Assert $page) => $page->where('passwordConfirmed', false));
    $this->put(route('settings.password.update'), NEW_PASSWORD)
        ->assertSessionHasErrors('current_password');

    expect(Hash::check('password', $this->user->fresh()->password))->toBeTrue();
});

test('opening another page locks step 2 again', function (string $route) {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('settings.password.confirm'), ['current_password' => 'password']);

    $this->get(route($route))->assertSessionMissing(SettingsController::PASSWORD_CONFIRMED_KEY);

    $this->get(route('settings.edit'))
        ->assertInertia(fn (Assert $page) => $page->where('passwordConfirmed', false));
    $this->put(route('settings.password.update'), NEW_PASSWORD)
        ->assertSessionHasErrors('current_password');
})->with(['dashboard', 'users.index', 'departments.index', 'bin.index']);

test('staying on settings keeps step 2 open', function () {
    $this->actingAs($this->user)
        ->post(route('settings.password.confirm'), ['current_password' => 'password']);

    // A refresh, or the redirect back after a validation error.
    $this->get(route('settings.edit'))
        ->assertInertia(fn (Assert $page) => $page->where('passwordConfirmed', true));
    $this->from(route('settings.edit'))
        ->put(route('settings.password.update'), ['password' => 'short', 'password_confirmation' => 'short'])
        ->assertSessionHasErrors('password');
    $this->get(route('settings.edit'))
        ->assertInertia(fn (Assert $page) => $page->where('passwordConfirmed', true));
});

test('prefetching another page does not lock step 2', function () {
    $this->actingAs($this->user)
        ->post(route('settings.password.confirm'), ['current_password' => 'password']);

    $this->get(route('dashboard'), ['Purpose' => 'prefetch'])
        ->assertSessionHas(SettingsController::PASSWORD_CONFIRMED_KEY);
});

test('users can change their own password after confirming the current one', function () {
    $this->actingAs($this->user)
        ->post(route('settings.password.confirm'), ['current_password' => 'password']);

    $this->from(route('settings.edit'))
        ->put(route('settings.password.update'), NEW_PASSWORD)
        ->assertRedirect(route('settings.edit'))
        ->assertInertiaFlash('status', 'Your password has been changed.')
        // The next change has to start from step 1 again.
        ->assertSessionMissing(SettingsController::PASSWORD_CONFIRMED_KEY);

    $user = $this->user->fresh();
    expect(Hash::check('brand-new-secret', $user->password))->toBeTrue()
        ->and($user->remember_token)->not->toBe('old-token');
    $this->assertAuthenticatedAs($user);
});

test('a client-supplied user id cannot change someone else\'s password', function () {
    $other = User::factory()->employee()->create();

    $this->actingAs($this->user)
        ->withSession([SettingsController::PASSWORD_CONFIRMED_KEY => now()->unix()])
        ->put(route('settings.password.update'), [...NEW_PASSWORD, 'user_id' => $other->id, 'id' => $other->id]);

    expect(Hash::check('password', $other->fresh()->password))->toBeTrue()
        ->and(Hash::check('brand-new-secret', $this->user->fresh()->password))->toBeTrue();
});

test('the new password is validated', function (array $data) {
    $this->actingAs($this->user)
        ->withSession([SettingsController::PASSWORD_CONFIRMED_KEY => now()->unix()])
        ->put(route('settings.password.update'), $data)
        ->assertSessionHasErrors('password');

    expect(Hash::check('password', $this->user->fresh()->password))->toBeTrue();
})->with([
    'missing' => [[]],
    'too short' => [['password' => 'short', 'password_confirmation' => 'short']],
    'mismatched confirmation' => [['password' => 'brand-new-secret', 'password_confirmation' => 'other-secret-123']],
    'same as current' => [['password' => 'password', 'password_confirmation' => 'password']],
]);

test('both steps are rate limited', function (string $method, string $route, array $data) {
    $this->actingAs($this->user);

    foreach (range(1, 6) as $attempt) {
        $this->call($method, route($route), $data);
    }

    $this->call($method, route($route), $data)->assertTooManyRequests();
})->with([
    'confirm' => ['POST', 'settings.password.confirm', ['current_password' => 'wrong-password']],
    'update' => ['PUT', 'settings.password.update', NEW_PASSWORD],
]);
