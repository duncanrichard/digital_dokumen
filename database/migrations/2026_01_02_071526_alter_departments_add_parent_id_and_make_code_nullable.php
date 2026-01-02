<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            // code jadi nullable (agar detail/cabang tidak wajib punya code)
            $table->string('code', 20)->nullable()->change();

            // parent_id untuk relasi divisi utama -> detail/cabang
            if (!Schema::hasColumn('departments', 'parent_id')) {
                $table->uuid('parent_id')->nullable()->after('id');
                $table->foreign('parent_id')
                    ->references('id')->on('departments')
                    ->nullOnDelete();
            }

            // index untuk query cepat
            $table->index(['office_type', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (Schema::hasColumn('departments', 'parent_id')) {
                $table->dropForeign(['parent_id']);
                $table->dropColumn('parent_id');
            }
            $table->string('code', 20)->nullable(false)->change();
        });
    }
};
