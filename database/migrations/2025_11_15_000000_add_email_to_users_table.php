<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Jika belum ada kolom email
            $table->string('email', 191)
                ->nullable()          // nullable agar migration aman untuk data lama
                ->unique()
                ->after('username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hapus kolom email jika rollback
            $table->dropUnique('users_email_unique'); // nama index bawaan untuk unique
            $table->dropColumn('email');
        });
    }
};
