
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

return new class extends Migration
{
    public function up(): void
    {
        $email = strtolower(trim((string) getenv('ADMIN_EMAIL')));
$password = (string) getenv('ADMIN_PASSWORD');

        if ($email === '' || $password === '') {
            throw new RuntimeException(
                'ADMIN_EMAIL and ADMIN_PASSWORD must be configured.'
            );
        }

        $admin = DB::table('users')
            ->where('email', $email)
            ->where('role', 'admin')
            ->exists();

        if (! $admin) {
            throw new RuntimeException(
                'The configured admin account was not found.'
            );
        }

        DB::table('users')
            ->where('email', $email)
            ->where('role', 'admin')
            ->update([
                'password' => Hash::make($password),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Password reset is intentionally not reversed.
    }
};
