<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Moves every @gmail.com user email (including users in the bin) to @fakemail.com.
     */
    public function up(): void
    {
        $this->changeDomain('gmail.com', 'fakemail.com');
    }

    /**
     * Reverse the migrations.
     *
     * Moves every @fakemail.com email back to @gmail.com.
     */
    public function down(): void
    {
        $this->changeDomain('fakemail.com', 'gmail.com');
    }

    private function changeDomain(string $from, string $to): void
    {
        DB::transaction(function () use ($from, $to) {
            $users = DB::table('users')->where('email', 'like', "%@{$from}")->get(['id', 'email']);

            foreach ($users as $user) {
                $email = substr($user->email, 0, -strlen($from)).$to;

                // Stop (and roll back everything) rather than hit the unique index halfway.
                if (DB::table('users')->where('email', $email)->exists()) {
                    throw new RuntimeException("Cannot change {$user->email}: {$email} is already taken.");
                }

                DB::table('users')->where('id', $user->id)->update(['email' => $email]);
            }
        });
    }
};
