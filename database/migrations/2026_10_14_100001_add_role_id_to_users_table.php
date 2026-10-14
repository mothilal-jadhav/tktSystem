<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Restricted so a role can't be deleted while users still have it.
            $table->foreignId('role_id')->nullable()->after('password')->constrained()->restrictOnDelete();
        });

        // Users that exist before roles are introduced keep full access as admins.
        DB::table('users')->update([
            'role_id' => DB::table('roles')->where('name', 'admin')->value('id'),
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });
    }
};
