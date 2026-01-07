<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            // token Fonnte untuk WA group (nullable, hanya wajib jika wa_send_type = group)
            $table->string('fonnte_token', 255)->nullable()->after('wa_send_type');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('fonnte_token');
        });
    }
};
