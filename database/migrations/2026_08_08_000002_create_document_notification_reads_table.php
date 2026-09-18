<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_notification_reads', function (Blueprint $table) {
            $table->uuid('document_id');
            $table->uuid('user_id');
            $table->timestamp('read_at')->useCurrent();
            $table->primary(['document_id', 'user_id']);
            $table->foreign('document_id')->references('id')->on('documents')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_notification_reads');
    }
};
