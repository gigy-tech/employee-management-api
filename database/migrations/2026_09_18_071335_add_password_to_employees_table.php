<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('employees', 'password_set')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->boolean('password_set')->default(false)->after('password');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('employees', 'password_set')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('password_set');
            });
        }
    }
};