<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_distributions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // relasi ke dokumen & departemen (uuid)
            $table->uuid('document_id');
            $table->uuid('department_id');

            $table->timestamps();

            // FK + cascade
            $table->foreign('document_id')
                ->references('id')->on('documents')
                ->onDelete('cascade');

            $table->foreign('department_id')
                ->references('id')->on('departments')
                ->onDelete('cascade');

            // kombinasi unik: 1 dokumen hanya sekali per departemen
            $table->unique(['document_id', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_distributions');
    }
};
