<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    /**
     * List every department, sorted by name.
     */
    public function index(): Response
    {
        return Inertia::render('departments/index', [
            // Only these fields are sent to the browser.
            'departments' => Department::orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Department $department) => [
                    'id' => $department->id,
                    'name' => $department->name,
                ]),
        ]);
    }
}
