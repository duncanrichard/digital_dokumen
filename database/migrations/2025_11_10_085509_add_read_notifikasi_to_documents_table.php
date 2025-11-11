<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // boolean untuk MySQL/SQLite, akan menjadi tinyint(1) di MySQL
            $table->boolean('read_notifikasi')
                  ->default(false)       // 0 = belum dibaca
                  ->after('is_active');   // sesuaikan posisi jika perlu
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('read_notifikasi');
        });
    }
};
