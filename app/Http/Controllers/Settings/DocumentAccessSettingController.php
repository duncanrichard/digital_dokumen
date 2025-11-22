<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DocumentAccessSetting;
use Illuminate\Support\Facades\Auth;

class DocumentAccessSettingController extends Controller
{
    protected function ensureSuperadmin(): void
    {
        $user = Auth::user();
        if (!$user || !$user->role || $user->role->name !== 'Superadmin') {
            abort(403, 'Only Superadmin can access this page.');
        }
    }

    public function index()
    {
        $this->ensureSuperadmin();

        $setting = DocumentAccessSetting::first() ?? new DocumentAccessSetting([
            'enabled' => true,
            'default_duration_minutes' => 60 * 24,
        ]);

        return view('settings.document-access', compact('setting'));
    }

    public function update(Request $request)
    {
        $this->ensureSuperadmin();

        $data = $request->validate([
            'enabled'                 => ['nullable', 'in:1'],
            'default_duration_minutes'=> ['required', 'integer', 'min:1'],
        ]);

        $setting = DocumentAccessSetting::first() ?? new DocumentAccessSetting();
        $setting->enabled = $request->boolean('enabled');
        $setting->default_duration_minutes = $data['default_duration_minutes'];
        $setting->save();

        return redirect()
            ->route('settings.document-access')
            ->with('success', 'Document access setting updated.');
    }
}
