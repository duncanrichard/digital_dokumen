<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class FrameworkController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();

            if (!$user) {
                abort(401);
            }

            $roleName     = optional($user->role)->name;
            $isSuperadmin = $roleName && strcasecmp($roleName, 'Superadmin') === 0;

            // Superadmin bebas
            if ($isSuperadmin) {
                return $next($request);
            }

            $required = 'system.framework.view';

            if (! $user->role || ! $user->role->hasPermissionTo($required)) {
                abort(403, 'Anda tidak memiliki izin membuka halaman Framework System.');
            }

            return $next($request);
        });
    }

    public function index()
    {
        $svgPath = 'framwork/Flow 2.drawio.svg';

        if (!Storage::disk('public')->exists($svgPath)) {
            abort(404, 'Diagram framework belum ditemukan.');
        }

        return view('system.framework', [
            'svgPath' => $svgPath,
        ]);
    }
}
