<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // The factory's password is "password".
    $this->user = User::factory()->create([
        'email' => 'asha@example.com',
        'must_change_password' => true,
    ]);
});

test('users with a temporary password are sent to change it', function (string $route) {
    $this->actingAs($this->user)
        ->get(route($route))
        ->assertRedirect(route('password.edit'));
})->with(['dashboard', 'users.create']);

test('signing in with a temporary password leads to the change password page', function () {
    $this->post(route('login.store'), ['email' => 'asha@example.com', 'password' => 'password'])
        ->assertRedirect(route('dashboard'));

    $this->get(route('dashboard'))->assertRedirect(route('password.edit'));

    $this->get(route('password.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/change-password'));
});

test('users with a temporary password can still sign out', function () {
    $this->actingAs($this->user)->post(route('logout'))->assertRedirect(route('login'));

    $this->assertGuest();
});

test('the new password is validated', function (array $data) {
    $this->actingAs($this->user)
        ->put(route('password.update'), $data)
        ->assertSessionHasErrors('password');

    expect($this->user->fresh()->must_change_password)->toBeTrue();
})->with([
    'missing' => [[]],
    'too short' => [['password' => 'short', 'password_confirmation' => 'short']],
    'mismatched confirmation' => [['password' => 'new-secret-123', 'password_confirmation' => 'other-secret-123']],
    'same as temporary' => [['password' => 'password', 'password_confirmation' => 'password']],
]);

test('changing the password lifts the restriction', function () {
    $this->actingAs($this->user)
        ->put(route('password.update'), [
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ])
        ->assertRedirect(route('dashboard'));

    $user = $this->user->fresh();
    expect($user->must_change_password)->toBeFalse()
        ->and(Hash::check('new-secret-123', $user->password))->toBeTrue();

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

test('users without a temporary password skip the change page', function () {
    $this->user->forceFill(['must_change_password' => false])->save();

    $this->actingAs($this->user)
        ->get(route('password.edit'))
        ->assertRedirect(route('dashboard'));
});

test('guests cannot change a password', function () {
    $this->get(route('password.edit'))->assertRedirect(route('login'));
    $this->put(route('password.update'), [
        'password' => 'new-secret-123',
        'password_confirmation' => 'new-secret-123',
    ])->assertRedirect(route('login'));
});
