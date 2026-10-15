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

test('each department has only an id, a name and its employees', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('departments.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('departments', 6)
            ->has('departments.0', fn (Assert $row) => $row
                ->whereType('id', 'integer')
                ->where('name', 'Claims')
                ->where('employees', [])
            )
        );
});

test('each card lists its employees by name with only their id, name and role', function () {
    $viewer = User::factory()->create();
    $bala = User::factory()->manager()->inDepartment(Department::IT)->create(['name' => 'Bala Iyer']);
    $asha = User::factory()->inDepartment(Department::IT)->create(['name' => 'Asha Rao']);
    User::factory()->inDepartment(Department::HR)->create(['name' => 'Chitra Nair']);

    $this->actingAs($viewer)
        ->get(route('departments.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('departments.3.name', 'IT')
            ->where('departments.3.employees', [
                ['id' => $asha->id, 'name' => 'Asha Rao', 'role' => 'employee'],
                ['id' => $bala->id, 'name' => 'Bala Iyer', 'role' => 'manager'],
            ])
            ->where('departments.2.employees.0.name', 'Chitra Nair')
            ->has('departments.2.employees', 1)
        );
});

test('users without a department and users in the bin are not listed', function () {
    $viewer = User::factory()->create();
    User::factory()->inDepartment(Department::SALES)->create(['name' => 'Asha Rao'])->delete();

    $this->actingAs($viewer)
        ->get(route('departments.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('departments', fn ($rows) => collect($rows)->every(fn ($row) => $row['employees'] === []))
        );
});

test('users with a temporary password are sent to change it first', function () {
    $this->actingAs(User::factory()->create(['must_change_password' => true]))
        ->get(route('departments.index'))
        ->assertRedirect(route('password.edit'));
});
