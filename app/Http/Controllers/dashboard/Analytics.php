<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;

class Analytics extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();

            if (!$user) {
                abort(401);
            }

            // ==============================
            // 1. CEK SUPERADMIN DARI role_id
            // ==============================
            $roleName     = optional($user->role)->name; // relasi role() di model User
            $isSuperadmin = $roleName && strcasecmp($roleName, 'Superadmin') === 0;

            if ($isSuperadmin) {
                // Superadmin bebas akses
                return $next($request);
            }

            // ========================================
            // 2. USER BIASA → CEK PERMISSION DARI ROLE
            // ========================================
            $role = $user->role; // App\Models\Role (extends Spatie Role)

            $hasPermission = $role && $role->hasPermissionTo('dashboard.analytics.view');

            if (!$hasPermission) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses Dashboard Analytics.');
            }

            return $next($request);
        });
    }

    public function index()
    {
        return view('content.dashboard.dashboards-analytics');
    }
}
