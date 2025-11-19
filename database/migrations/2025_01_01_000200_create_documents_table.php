<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // relasi ke jenis_dokumen & departments (uuid)
            $table->uuid('jenis_dokumen_id');
            $table->uuid('department_id');

            // nomor urut dan nomor dokumen jadi satuan kontrol
            $table->unsignedBigInteger('sequence'); // nomor urut per (jenis_dokumen_id, department_id)
            $table->string('document_number', 190)->unique(); // ex: SOP-ENG/1/2025

            // metadata
            $table->string('name', 255);
            $table->date('publish_date');

            // file path (disk: public)
            $table->string('file_path', 255);

            $table->timestamps();

            $table->index(['jenis_dokumen_id', 'department_id']);
            $table->foreign('jenis_dokumen_id')->references('id')->on('jenis_dokumen')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
