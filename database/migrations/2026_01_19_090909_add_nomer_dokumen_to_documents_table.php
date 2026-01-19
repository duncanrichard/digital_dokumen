<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // simpan 001/002/025 dst
            $table->string('nomer_dokumen', 10)->nullable()->after('sequence');
        });

        /**
         * Backfill dari document_number lama.
         * Ambil angka setelah "/" dan sebelum "/YYYY"
         * Contoh: PKWTT-DM/025/2026  => 025
         * Contoh: PKWTT-DM/025/2026-CLINIC => tetap ambil 025
         */
        DB::statement("
            UPDATE documents
            SET nomer_dokumen = LPAD(
                COALESCE(
                    NULLIF(substring(document_number from '/([0-9]+)/[0-9]{4}'), ''),
                    '0'
                ),
                3,
                '0'
            )
            WHERE nomer_dokumen IS NULL
              AND document_number IS NOT NULL
              AND document_number ~ '/[0-9]+/[0-9]{4}'
        ");
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('nomer_dokumen');
        });
    }
}; 