<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1️⃣ Update data lama ke format baru
        DB::statement("
            UPDATE document_access_requests
            SET status = UPPER(status)
        ");

        // pastikan tidak ada typo
        DB::statement("
            UPDATE document_access_requests
            SET status = 'PENDING'
            WHERE status NOT IN ('PENDING','APPROVED','REJECTED')
        ");

        // 2️⃣ Drop constraint lama kalau ada
        DB::statement("
            ALTER TABLE document_access_requests
            DROP CONSTRAINT IF EXISTS document_access_requests_status_check
        ");

        // 3️⃣ Tambahkan CHECK constraint baru
        DB::statement("
            ALTER TABLE document_access_requests
            ADD CONSTRAINT document_access_requests_status_check
            CHECK (status IN ('PENDING','APPROVED','REJECTED'))
        ");

        // 4️⃣ Set default baru
        DB::statement("
            ALTER TABLE document_access_requests
            ALTER COLUMN status SET DEFAULT 'PENDING'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE document_access_requests
            DROP CONSTRAINT IF EXISTS document_access_requests_status_check
        ");

        DB::statement("
            ALTER TABLE document_access_requests
            ALTER COLUMN status SET DEFAULT 'pending'
        ");
    }
};
