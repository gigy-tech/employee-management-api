<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $email = strtolower(trim((string) env('ADMIN_EMAIL')));
        $password = (string) env('ADMIN_PASSWORD');

        if ($email === '' || $password === '') {
            throw new RuntimeException(
                'ADMIN_EMAIL and ADMIN_PASSWORD must be configured.'
            );
        }

        $now = now();

        DB::table('users')->updateOrInsert(
            ['email' => $email],
            [
                'name' => 'System Administrator',
                'password' => Hash::make($password),
                'role' => 'admin',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        // Keep the administrator account when rolling back.
    }
};