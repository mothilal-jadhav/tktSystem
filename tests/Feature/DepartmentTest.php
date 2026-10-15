<?php

use App\Models\Department;
use Illuminate\Database\QueryException;

test('the organisation departments exist in order', function () {
    expect(Department::orderBy('id')->pluck('name')->all())->toBe([
        'HR',
        'Sales',
        'IT',
        'Wellness',
        'Claims',
        'Endorsement',
    ]);
});

test('there are exactly six departments', function () {
    expect(Department::count())->toBe(6);
});

test('department names are unique', function () {
    Department::create(['name' => Department::IT]);
})->throws(QueryException::class);

test('the department constants match the database', function () {
    expect(Department::orderBy('id')->pluck('name')->all())->toBe(Department::ALL);
});
