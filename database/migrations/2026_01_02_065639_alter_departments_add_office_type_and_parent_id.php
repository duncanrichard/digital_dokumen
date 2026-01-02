<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {

            // ✅ office_type (holding/djc)
            if (!Schema::hasColumn('departments', 'office_type')) {
                $table->string('office_type', 20)->default('holding')->index()->after('id');
            }

            // ✅ parent_id untuk detail divisi
            if (!Schema::hasColumn('departments', 'parent_id')) {
                $table->uuid('parent_id')->nullable()->index()->after('office_type');
            }
        });

        // Foreign key dipisah agar aman di beberapa DB
        Schema::table('departments', function (Blueprint $table) {
            if (Schema::hasColumn('departments', 'parent_id')) {
                // Cegah double foreign jika sudah ada
                // (Laravel tidak punya hasForeignKey bawaan, jadi kita pasang langsung)
                $table->foreign('parent_id')
                    ->references('id')
                    ->on('departments')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (Schema::hasColumn('departments', 'parent_id')) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            }

            if (Schema::hasColumn('departments', 'office_type')) {
                $table->dropColumn('office_type');
            }
        });
    }
};
