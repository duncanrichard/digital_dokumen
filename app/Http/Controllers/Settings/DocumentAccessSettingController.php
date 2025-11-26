<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DocumentAccessSetting;

class DocumentAccessSettingController extends Controller
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

            // Superadmin bypass semua
            if ($isSuperadmin) {
                return $next($request);
            }

            $method = $request->route()->getActionMethod();

            $requiredPermission = match ($method) {
                'index'  => 'settings.document-access.view',
                'update' => 'settings.document-access.update',
                default  => 'settings.document-access.view',
            };

            if (! $user->role || ! $user->role->hasPermissionTo($requiredPermission)) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses pengaturan ini.');
            }

            return $next($request);
        });
    }

    public function index()
    {
        $setting = DocumentAccessSetting::first() ?? new DocumentAccessSetting([
            'enabled'                  => true,
            'default_duration_minutes' => 60 * 24, // default 1 hari
        ]);

        return view('settings.document-access', compact('setting'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'enabled'                   => ['nullable', 'boolean'],
            'default_duration_minutes'  => ['required', 'integer', 'min:1'],
        ]);

        $setting = DocumentAccessSetting::first() ?? new DocumentAccessSetting();

        $setting->enabled                  = $request->boolean('enabled');
        $setting->default_duration_minutes = $data['default_duration_minutes'];
        $setting->save();

        return redirect()
            ->route('settings.document-access.index')
            ->with('success', 'Document access setting updated.');
    }
}
