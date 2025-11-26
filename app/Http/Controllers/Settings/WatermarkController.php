<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WatermarkSetting;

class WatermarkController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();

            if (! $user) {
                abort(401);
            }

            $roleName     = optional($user->role)->name;
            $isSuperadmin = $roleName && strcasecmp($roleName, 'Superadmin') === 0;

            // Superadmin bypass semua permission
            if ($isSuperadmin) {
                return $next($request);
            }

            $method = $request->route()->getActionMethod();

            $requiredPermission = match ($method) {
                'index'  => 'settings.watermark.view',
                'update' => 'settings.watermark.update',
                default  => 'settings.watermark.view',
            };

            if (! $user->role || ! $user->role->hasPermissionTo($requiredPermission)) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses fitur ini.');
            }

            return $next($request);
        });
    }

    public function index()
    {
        $setting = WatermarkSetting::query()->first();

        if (! $setting) {
            $setting = new WatermarkSetting(); // default kosong
        }

        return view('content.settings.watermark', compact('setting'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'enabled'          => ['nullable','boolean'],
            'mode'             => ['required','in:text,image'],
            'text_template'    => ['nullable','string','max:255'],
            'font_size'        => ['required','integer','min:8','max:120'],
            'rotation'         => ['required','integer','min:-180','max:180'],
            'opacity'          => ['required','integer','min:0','max:100'],
            'position'         => ['required','in:center,top-left,top-right,bottom-left,bottom-right'],
            'repeat'           => ['nullable','boolean'],
            'color_hex'        => ['required','regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/'],
            'image'            => ['nullable','image','mimes:png,jpg,jpeg','max:2048'],
            'show_on_download' => ['nullable','boolean'],
        ], [
            'color_hex.regex' => 'Format warna harus hex, mis. #A0A0A0 atau #A0A0A0CC',
        ]);

        $setting = WatermarkSetting::query()->first() ?? new WatermarkSetting();

        // upload image jika mode image & file dikirim
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('watermarks', 'public');
            $validated['image_path'] = 'storage/' . $path;
        }

        $setting->fill([
            'enabled'          => (bool) $request->boolean('enabled'),
            'mode'             => $validated['mode'],
            'text_template'    => $validated['mode'] === 'text'
                                    ? ($validated['text_template'] ?? '')
                                    : null,
            'font_size'        => $validated['font_size'],
            'rotation'         => $validated['rotation'],
            'opacity'          => $validated['opacity'],
            'position'         => $validated['position'],
            'repeat'           => (bool) $request->boolean('repeat'),
            'color_hex'        => $validated['color_hex'],
            'show_on_download' => (bool) $request->boolean('show_on_download'),
            'image_path'       => $validated['mode'] === 'image'
                                    ? ($validated['image_path'] ?? $setting->image_path)
                                    : null,
        ])->save();

        return back()->with('success', 'Watermark settings updated.');
    }
}
