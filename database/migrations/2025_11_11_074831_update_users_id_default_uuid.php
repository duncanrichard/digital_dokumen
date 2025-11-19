<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Jalankan perubahan.
     */
    public function up(): void
    {
        // Pastikan ekstensi pgcrypto aktif (dibutuhkan untuk gen_random_uuid)
        DB::statement('CREATE EXTENSION IF NOT EXISTS pgcrypto;');

        // Ubah kolom id agar punya default value UUID otomatis
        DB::statement('ALTER TABLE users ALTER COLUMN id SET DEFAULT gen_random_uuid();');
    }

    /**
     * Kembalikan perubahan.
     */
    public function down(): void
    {
        // Hapus default-nya (tidak hapus extension)
        DB::statement('ALTER TABLE users ALTER COLUMN id DROP DEFAULT;');
    }
};
