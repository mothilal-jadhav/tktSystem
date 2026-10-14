<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // The factory's password is "password".
    $this->user = User::factory()->create(['email' => 'asha@example.com']);
});

test('the home page renders the login form', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/login'));
});

test('a user can sign in with valid credentials', function () {
    $this->post(route('login.store'), [
        'email' => 'asha@example.com',
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($this->user);
});

test('the session id is regenerated on sign in', function () {
    $this->startSession();
    $before = session()->getId();

    $this->post(route('login.store'), ['email' => 'asha@example.com', 'password' => 'password']);

    expect(session()->getId())->not->toBe($before);
});

test('a wrong password and an unknown email get the same error', function () {
    $wrongPassword = $this->post(route('login.store'), [
        'email' => 'asha@example.com',
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email');
    $wrongPasswordError = session('errors')->first('email');

    $this->post(route('login.store'), [
        'email' => 'nobody@example.com',
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))
        ->toBe($wrongPasswordError)
        ->toBe(__('auth.failed'));

    $this->assertGuest();
});

test('sign in rejects invalid input', function (array $data, string $field) {
    $this->post(route('login.store'), $data)->assertSessionHasErrors($field);

    $this->assertGuest();
})->with([
    'missing email' => [['password' => 'password'], 'email'],
    'invalid email' => [['email' => 'not-an-email', 'password' => 'password'], 'email'],
    'missing password' => [['email' => 'asha@example.com'], 'password'],
    'non-boolean remember' => [['email' => 'asha@example.com', 'password' => 'password', 'remember' => 'yes please'], 'remember'],
]);

test('remember me sets a long-lived remember cookie', function () {
    $response = $this->post(route('login.store'), [
        'email' => 'asha@example.com',
        'password' => 'password',
        'remember' => '1',
    ]);

    $response->assertCookie(Auth::guard('web')->getRecallerName());
    expect($this->user->fresh()->remember_token)->not->toBeNull();
});

test('without remember me no remember cookie is set', function () {
    $this->post(route('login.store'), [
        'email' => 'asha@example.com',
        'password' => 'password',
    ])->assertCookieMissing(Auth::guard('web')->getRecallerName());
});

test('repeated failed attempts lock the account out', function () {
    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), ['email' => 'asha@example.com', 'password' => 'wrong']);
    }

    // Even the correct password is refused during the lockout.
    $this->post(route('login.store'), ['email' => 'asha@example.com', 'password' => 'password'])
        ->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toContain('Too many login attempts');
    $this->assertGuest();
});

test('after signing in the user returns to the page they asked for', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));

    $this->post(route('login.store'), ['email' => 'asha@example.com', 'password' => 'password'])
        ->assertRedirect(route('dashboard'));
});

test('signed-in users are redirected away from the login page', function () {
    $this->actingAs($this->user)->get('/')->assertRedirect(route('dashboard'));
    $this->actingAs($this->user)
        ->post(route('login.store'), ['email' => 'asha@example.com', 'password' => 'password'])
        ->assertRedirect(route('dashboard'));
});

test('a user can sign out', function () {
    $this->actingAs($this->user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('sign out cannot be triggered with a GET request', function () {
    $this->actingAs($this->user)->get('/logout')->assertMethodNotAllowed();

    $this->assertAuthenticated();
});

test('guests cannot sign out', function () {
    $this->post(route('logout'))->assertRedirect(route('login'));
});

test('registration has been removed', function () {
    expect(Route::has('register'))->toBeFalse()
        ->and(Route::has('register.store'))->toBeFalse();

    $this->get('/register')->assertNotFound();
    $this->post('/register', [
        'name' => 'Intruder',
        'email' => 'intruder@example.com',
        'password' => 'correct-horse-battery',
        'password_confirmation' => 'correct-horse-battery',
    ])->assertNotFound();

    expect(User::where('email', 'intruder@example.com')->exists())->toBeFalse();
});

test('only the id and name of the signed-in user are shared with the page', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user', ['id' => $this->user->id, 'name' => $this->user->name])
        );
});
