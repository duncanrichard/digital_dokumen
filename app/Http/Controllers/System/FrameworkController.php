<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class FrameworkController extends Controller
{
    public function index()
    {
        // path relatif di disk "public"
        $svgPath = 'framwork/Flow 2.drawio.svg';

        // optional: cek kalau filenya benar-benar ada
        if (!Storage::disk('public')->exists($svgPath)) {
            abort(404, 'Diagram framework belum ditemukan.');
        }

        return view('system.framework', [
            'svgPath' => $svgPath,
        ]);
    }
}
