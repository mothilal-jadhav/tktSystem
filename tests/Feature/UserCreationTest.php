<?php

use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->creator = User::factory()->admin()->create();
});

test('guests cannot open the create user page', function () {
    $this->get(route('users.create'))->assertRedirect(route('login'));
});

test('guests cannot create users', function () {
    $this->post(route('users.store'), ['name' => 'Asha Rao', 'role' => 'employee', 'department' => 'IT'])->assertRedirect(route('login'));

    expect(User::count())->toBe(1);
});

test('admins see the create user form', function () {
    $this->actingAs($this->creator)
        ->get(route('users.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('users/create'));
});

test('the email is built from the letters in the name', function () {
    $this->actingAs($this->creator)
        ->post(route('users.store'), ['name' => 'Dr. Asha  Rao', 'role' => 'employee', 'department' => 'IT'])
        ->assertRedirect(route('users.create'));

    $user = User::where('email', 'drasharao@fakemail.com')->sole();
    expect($user->name)->toBe('Dr. Asha  Rao')
        ->and($user->must_change_password)->toBeTrue();
});

test('a number is added when the generated email is already taken', function () {
    foreach (range(1, 3) as $attempt) {
        $this->actingAs($this->creator)->post(route('users.store'), ['name' => 'John Doe', 'role' => 'employee', 'department' => 'IT']);
    }

    expect(User::where('name', 'John Doe')->orderBy('id')->pluck('email')->all())
        ->toBe(['johndoe@fakemail.com', 'johndoe1@fakemail.com', 'johndoe2@fakemail.com']);
});

test('the temporary password is shown once and stored hashed', function () {
    $this->actingAs($this->creator)->post(route('users.store'), ['name' => 'Asha Rao', 'role' => 'employee', 'department' => 'IT']);

    $flashed = $this->actingAs($this->creator)
        ->get(route('users.create'))
        ->viewData('page')['flash']['createdUser'];

    $user = User::where('email', 'asharao@fakemail.com')->sole();

    expect($flashed['email'])->toBe('asharao@fakemail.com')
        ->and(strlen($flashed['password']))->toBe(16)
        ->and($user->password)->not->toBe($flashed['password'])
        ->and(Hash::check($flashed['password'], $user->password))->toBeTrue();

    // The next visit no longer carries it.
    $this->actingAs($this->creator)
        ->get(route('users.create'))
        ->assertInertia(fn (Assert $page) => $page->missing('flash.createdUser'));
});

test('invalid names are rejected', function (string $name) {
    $this->actingAs($this->creator)
        ->post(route('users.store'), ['name' => $name, 'role' => 'employee', 'department' => 'IT'])
        ->assertSessionHasErrors('name');

    expect(User::count())->toBe(1);
})->with([
    'empty' => '',
    'digits' => 'Asha1',
    'symbols' => 'Asha@Rao',
    'html' => '<script>alert(1)</script>',
    'hyphen' => 'Asha-Rao',
    'only dots and spaces' => '. .',
    'too long' => str_repeat('a', 256),
]);

test('names with more letters than an email address allows are rejected', function () {
    // 61 letters, well under the 255-character limit thanks to the spaces.
    $name = implode(' ', str_split(str_repeat('a', StoreUserRequest::MAX_NAME_LETTERS + 1), 10));

    $this->actingAs($this->creator)
        ->post(route('users.store'), ['name' => $name, 'role' => 'employee', 'department' => 'IT'])
        ->assertSessionHasErrors(['name' => 'The name may contain at most 60 letters.']);

    expect(User::count())->toBe(1);
});

test('the longest allowed name still gives a valid email, even with a number added', function () {
    $name = str_repeat('a', StoreUserRequest::MAX_NAME_LETTERS);

    foreach (range(1, 2) as $attempt) {
        $this->actingAs($this->creator)
            ->post(route('users.store'), ['name' => $name, 'role' => 'employee', 'department' => 'IT'])
            ->assertSessionHasNoErrors();
    }

    $emails = User::where('name', $name)->orderBy('id')->pluck('email');
    expect($emails->all())->toBe([
        str_repeat('a', 60).'@fakemail.com',
        str_repeat('a', 60).'1@fakemail.com',
    ]);
    $emails->each(fn (string $email) => expect(strlen(strstr($email, '@', true)))->toBeLessThanOrEqual(64));
});

test('client-supplied email, password and flags are ignored', function () {
    $this->actingAs($this->creator)->post(route('users.store'), [
        'name' => 'Asha Rao',
        'role' => 'employee',
        'department' => 'IT',
        'email' => 'admin@example.com',
        'password' => 'chosen-by-client',
        'must_change_password' => false,
    ]);

    $user = User::where('name', 'Asha Rao')->sole();

    expect($user->email)->toBe('asharao@fakemail.com')
        ->and(Hash::check('chosen-by-client', $user->password))->toBeFalse()
        ->and($user->must_change_password)->toBeTrue();
});

test('users with a temporary password cannot create users', function () {
    $this->creator->forceFill(['must_change_password' => true])->save();

    $this->actingAs($this->creator)
        ->post(route('users.store'), ['name' => 'Asha Rao', 'role' => 'employee', 'department' => 'IT'])
        ->assertRedirect(route('password.edit'));

    expect(User::count())->toBe(1);
});

test('non-admins cannot open the create user page', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->create())
        ->get(route('users.create'))
        ->assertForbidden();
})->with(['employee' => 'employee', 'manager' => 'manager']);

test('non-admins cannot create users', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->create())
        ->post(route('users.store'), ['name' => 'Asha Rao', 'role' => 'employee', 'department' => 'IT'])
        ->assertForbidden();

    expect(User::where('email', 'asharao@fakemail.com')->exists())->toBeFalse();
})->with(['employee' => 'employee', 'manager' => 'manager']);

test('only admins are told they can create users', function (string $role, bool $can) {
    $this->actingAs(User::factory()->{$role}()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('auth.can.createUsers', $can));
})->with([
    'employee' => ['employee', false],
    'manager' => ['manager', false],
    'admin' => ['admin', true],
]);

test('the create form receives the role options', function () {
    $this->actingAs($this->creator)
        ->get(route('users.create'))
        ->assertInertia(fn (Assert $page) => $page->where('roles', ['employee', 'manager', 'admin']));
});

test('admins choose the new user\'s role', function (string $role) {
    $this->actingAs($this->creator)
        ->post(route('users.store'), ['name' => 'Asha Rao', 'role' => $role, 'department' => 'IT'])
        ->assertRedirect(route('users.create'));

    expect(User::where('email', 'asharao@fakemail.com')->sole()->role->name)->toBe($role);

    $flashed = $this->actingAs($this->creator)->get(route('users.create'))->viewData('page')['flash']['createdUser'];
    expect($flashed['role'])->toBe($role);
})->with(['employee', 'manager', 'admin']);

test('an invalid role is rejected', function (array $data) {
    $this->actingAs($this->creator)
        ->post(route('users.store'), ['name' => 'Asha Rao', 'department' => 'IT', ...$data])
        ->assertSessionHasErrors('role');

    expect(User::where('name', 'Asha Rao')->exists())->toBeFalse();
})->with([
    'missing' => [[]],
    'empty' => [['role' => '']],
    'unknown' => [['role' => 'superuser']],
    'old requester role' => [['role' => 'requester']],
    'old agent role' => [['role' => 'agent']],
]);
