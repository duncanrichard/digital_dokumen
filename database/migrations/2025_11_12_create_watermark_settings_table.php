<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('watermark_settings', function (Blueprint $table) {
      $table->id();
      $table->boolean('enabled')->default(false);
      $table->enum('mode', ['text','image'])->default('text');
      $table->string('text_template')->nullable();
      $table->unsignedSmallInteger('font_size')->default(28);
      $table->smallInteger('rotation')->default(45);
      $table->unsignedTinyInteger('opacity')->default(30); // informasi visual
      $table->enum('position', ['center','top-left','top-right','bottom-left','bottom-right'])->default('center');
      $table->boolean('repeat')->default(true);
      $table->string('color_hex', 9)->default('#A0A0A0');
      $table->string('image_path')->nullable();
      $table->boolean('show_on_download')->default(true);
      $table->timestamps();
    });

    // Seed default row
    \DB::table('watermark_settings')->insert([
      'enabled' => true,
      'mode' => 'text',
      'text_template' => 'CONFIDENTIAL — {user.name} — {date}',
      'font_size' => 28,
      'rotation' => 45,
      'opacity' => 30,
      'position' => 'center',
      'repeat' => true,
      'color_hex' => '#A0A0A0',
      'show_on_download' => true,
      'created_at' => now(),
      'updated_at' => now(),
    ]);
  }

  public function down(): void
  {
    Schema::dropIfExists('watermark_settings');
  }
};
