<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WatermarkSetting extends Model
{
    protected $fillable = [
        'enabled',          // bool
        'mode',             // 'text' | 'image'
        'text_template',    // e.g. 'CONFIDENTIAL - {user.name} - {date}'
        'font_size',        // int
        'rotation',         // int (deg)
        'opacity',          // 0..100 (visual; kita pakai warna abu2 sbg pengganti)
        'position',         // 'center'|'top-left'|'top-right'|'bottom-left'|'bottom-right'
        'repeat',           // bool (ulang di beberapa posisi diagonal)
        'color_hex',        // e.g. '#A0A0A0'
        'image_path',       // path watermark image (optional, if mode=image)
        'show_on_download', // bool (kalau kamu juga pakai route khusus download)
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'repeat'  => 'boolean',
        'show_on_download' => 'boolean',
        'font_size' => 'integer',
        'rotation'  => 'integer',
        'opacity'   => 'integer',
    ];
}
