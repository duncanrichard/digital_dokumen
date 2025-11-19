<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // angka revisi: 0 = original, 1 = revisi pertama, dst
            $table->unsignedInteger('revision')->default(0)->after('document_number');
            // indeks gabungan untuk akses cepat saat hitung revisi
            $table->index(['document_number', 'revision']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['document_number', 'revision']);
            $table->dropColumn('revision');
        });
    }
};
