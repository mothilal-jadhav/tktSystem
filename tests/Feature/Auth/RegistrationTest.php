<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

function validRegistration(array $overrides = []): array
{
    return [
        'name' => 'Asha Rao',
        'email' => 'asha@example.com',
        'password' => 'correct-horse-battery',
        'password_confirmation' => 'correct-horse-battery',
        ...$overrides,
    ];
}

test('the home page renders the registration form', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/register'));
});

test('a guest can register and is signed in', function () {
    $this->post(route('register.store'), validRegistration())
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();

    $user = User::sole();
    expect($user->name)->toBe('Asha Rao')
        ->and($user->email)->toBe('asha@example.com');
});

test('the password is stored hashed', function () {
    $this->post(route('register.store'), validRegistration());

    $user = User::sole();
    expect($user->password)->not->toBe('correct-horse-battery')
        ->and(Hash::check('correct-horse-battery', $user->password))->toBeTrue();
});

test('the session id is regenerated on registration', function () {
    $this->startSession();
    $before = session()->getId();

    $this->post(route('register.store'), validRegistration());

    expect(session()->getId())->not->toBe($before);
});

test('registration rejects invalid input', function (array $overrides, string $field) {
    $this->post(route('register.store'), validRegistration($overrides))
        ->assertSessionHasErrors($field);

    $this->assertGuest();
    expect(User::count())->toBe(0);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'name too long' => [['name' => str_repeat('a', 256)], 'name'],
    'missing email' => [['email' => ''], 'email'],
    'invalid email' => [['email' => 'not-an-email'], 'email'],
    'uppercase email' => [['email' => 'Asha@Example.com'], 'email'],
    'missing password' => [['password' => '', 'password_confirmation' => ''], 'password'],
    'short password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
    'mismatched confirmation' => [['password_confirmation' => 'something-else'], 'password'],
]);

test('an email address can only be registered once', function () {
    User::factory()->create(['email' => 'asha@example.com']);

    $this->post(route('register.store'), validRegistration())
        ->assertSessionHasErrors('email');

    expect(User::count())->toBe(1);
});

test('client-supplied privileged fields are ignored', function () {
    $this->post(route('register.store'), validRegistration([
        'id' => 999,
        'role' => 'administrator',
        'email_verified_at' => now()->toDateTimeString(),
    ]));

    $user = User::sole();
    expect($user->id)->not->toBe(999)
        ->and($user->email_verified_at)->toBeNull()
        ->and($user->getAttributes())->not->toHaveKey('role');
});

test('signed-in users cannot reach the registration page', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertRedirect(route('dashboard'));
});

test('signed-in users cannot register another account', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('register.store'), validRegistration())
        ->assertRedirect(route('dashboard'));

    expect(User::count())->toBe(1);
});

test('registration attempts are rate limited', function () {
    foreach (range(1, 6) as $attempt) {
        $this->post(route('register.store'), validRegistration(['email' => 'bad']));
    }

    $this->post(route('register.store'), validRegistration())
        ->assertTooManyRequests();

    expect(User::count())->toBe(0);
});

test('only the id and name of the signed-in user are shared with the page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user', ['id' => $user->id, 'name' => $user->name])
        );
});
