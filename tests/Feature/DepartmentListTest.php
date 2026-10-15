<?php

use App\Models\Department;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot see the department list', function () {
    $this->get(route('departments.index'))->assertRedirect(route('login'));
});

test('every role can see all departments sorted by name', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->create())
        ->get(route('departments.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('departments/index')
            ->where('departments', fn ($rows) => collect($rows)->pluck('name')->all() === [
                'Claims',
                'Endorsement',
                'HR',
                'IT',
                'Sales',
                'Wellness',
            ])
        );
})->with(['employee', 'manager', 'admin']);

test('each department has only an id, a name, an employee count and its managers', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('departments.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('departments', 6)
            ->where('departments.0', [
                'id' => Department::where('name', 'Claims')->value('id'),
                'name' => 'Claims',
                'employeeCount' => 0,
                'managers' => [],
            ])
        );
});

test('cards show the head count including managers, and the manager, but no employee names', function () {
    User::factory()->manager()->inDepartment(Department::IT)->create(['name' => 'Bala Iyer']);
    User::factory()->inDepartment(Department::IT)->create(['name' => 'Asha Rao']);
    User::factory()->inDepartment(Department::IT)->create(['name' => 'Chitra Nair']);
    User::factory()->inDepartment(Department::HR)->create(['name' => 'Dev Shah']);

    $this->actingAs(User::factory()->create())
        ->get(route('departments.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('departments.3', fn ($it) => $it['name'] === 'IT'
                && $it['employeeCount'] === 3
                && collect($it['managers'])->all() === ['Bala Iyer'])
            ->where('departments.2.employeeCount', 1)
            ->where('departments.2.managers', [])
            ->missing('departments.3.employees')
        );
});

test('users without a department and users in the bin are not counted', function () {
    $viewer = User::factory()->create();
    User::factory()->inDepartment(Department::SALES)->create()->delete();
    User::factory()->manager()->inDepartment(Department::SALES)->create()->delete();

    $this->actingAs($viewer)
        ->get(route('departments.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('departments', fn ($rows) => collect($rows)
                ->every(fn ($row) => $row['employeeCount'] === 0 && collect($row['managers'])->isEmpty()))
        );
});

test('users with a temporary password are sent to change it first', function () {
    $this->actingAs(User::factory()->create(['must_change_password' => true]))
        ->get(route('departments.index'))
        ->assertRedirect(route('password.edit'));
});
