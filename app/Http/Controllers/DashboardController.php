<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the helpdesk dashboard.
     */
    public function __invoke(): Response
    {
        return Inertia::render('dashboard', $this->sampleData());
    }

    /**
     * Placeholder statistics until the ticket tables exist.
     *
     * These values will be replaced by Eloquent queries scoped to the
     * authenticated user's role and department. They contain no real data.
     *
     * @return array<string, mixed>
     */
    private function sampleData(): array
    {
        return [
            'stats' => [
                ['label' => 'Open tickets', 'value' => '128', 'change' => 4.2, 'trend' => 'up'],
                ['label' => 'Pending', 'value' => '37', 'change' => 1.8, 'trend' => 'down'],
                ['label' => 'Resolved today', 'value' => '54', 'change' => 12.5, 'trend' => 'up'],
                ['label' => 'Avg first response', 'value' => '1h 42m', 'change' => 6.3, 'trend' => 'down'],
                ['label' => 'SLA breaches', 'value' => '9', 'change' => 2.1, 'trend' => 'up'],
                ['label' => 'CSAT', 'value' => '92%', 'change' => 3.07, 'trend' => 'up'],
            ],
            'ticketsByDay' => [
                ['date' => '2026-10-02', 'created' => 42, 'resolved' => 35],
                ['date' => '2026-10-03', 'created' => 38, 'resolved' => 40],
                ['date' => '2026-10-04', 'created' => 21, 'resolved' => 18],
                ['date' => '2026-10-05', 'created' => 17, 'resolved' => 22],
                ['date' => '2026-10-06', 'created' => 45, 'resolved' => 31],
                ['date' => '2026-10-07', 'created' => 50, 'resolved' => 43],
                ['date' => '2026-10-08', 'created' => 39, 'resolved' => 41],
                ['date' => '2026-10-09', 'created' => 33, 'resolved' => 36],
                ['date' => '2026-10-10', 'created' => 47, 'resolved' => 39],
                ['date' => '2026-10-11', 'created' => 19, 'resolved' => 24],
                ['date' => '2026-10-12', 'created' => 15, 'resolved' => 16],
                ['date' => '2026-10-13', 'created' => 41, 'resolved' => 27],
            ],
            'resolutionRate' => 67,
            'ticketsByDepartment' => [
                ['name' => 'IT Support', 'count' => 72],
                ['name' => 'Facilities', 'count' => 39],
                ['name' => 'Human Resources', 'count' => 31],
                ['name' => 'Finance', 'count' => 24],
                ['name' => 'Procurement', 'count' => 12],
            ],
            'recentTickets' => [
                ['reference' => 'TKT-1048', 'subject' => 'VPN disconnects every few minutes', 'department' => 'IT Support', 'priority' => 'high', 'status' => 'open', 'updated_at' => '2026-10-13T09:24:00Z'],
                ['reference' => 'TKT-1047', 'subject' => 'Request for second monitor', 'department' => 'Procurement', 'priority' => 'low', 'status' => 'pending', 'updated_at' => '2026-10-13T08:51:00Z'],
                ['reference' => 'TKT-1046', 'subject' => 'Air conditioning not working on floor 3', 'department' => 'Facilities', 'priority' => 'medium', 'status' => 'in_progress', 'updated_at' => '2026-10-13T08:10:00Z'],
                ['reference' => 'TKT-1045', 'subject' => 'Payslip shows incorrect deductions', 'department' => 'Finance', 'priority' => 'high', 'status' => 'open', 'updated_at' => '2026-10-12T17:42:00Z'],
                ['reference' => 'TKT-1044', 'subject' => 'Update emergency contact details', 'department' => 'Human Resources', 'priority' => 'low', 'status' => 'resolved', 'updated_at' => '2026-10-12T15:03:00Z'],
                ['reference' => 'TKT-1043', 'subject' => 'Shared drive permission denied', 'department' => 'IT Support', 'priority' => 'urgent', 'status' => 'in_progress', 'updated_at' => '2026-10-12T11:30:00Z'],
            ],
        ];
    }
}
