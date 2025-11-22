<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_access_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // User yang meminta akses
            $table->uuid('user_id'); // relasi -> users.id
            
            // Dokumen yang diminta
            $table->uuid('document_id'); // relasi -> documents.id

            // Alasan user
            $table->text('reason')->nullable();

            // Status request
            // pending | approved | rejected
            $table->string('status', 20)->default('pending');

            // Admin (Superadmin) yang memutuskan
            $table->uuid('decided_by')->nullable(); // relasi -> users.id

            // Waktu diputuskan
            $table->timestamp('decided_at')->nullable();

            // Jika APPROVED bisa memiliki masa berlaku
            $table->timestamp('expires_at')->nullable();

            // Timestamps
            $table->timestamps();

            // -----------------------
            // Foreign keys
            // -----------------------
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');

            $table->foreign('document_id')
                ->references('id')->on('documents')
                ->onDelete('cascade');

            $table->foreign('decided_by')
                ->references('id')->on('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_access_requests');
    }
};
