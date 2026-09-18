<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Rapikan data lama (kalau ada yang lowercase / typo)
        DB::statement("
            UPDATE document_access_requests
            SET status = UPPER(status)
            WHERE status IS NOT NULL
        ");

        // Kalau ada typo 'RIJECTED' -> 'REJECTED'
        DB::statement("
            UPDATE document_access_requests
            SET status = 'REJECTED'
            WHERE status = 'RIJECTED'
        ");

        // Drop constraint lama (nama sesuai error kamu)
        DB::statement("
            ALTER TABLE document_access_requests
            DROP CONSTRAINT IF EXISTS document_access_requests_status_check
        ");

        // Buat constraint baru: hanya boleh 3 value ini
        DB::statement("
            ALTER TABLE document_access_requests
            ADD CONSTRAINT document_access_requests_status_check
            CHECK (status IN ('PENDING','APPROVED','REJECTED'))
        ");

        // Set default PENDING (opsional tapi recommended)
        DB::statement("
            ALTER TABLE document_access_requests
            ALTER COLUMN status SET DEFAULT 'PENDING'
        ");
    }

    public function down(): void
    {
        // Balikkan default (opsional)
        DB::statement("
            ALTER TABLE document_access_requests
            ALTER COLUMN status DROP DEFAULT
        ");

        DB::statement("
            ALTER TABLE document_access_requests
            DROP CONSTRAINT IF EXISTS document_access_requests_status_check
        ");

        // (Kalau mau, kamu bisa bikin constraint versi lama di sini)
    }
};
