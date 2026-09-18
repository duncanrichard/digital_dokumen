<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ubah semua status existing jadi uppercase yang valid
        DB::statement("UPDATE document_access_requests SET status = UPPER(status)");
        DB::statement("
            UPDATE document_access_requests
            SET status = 'PENDING'
            WHERE status NOT IN ('PENDING','APPROVED','REJECTED')
        ");

        // Set default uppercase
        DB::statement("
            ALTER TABLE document_access_requests
            ALTER COLUMN status SET DEFAULT 'PENDING'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE document_access_requests
            ALTER COLUMN status SET DEFAULT 'pending'
        ");
    }
};
