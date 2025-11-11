<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('jenis_dokumen', function (Blueprint $table) {
            // Primary UUID key
            $table->uuid('id')->primary();

            $table->string('kode', 20)->unique();   // e.g., SK, IN, OUT
            $table->string('nama', 100);            // e.g., Surat Keputusan
            $table->text('deskripsi')->nullable();  // optional description
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jenis_dokumen');
    }
};
