<?php

use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentEmployeeSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

/**
 * @return list<list<string>>
 */
function seededCsvRows(): array
{
    $lines = explode(PHP_EOL, trim(Storage::disk('local')->get(DepartmentEmployeeSeeder::PASSWORDS_FILE)));

    return array_map(fn (string $line) => str_getcsv($line, escape: ''), $lines);
}

test('the list covers every department with unique names', function () {
    expect(array_keys(DepartmentEmployeeSeeder::EMPLOYEES))->toEqualCanonicalizing(Department::ALL);

    $names = collect(DepartmentEmployeeSeeder::EMPLOYEES)->flatten();
    expect($names->unique()->count())->toBe($names->count());
});

test('HR gets 3 employees and every other department gets 10', function () {
    $this->seed(DepartmentEmployeeSeeder::class);

    $counts = Department::withCount(['users' => fn ($query) => $query->whereRelation('role', 'name', 'employee')])
        ->pluck('users_count', 'name')
        ->all();

    expect($counts)->toEqualCanonicalizing([
        'HR' => 3,
        'Sales' => 10,
        'IT' => 10,
        'Wellness' => 10,
        'Claims' => 10,
        'Endorsement' => 10,
    ]);
});

test('seeded users have unique fake emails and must change their password', function () {
    $this->seed(DepartmentEmployeeSeeder::class);

    $users = User::all();

    expect($users)->toHaveCount(53)
        ->and($users->pluck('email')->unique())->toHaveCount(53)
        ->and($users->every(fn (User $user) => str_ends_with($user->email, '@fakemail.com')))->toBeTrue()
        ->and($users->every(fn (User $user) => $user->must_change_password))->toBeTrue()
        ->and(User::where('name', 'Anjali Dsouza')->sole()->email)->toBe('anjalidsouza@fakemail.com');
});

test('the passwords file has a header and a working password for every user', function () {
    $this->seed(DepartmentEmployeeSeeder::class);

    $rows = seededCsvRows();

    expect($rows[0])->toBe(['name', 'email', 'department', 'password'])
        ->and($rows)->toHaveCount(54);

    foreach (array_slice($rows, 1) as [$name, $email, $department, $password]) {
        $user = User::where('email', $email)->sole();

        expect($user->name)->toBe($name)
            ->and($user->department->name)->toBe($department)
            ->and(Hash::check($password, $user->password))->toBeTrue();
    }
});

test('running it again creates no one and adds nothing to the passwords file', function () {
    $this->seed(DepartmentEmployeeSeeder::class);
    $this->seed(DepartmentEmployeeSeeder::class);

    expect(User::count())->toBe(53)
        ->and(seededCsvRows())->toHaveCount(54);
});

test('a binned user with the same email is not recreated or restored', function () {
    User::factory()->create(['name' => 'Ananya Iyer', 'email' => 'ananyaiyer@fakemail.com'])->delete();

    $this->seed(DepartmentEmployeeSeeder::class);

    expect(User::withTrashed()->where('email', 'ananyaiyer@fakemail.com')->count())->toBe(1)
        ->and(User::onlyTrashed()->where('email', 'ananyaiyer@fakemail.com')->exists())->toBeTrue();
});

test('it does nothing in production', function () {
    app()->detectEnvironment(fn () => 'production');

    // --force skips Laravel's own production prompt, so the seeder's check is what's tested.
    $this->artisan('db:seed', ['--class' => DepartmentEmployeeSeeder::class, '--force' => true])
        ->expectsOutputToContain('not created in production')
        ->assertSuccessful();

    expect(User::count())->toBe(0);
    Storage::disk('local')->assertMissing(DepartmentEmployeeSeeder::PASSWORDS_FILE);
});
