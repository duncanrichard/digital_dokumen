<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Postgres menamai constraint sesuai error: documents_document_number_unique
        // Aman kalau beda environment: kita drop via raw SQL jika ada.
        DB::statement('ALTER TABLE "documents" DROP CONSTRAINT IF EXISTS documents_document_number_unique');

        Schema::table('documents', function (Blueprint $table) {
            // Pastikan kolom revision ada & default 0 (opsional jika belum)
            if (!Schema::hasColumn('documents', 'revision')) {
                $table->unsignedInteger('revision')->default(0)->after('sequence');
            }

            // Unique gabungan nomor + revisi
            $table->unique(['document_number', 'revision'], 'documents_docnum_rev_unique');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropUnique('documents_docnum_rev_unique');
        });

        // Kembalikan unique lama (single)
        DB::statement('ALTER TABLE "documents" ADD CONSTRAINT documents_document_number_unique UNIQUE ("document_number")');
    }
};
