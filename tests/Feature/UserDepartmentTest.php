<?php

use App\Models\Department;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

// --- Creating a user ---

test('the create form receives the department options sorted by name', function () {
    $this->actingAs($this->admin)
        ->get(route('users.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('departments', ['Claims', 'Endorsement', 'HR', 'IT', 'Sales', 'Wellness'])
        );
});

test('admins choose the new user\'s department', function (string $department) {
    $this->actingAs($this->admin)
        ->post(route('users.store'), ['name' => 'Asha Rao', 'role' => 'employee', 'department' => $department])
        ->assertRedirect(route('users.create'));

    expect(User::where('email', 'asharao@fakemail.com')->sole()->department->name)->toBe($department);

    $flashed = $this->actingAs($this->admin)->get(route('users.create'))->viewData('page')['flash']['createdUser'];
    expect($flashed['department'])->toBe($department);
})->with(Department::ALL);

test('an invalid department is rejected when creating a user', function (array $data) {
    $this->actingAs($this->admin)
        ->post(route('users.store'), ['name' => 'Asha Rao', 'role' => 'employee', ...$data])
        ->assertSessionHasErrors('department');

    expect(User::where('name', 'Asha Rao')->exists())->toBeFalse();
})->with([
    'missing' => [[]],
    'empty' => [['department' => '']],
    'unknown' => [['department' => 'Marketing']],
    'wrong case' => [['department' => 'it']],
    'an id instead of a name' => [['department' => '1']],
]);

test('a raw department_id sent by the client is ignored when creating', function () {
    $this->actingAs($this->admin)->post(route('users.store'), [
        'name' => 'Asha Rao',
        'role' => 'employee',
        'department' => Department::IT,
        'department_id' => Department::where('name', Department::HR)->value('id'),
    ]);

    expect(User::where('email', 'asharao@fakemail.com')->sole()->department->name)->toBe(Department::IT);
});

// --- Editing a user ---

test('the edit form receives the user\'s current department', function () {
    $target = User::factory()->inDepartment(Department::SALES)->create();

    $this->actingAs($this->admin)
        ->get(route('users.edit', $target))
        ->assertInertia(fn (Assert $page) => $page->where('user.department', 'Sales'));
});

test('admins can change a user\'s department', function () {
    $target = User::factory()->inDepartment(Department::SALES)->create();

    $this->actingAs($this->admin)
        ->put(route('users.update', $target), ['role' => 'employee', 'department' => Department::CLAIMS])
        ->assertRedirect(route('users.index'));

    expect($target->fresh()->department->name)->toBe(Department::CLAIMS);
});

test('a user without a department can be given one', function () {
    $target = User::factory()->create();

    $this->actingAs($this->admin)
        ->put(route('users.update', $target), ['role' => 'employee', 'department' => Department::HR]);

    expect($target->fresh()->department->name)->toBe(Department::HR);
});

test('an invalid department is rejected when editing a user', function (array $data) {
    $target = User::factory()->inDepartment(Department::SALES)->create();

    $this->actingAs($this->admin)
        ->put(route('users.update', $target), ['role' => 'employee', ...$data])
        ->assertSessionHasErrors('department');

    expect($target->fresh()->department->name)->toBe(Department::SALES);
})->with([
    'missing' => [[]],
    'empty' => [['department' => '']],
    'unknown' => [['department' => 'Marketing']],
]);

test('a raw department_id sent by the client is ignored when editing', function () {
    $target = User::factory()->create();

    $this->actingAs($this->admin)->put(route('users.update', $target), [
        'role' => 'employee',
        'department' => Department::IT,
        'department_id' => Department::where('name', Department::HR)->value('id'),
    ]);

    expect($target->fresh()->department->name)->toBe(Department::IT);
});

test('non-admins cannot change a user\'s department', function (string $role) {
    $target = User::factory()->inDepartment(Department::SALES)->create();

    $this->actingAs(User::factory()->{$role}()->create())
        ->put(route('users.update', $target), ['role' => 'employee', 'department' => Department::IT])
        ->assertForbidden();

    expect($target->fresh()->department->name)->toBe(Department::SALES);
})->with(['employee', 'manager']);

// --- Users list ---

test('the users list shows each user\'s department', function () {
    User::factory()->inDepartment(Department::WELLNESS)->create(['name' => 'Asha Rao']);
    User::factory()->create(['name' => 'Bala Iyer']);

    $this->actingAs($this->admin)
        ->get(route('users.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('users.data', fn ($rows) => collect($rows)->pluck('department', 'name')->only(['Asha Rao', 'Bala Iyer'])->all() === [
                'Asha Rao' => 'Wellness',
                'Bala Iyer' => null,
            ])
        );
});
