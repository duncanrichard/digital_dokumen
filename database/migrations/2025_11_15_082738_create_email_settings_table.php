<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Default');
            $table->string('host');
            $table->unsignedSmallInteger('port')->default(587);
            $table->string('encryption', 10)->nullable(); // tls / ssl / null
            $table->string('username');
            $table->string('password');
            $table->string('from_address');
            $table->string('from_name')->nullable();
            $table->boolean('is_active')->default(false); // status aktif / non aktif
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_settings');
    }
};
