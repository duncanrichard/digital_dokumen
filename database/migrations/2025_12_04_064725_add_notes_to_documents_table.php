<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // letakkan setelah publish_date (bisa disesuaikan)
            if (!Schema::hasColumn('documents', 'notes')) {
                $table->text('notes')->nullable()->after('publish_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
