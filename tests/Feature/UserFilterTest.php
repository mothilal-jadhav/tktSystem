<?php

use App\Models\Department;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->viewer = User::factory()->admin()->create(['name' => 'Zara Admin']);

    User::factory()->employee()->inDepartment(Department::IT)->create(['name' => 'Asha Rao']);
    User::factory()->manager()->inDepartment(Department::IT)->create(['name' => 'Bala Iyer']);
    User::factory()->employee()->inDepartment(Department::HR)->create(['name' => 'Chitra Nair']);
    User::factory()->manager()->inDepartment(Department::HR)->create(['name' => 'Dev Shah']);
    User::factory()->employee()->create(['name' => 'Esha Gupta']);
});

/**
 * Names on the first page of the users list for the given query string.
 *
 * @param  array<string, mixed>  $query
 * @return list<string>
 */
function filteredNames(mixed $test, array $query): array
{
    return collect($test->actingAs($test->viewer)
        ->get(route('users.index', $query))
        ->viewData('page')['props']['users']['data'])
        ->pluck('name')
        ->all();
}

test('the list receives the filter options and the current filters', function () {
    $this->actingAs($this->viewer)
        ->get(route('users.index', ['role' => 'manager', 'department' => 'IT']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('roles', ['employee', 'manager', 'admin'])
            ->where('departments', ['Claims', 'Endorsement', 'HR', 'IT', 'Sales', 'Wellness'])
            ->where('filters', ['role' => 'manager', 'department' => 'IT'])
        );
});

test('without filters everyone is listed', function () {
    expect(filteredNames($this, []))
        ->toBe(['Asha Rao', 'Bala Iyer', 'Chitra Nair', 'Dev Shah', 'Esha Gupta', 'Zara Admin']);
});

test('users can be filtered by role', function () {
    expect(filteredNames($this, ['role' => 'manager']))->toBe(['Bala Iyer', 'Dev Shah'])
        ->and(filteredNames($this, ['role' => 'admin']))->toBe(['Zara Admin']);
});

test('users can be filtered by department', function () {
    expect(filteredNames($this, ['department' => 'HR']))->toBe(['Chitra Nair', 'Dev Shah']);
});

test('users without a department can be listed', function () {
    expect(filteredNames($this, ['department' => 'none']))->toBe(['Esha Gupta', 'Zara Admin']);
});

test('role and department filters combine', function () {
    expect(filteredNames($this, ['role' => 'employee', 'department' => 'IT']))->toBe(['Asha Rao'])
        ->and(filteredNames($this, ['role' => 'admin', 'department' => 'Sales']))->toBe([]);
});

test('unknown or malformed filter values are ignored', function (array $query) {
    $this->actingAs($this->viewer)
        ->get(route('users.index', $query))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters', ['role' => null, 'department' => null])
            ->has('users.data', 6)
        );
})->with([
    'unknown role' => [['role' => 'superuser']],
    'old requester role' => [['role' => 'requester']],
    'unknown department' => [['department' => 'Marketing']],
    'wrong case' => [['department' => 'it']],
    'department id' => [['department' => '1']],
    'array' => [['role' => ['admin'], 'department' => ['IT']]],
    'sql' => [['department' => "IT' OR '1'='1"]],
]);

test('users in the bin are not listed by any filter', function () {
    User::where('name', 'Asha Rao')->sole()->delete();

    expect(filteredNames($this, ['department' => 'IT']))->toBe(['Bala Iyer']);
});

test('pagination links keep the filters', function () {
    User::factory()->count(16)->employee()->inDepartment(Department::SALES)->create();

    $this->actingAs($this->viewer)
        ->get(route('users.index', ['department' => 'Sales']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('users.total', 16)
            ->where('users.next_page_url', fn (string $url) => str_contains($url, 'department=Sales') && str_contains($url, 'page=2'))
        );
});

test('every role can use the filters', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->create())
        ->get(route('users.index', ['department' => 'IT']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('users.data', 2));
})->with(['employee', 'manager']);

test('guests cannot filter the user list', function () {
    $this->get(route('users.index', ['role' => 'admin']))->assertRedirect(route('login'));
});
