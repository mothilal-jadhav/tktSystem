<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Requester and agent describe someone's part in a ticket, not who they are,
     * so user roles become organisational: employee, manager and admin.
     *
     * Rows are renamed in place, so role ids (and users.role_id) don't change.
     */
    private const RENAMES = [
        'requester' => 'employee',
        'agent' => 'manager',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::RENAMES as $from => $to) {
            DB::table('roles')->where('name', $from)->update(['name' => $to, 'updated_at' => now()]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::RENAMES as $from => $to) {
            DB::table('roles')->where('name', $to)->update(['name' => $from, 'updated_at' => now()]);
        }
    }
};
