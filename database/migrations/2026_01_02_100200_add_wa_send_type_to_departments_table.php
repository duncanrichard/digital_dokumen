<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            // enum: group/personal
            $table->enum('wa_send_type', ['group', 'personal'])
                  ->default('personal')
                  ->after('no_wa');
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('wa_send_type');
        });
    }
};
