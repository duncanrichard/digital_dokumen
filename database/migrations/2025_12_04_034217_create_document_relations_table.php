<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_relations', function (Blueprint $table) {
            $table->uuid('parent_document_id'); // dokumen asal (yang diubah)
            $table->uuid('child_document_id');  // dokumen baru (hasil perubahan)
            $table->string('relation_type')->default('changed_to');
            $table->timestamps();

            $table->primary(['parent_document_id', 'child_document_id']);

            $table->foreign('parent_document_id')
                ->references('id')->on('documents')
                ->cascadeOnDelete();

            $table->foreign('child_document_id')
                ->references('id')->on('documents')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_relations');
    }
};
