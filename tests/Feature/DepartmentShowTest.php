<?php

use App\Models\Department;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->it = Department::where('name', Department::IT)->sole();
});

test('guests cannot see a department', function () {
    $this->get(route('departments.show', $this->it))->assertRedirect(route('login'));
});

test('every role can see a department', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->create())
        ->get(route('departments.show', $this->it))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('departments/show')
            ->where('department', ['id' => $this->it->id, 'name' => 'IT', 'employeeCount' => 0])
            ->where('managers', [])
        );
})->with(['employee', 'manager', 'admin']);

test('it lists only that department\'s employees, sorted by name', function () {
    User::factory()->inDepartment(Department::IT)->create(['name' => 'Bala Iyer']);
    User::factory()->inDepartment(Department::IT)->create(['name' => 'Asha Rao']);
    User::factory()->inDepartment(Department::HR)->create(['name' => 'Chitra Nair']);
    User::factory()->create(['name' => 'Dev Shah']);

    $this->actingAs(User::factory()->create())
        ->get(route('departments.show', $this->it))
        ->assertInertia(fn (Assert $page) => $page
            ->where('employees.data', fn ($rows) => collect($rows)->pluck('name')->all() === ['Asha Rao', 'Bala Iyer'])
            ->where('employees.total', 2)
        );
});

test('each row has only the id, name, email and joined date', function () {
    $asha = User::factory()->employee()->inDepartment(Department::IT)->create([
        'name' => 'Asha Rao',
        'email' => 'asha@example.com',
        'created_at' => '2026-10-01 09:30:00',
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('departments.show', $this->it))
        ->assertInertia(fn (Assert $page) => $page
            ->where('employees.data.0', [
                'id' => $asha->id,
                'name' => 'Asha Rao',
                'email' => 'asha@example.com',
                'joined' => '2026-10-01',
            ])
        );
});

test('managers are shown at the top and not repeated in the team list', function () {
    $bala = User::factory()->manager()->inDepartment(Department::IT)->create([
        'name' => 'Bala Iyer',
        'email' => 'bala@example.com',
        'created_at' => '2026-09-01 10:00:00',
    ]);
    User::factory()->inDepartment(Department::IT)->create(['name' => 'Asha Rao']);
    User::factory()->inDepartment(Department::IT)->create(['name' => 'Chitra Nair']);
    User::factory()->manager()->inDepartment(Department::HR)->create();

    $this->actingAs(User::factory()->create())
        ->get(route('departments.show', $this->it))
        ->assertInertia(fn (Assert $page) => $page
            ->where('managers', [[
                'id' => $bala->id,
                'name' => 'Bala Iyer',
                'email' => 'bala@example.com',
                'joined' => '2026-09-01',
            ]])
            ->where('employees.data', fn ($rows) => collect($rows)->pluck('name')->all() === ['Asha Rao', 'Chitra Nair'])
            ->where('department.employeeCount', 3)
        );
});

test('users in the bin are not listed', function () {
    User::factory()->inDepartment(Department::IT)->create()->delete();

    $this->actingAs(User::factory()->create())
        ->get(route('departments.show', $this->it))
        ->assertInertia(fn (Assert $page) => $page
            ->where('employees.total', 0)
            ->where('department.employeeCount', 0)
        );
});

test('employees are paginated 15 per page', function () {
    User::factory()->count(17)->inDepartment(Department::IT)->create();

    $this->actingAs(User::factory()->create())
        ->get(route('departments.show', ['department' => $this->it, 'page' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('employees.data', 2)
            ->where('employees.total', 17)
        );
});

test('an unknown department returns 404', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('departments.show', 999))
        ->assertNotFound();
});

test('users with a temporary password are sent to change it first', function () {
    $this->actingAs(User::factory()->create(['must_change_password' => true]))
        ->get(route('departments.show', $this->it))
        ->assertRedirect(route('password.edit'));
});
