<?php

use App\Models\User;

function emailDomainMigration(): object
{
    return require database_path('migrations/2026_10_14_140000_change_user_email_domain_to_fakemail.php');
}

test('existing gmail addresses move to fakemail, including users in the bin', function () {
    $active = User::factory()->create(['email' => 'asha@gmail.com']);
    $binned = User::factory()->create(['email' => 'john@gmail.com']);
    $binned->delete();
    $other = User::factory()->create(['email' => 'someone@example.com']);

    emailDomainMigration()->up();

    expect($active->fresh()->email)->toBe('asha@fakemail.com')
        ->and(User::withTrashed()->find($binned->id)->email)->toBe('john@fakemail.com')
        ->and($other->fresh()->email)->toBe('someone@example.com');
});

test('only a trailing @gmail.com is replaced', function () {
    $user = User::factory()->create(['email' => 'gmail.com.fan@gmail.com']);

    emailDomainMigration()->up();

    expect($user->fresh()->email)->toBe('gmail.com.fan@fakemail.com');
});

test('the change stops and rolls back if a new address is already taken', function () {
    $first = User::factory()->create(['email' => 'asha@gmail.com']);
    User::factory()->create(['email' => 'john@gmail.com']);
    User::factory()->create(['email' => 'john@fakemail.com']);

    expect(fn () => emailDomainMigration()->up())->toThrow(RuntimeException::class);

    expect($first->fresh()->email)->toBe('asha@gmail.com');
});

test('the migration can be reversed', function () {
    $user = User::factory()->create(['email' => 'asha@gmail.com']);

    emailDomainMigration()->up();
    emailDomainMigration()->down();

    expect($user->fresh()->email)->toBe('asha@gmail.com');
});

test('signing in works with the new address', function () {
    User::factory()->create(['email' => 'asha@gmail.com']);

    emailDomainMigration()->up();

    $this->post(route('login.store'), ['email' => 'asha@fakemail.com', 'password' => 'password']);
    $this->assertAuthenticated();
});
