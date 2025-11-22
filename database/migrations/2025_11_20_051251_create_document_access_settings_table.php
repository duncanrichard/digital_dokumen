<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_access_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(true);              // pakai expiry atau tidak
            $table->unsignedInteger('default_duration_minutes')     // lama akses default
                  ->default(60 * 24); // 1 hari
            $table->timestamps();
        });

        // seed 1 record default
        DB::table('document_access_settings')->insert([
            'enabled'                 => true,
            'default_duration_minutes'=> 60 * 24, // 1 hari
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('document_access_settings');
    }
};
