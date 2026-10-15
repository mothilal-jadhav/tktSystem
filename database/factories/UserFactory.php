<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role_id' => fn () => Role::where('name', Role::EMPLOYEE)->value('id'),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Give the user the employee role (also the default).
     */
    public function employee(): static
    {
        return $this->withRole(Role::EMPLOYEE);
    }

    /**
     * Give the user the manager role.
     */
    public function manager(): static
    {
        return $this->withRole(Role::MANAGER);
    }

    /**
     * Give the user the admin role.
     */
    public function admin(): static
    {
        return $this->withRole(Role::ADMIN);
    }

    /**
     * Put the user in the given department, e.g. inDepartment(Department::IT).
     */
    public function inDepartment(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'department_id' => Department::where('name', $name)->value('id'),
        ]);
    }

    private function withRole(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::where('name', $name)->value('id'),
        ]);
    }
}
