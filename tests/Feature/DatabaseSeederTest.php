<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // DepartmentEmployeeSeeder writes seeded passwords to the local disk.
    Storage::fake('local');
});

test('it creates the demo admin outside production', function () {
    $this->seed(DatabaseSeeder::class);

    $admin = User::where('email', DatabaseSeeder::DEMO_ADMIN_EMAIL)->sole();
    expect($admin->hasRole(Role::ADMIN))->toBeTrue();
});

test('running it again does not fail or duplicate the demo admin', function () {
    $this->seed(DatabaseSeeder::class);
    $count = User::count();

    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBe($count)
        ->and(User::where('email', DatabaseSeeder::DEMO_ADMIN_EMAIL)->count())->toBe(1);
});

test('it creates no users in production', function () {
    app()->detectEnvironment(fn () => 'production');

    // --force skips Laravel's own production prompt, so the seeder's check is what's tested.
    $this->artisan('db:seed', ['--force' => true])
        ->expectsOutputToContain('not created in production')
        ->assertSuccessful();

    expect(User::count())->toBe(0);
});
