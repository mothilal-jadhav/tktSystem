<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;

const RESET_LINK_SENT = 'If an account exists for that email, a password reset link has been sent to it.';

beforeEach(function () {
    // The factory's password is "password".
    $this->user = User::factory()->create(['email' => 'asha@example.com']);
});

test('guests can open the forgot password page', function () {
    $this->get(route('password.request'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/forgot-password'));
});

test('signed-in users are sent away from the reset pages', function (string $method, string $url) {
    $this->actingAs($this->user)
        ->call($method, $url)
        ->assertRedirect(route('dashboard'));
})->with([
    'forgot page' => ['GET', '/forgot-password'],
    'send link' => ['POST', '/forgot-password'],
    'reset page' => ['GET', '/reset-password/some-token'],
    'reset' => ['POST', '/reset-password'],
]);

test('a reset link is sent to an existing user', function () {
    Notification::fake();

    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => 'asha@example.com'])
        ->assertRedirect(route('password.request'))
        ->assertInertiaFlash('status', RESET_LINK_SENT);

    Notification::assertSentTo($this->user, ResetPassword::class, function (ResetPassword $notification) {
        // The link opens the reset page with the token and email.
        $url = $notification->toMail($this->user)->actionUrl;

        return str_contains($url, route('password.reset', $notification->token, false))
            && str_contains($url, 'email=asha%40example.com');
    });
});

test('unknown and binned emails get the same message and no link', function (string $email) {
    Notification::fake();
    User::factory()->create(['email' => 'binned@example.com'])->delete();

    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => $email])
        ->assertRedirect(route('password.request'))
        ->assertInertiaFlash('status', RESET_LINK_SENT);

    Notification::assertNothingSent();
})->with(['nobody@example.com', 'binned@example.com']);

test('the forgot password email is validated', function (array $data) {
    Notification::fake();

    $this->post(route('password.email'), $data)->assertSessionHasErrors('email');

    Notification::assertNothingSent();
})->with([
    'missing' => [[]],
    'not an email' => [['email' => 'not-an-email']],
]);

test('the reset link opens the new password page', function () {
    $token = Password::createToken($this->user);

    $this->get(route('password.reset', ['token' => $token, 'email' => 'asha@example.com']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/reset-password')
            ->where('token', $token)
            ->where('email', 'asha@example.com'));
});

test('a valid token resets the password', function () {
    $this->user->forceFill(['must_change_password' => true, 'remember_token' => 'old-token'])->save();
    $token = Password::createToken($this->user);

    $this->post(route('password.store'), [
        'token' => $token,
        'email' => 'asha@example.com',
        'password' => 'brand-new-secret',
        'password_confirmation' => 'brand-new-secret',
    ])
        ->assertRedirect(route('login'))
        ->assertInertiaFlash('status', 'Your password has been reset. You can now sign in.');

    $user = $this->user->fresh();
    expect(Hash::check('brand-new-secret', $user->password))->toBeTrue()
        ->and($user->must_change_password)->toBeFalse()
        ->and($user->remember_token)->not->toBe('old-token');

    $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'asha@example.com']);
    $this->assertGuest();
});

test('a used token cannot reset the password again', function () {
    $token = Password::createToken($this->user);
    $data = [
        'token' => $token,
        'email' => 'asha@example.com',
        'password' => 'brand-new-secret',
        'password_confirmation' => 'brand-new-secret',
    ];

    $this->post(route('password.store'), $data)->assertRedirect(route('login'));

    $this->post(route('password.store'), [...$data, 'password' => 'another-secret', 'password_confirmation' => 'another-secret'])
        ->assertSessionHasErrors('email');

    expect(Hash::check('brand-new-secret', $this->user->fresh()->password))->toBeTrue();
});

test('a bad token or another email cannot reset the password', function (string $email, ?string $token) {
    User::factory()->create(['email' => 'other@example.com']);
    $realToken = Password::createToken($this->user);

    $this->post(route('password.store'), [
        'token' => $token ?? $realToken,
        'email' => $email,
        'password' => 'brand-new-secret',
        'password_confirmation' => 'brand-new-secret',
    ])->assertSessionHasErrors(['email' => 'This password reset link is invalid or has expired.']);

    expect(Hash::check('password', $this->user->fresh()->password))->toBeTrue();
})->with([
    'wrong token' => ['asha@example.com', 'wrong-token'],
    "token used for another user's email" => ['other@example.com', null],
    'unknown email' => ['nobody@example.com', null],
]);

test('an expired token cannot reset the password', function () {
    $token = Password::createToken($this->user);

    $this->travel(61)->minutes();

    $this->post(route('password.store'), [
        'token' => $token,
        'email' => 'asha@example.com',
        'password' => 'brand-new-secret',
        'password_confirmation' => 'brand-new-secret',
    ])->assertSessionHasErrors('email');

    expect(Hash::check('password', $this->user->fresh()->password))->toBeTrue();
});

test('the new password is validated', function (array $data) {
    $token = Password::createToken($this->user);

    $this->post(route('password.store'), ['token' => $token, 'email' => 'asha@example.com', ...$data])
        ->assertSessionHasErrors('password');

    expect(Hash::check('password', $this->user->fresh()->password))->toBeTrue();
})->with([
    'missing' => [[]],
    'too short' => [['password' => 'short', 'password_confirmation' => 'short']],
    'mismatched confirmation' => [['password' => 'brand-new-secret', 'password_confirmation' => 'other-secret-123']],
]);

test('the reset form is rate limited', function () {
    foreach (range(1, 6) as $attempt) {
        $this->post(route('password.email'), ['email' => 'nobody@example.com']);
    }

    $this->post(route('password.email'), ['email' => 'nobody@example.com'])->assertTooManyRequests();
});
