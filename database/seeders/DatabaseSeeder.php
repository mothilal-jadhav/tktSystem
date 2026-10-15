<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public const DEMO_ADMIN_EMAIL = 'test@example.com';

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Demo data only. The demo admin's password is the factory's known
        // "password", so it must never exist in production. Create the first
        // production admin separately, with a strong password.
        if (app()->isProduction()) {
            $this->command->warn('Demo users are not created in production.');

            return;
        }

        // Safe to run again: users in the bin keep their email, so check them too.
        if (! User::withTrashed()->where('email', self::DEMO_ADMIN_EMAIL)->exists()) {
            User::factory()->admin()->create([
                'name' => 'Test User',
                'email' => self::DEMO_ADMIN_EMAIL,
            ]);
        }

        $this->call(DepartmentEmployeeSeeder::class);
    }
}
