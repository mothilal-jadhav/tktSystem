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
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // The organisation's departments are fixed reference data, so they are created here rather than in a seeder.
        DB::table('departments')->insert(
            collect(['HR', 'Sales', 'IT', 'Wellness', 'Claims', 'Endorsement'])
                ->map(fn (string $name) => ['name' => $name, 'created_at' => now(), 'updated_at' => now()])
                ->all()
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
