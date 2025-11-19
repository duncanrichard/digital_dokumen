<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('document_distributions', function (Blueprint $table) {
            // Drop primary key constraint if exists
            $table->dropPrimary();
            // Drop id column
            $table->dropColumn('id');
        });

        // Add composite primary key (document_id + department_id)
        DB::statement('ALTER TABLE document_distributions ADD PRIMARY KEY (document_id, department_id)');
    }

    public function down(): void
    {
        Schema::table('document_distributions', function (Blueprint $table) {
            $table->uuid('id')->primary();
        });
    }
};
