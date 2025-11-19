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
        Schema::create('users', function (Blueprint $table) {
            // PK UUID
            $table->uuid('id')->primary();

            // Fields utama
            $table->string('name', 150);
            $table->string('username', 100)->unique();
            $table->string('password');

            // Relasi ke departments (UUID) — boleh null
            $table->uuid('department_id')->nullable();

            // Status aktif
            $table->boolean('is_active')->default(true);

            // Token remember (opsional, berguna kalau pakai Auth remember me)
            $table->rememberToken();

            // Timestamps
            $table->timestamps();

            // Foreign key ke departments.id (UUID)
            $table->foreign('department_id')
                ->references('id')->on('departments')
                ->onUpdate('cascade')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
