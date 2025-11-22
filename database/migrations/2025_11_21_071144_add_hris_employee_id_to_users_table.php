<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // id employee dari HRIS (MySQL)
            $table->unsignedBigInteger('hris_employee_id')
                ->nullable()
                ->after('id'); // atau after field lain sesuka kamu

            // optional: index biar query lebih cepat
            $table->index('hris_employee_id', 'users_hris_employee_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_hris_employee_id_index');
            $table->dropColumn('hris_employee_id');
        });
    }
};
