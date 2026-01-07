<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // PostgreSQL UUID
            $table->uuid('clinic_id')->nullable()->after('department_id');

            // FK ke clinics(id)
            $table->foreign('clinic_id')
                ->references('id')
                ->on('clinics')
                ->nullOnDelete(); // kalau clinic dihapus, clinic_id jadi null
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['clinic_id']);
            $table->dropColumn('clinic_id');
        });
    }
};
