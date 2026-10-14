<?php

use App\Models\User;
use Illuminate\Support\Arr;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('signed-in users see the dashboard', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('dashboard'));
});

test('the dashboard receives its statistics as props', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('stats', 6, fn (Assert $stat) => $stat
                ->whereType('label', 'string')
                ->whereType('value', 'string')
                ->whereType('change', 'double')
                ->where('trend', fn (string $trend) => in_array($trend, ['up', 'down'], true))
            )
            ->has('ticketsByDay', 12, fn (Assert $day) => $day
                ->hasAll(['date', 'created', 'resolved'])
            )
            ->where('resolutionRate', fn (int $rate) => $rate >= 0 && $rate <= 100)
            ->has('ticketsByDepartment.0', fn (Assert $department) => $department
                ->whereType('name', 'string')
                ->whereType('count', 'integer')
            )
            ->has('recentTickets.0', fn (Assert $ticket) => $ticket
                ->hasAll(['reference', 'subject', 'department', 'priority', 'status', 'updated_at'])
            )
        );
});

test('the dashboard props do not expose sensitive fields', function () {
    $props = $this->actingAs($this->user)->get(route('dashboard'))->viewData('page')['props'];

    $keys = array_keys(Arr::dot($props));

    foreach (['password', 'remember_token', 'email', 'internal_note'] as $sensitive) {
        expect($keys)->each->not->toContain($sensitive);
    }
});

test('guests are redirected away from the dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('register'));
});
