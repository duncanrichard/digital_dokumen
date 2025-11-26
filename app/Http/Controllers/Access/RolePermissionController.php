<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionController extends Controller
{
    public function __construct()
    {
        // Hanya Superadmin (berdasarkan relasi role_id) yang boleh akses
        $this->middleware(function ($request, $next) {
            $user = auth()->user();

            if (! $user) {
                abort(401);
            }

            $roleName     = optional($user->role)->name;
            $isSuperadmin = $roleName && strcasecmp($roleName, 'Superadmin') === 0;

            if (! $isSuperadmin) {
                abort(403, 'Anda tidak memiliki akses ke pengaturan permission.');
            }

            return $next($request);
        });
    }

    /**
     * Tampilkan halaman setting permission per role.
     */
    public function index(Request $request)
    {
        $roles       = Role::orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();

        // GROUPING PERMISSION → MENENTUKAN MENU
        $groupedPermissions = $permissions->groupBy(function ($perm) {
            $name = $perm->name;

            // =========
            // DOKUMEN
            // =========

            // Access / approvals (dipisah menu sendiri)
            if (Str::startsWith($name, 'documents.access-approvals.')) {
                return 'Document Access / Approvals';
            }

            if (Str::startsWith($name, 'documents.distribution.')) {
                return 'Document Distribution';
            }

            if (Str::startsWith($name, 'documents.revisions.')) {
                return 'Document Revisions';
            }

            if (Str::startsWith($name, 'documents.upload.')) {
                return 'Dokumen Library';
            }

            // Fallback permission dokumen lain
            if (Str::startsWith($name, 'documents.')) {
                return 'Dokumen Library';
            }

            // =========
            // MASTER DATA (DIPISAH PER MENU)
            // =========

            if (Str::startsWith($name, 'master.departments.')) {
                return 'Master Data - Divisi';
            }

            if (Str::startsWith($name, 'master.jenis-dokumen.')) {
                return 'Master Data - Jenis Dokumen';
            }

            // fallback master lain
            if (Str::startsWith($name, 'master.')) {
                return 'Master Data - Lainnya';
            }

            // =========
            // USER ACCESS (DIPISAH PER MENU)
            // =========

            if (Str::startsWith($name, 'access.roles.')) {
                return 'User Access - Roles';
            }

            if (Str::startsWith($name, 'access.users.')) {
                return 'User Access - Users';
            }

            if (Str::startsWith($name, 'access.permissions.')) {
                return 'User Access - Permissions';
            }

            if (Str::startsWith($name, 'access.')) {
                return 'User Access - Lainnya';
            }

            // =========
            // SETTINGS (DIPISAH PER FITUR)
            // =========

            if (Str::startsWith($name, 'settings.document-access.')) {
                return 'Settings - Document Access';
            }

            if (Str::startsWith($name, 'settings.watermark.')) {
                return 'Settings - Watermark / DRM';
            }

            // fallback settings lain
            if (Str::startsWith($name, 'settings.')) {
                return 'Settings - Lainnya';
            }

            // =========
            // SYSTEM
            // =========
            if (Str::startsWith($name, 'system.')) {
                return 'System Framework';
            }

            // =========
            // LAINNYA
            // =========
            if (Str::startsWith($name, 'dashboard.')) {
                return 'Dashboard';
            }

            return 'Lainnya';
        });

        // Role yang dipilih
        $selectedRoleId = $request->query('role_id', $roles->first()->id ?? null);
        $selectedRole   = $selectedRoleId
            ? $roles->firstWhere('id', $selectedRoleId)
            : null;

        $assignedPermissions = $selectedRole
            ? $selectedRole->permissions->pluck('name')->toArray()
            : [];

        return view('access.permissions.index', [
            'roles'               => $roles,
            'groupedPermissions'  => $groupedPermissions,
            'selectedRole'        => $selectedRole,
            'selectedRoleId'      => $selectedRoleId,
            'assignedPermissions' => $assignedPermissions,
        ]);
    }

    /**
     * Update permission untuk 1 role.
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'role_id'       => ['required', 'uuid', 'exists:roles,id'],
            'permissions'   => ['array'],
            'permissions.*' => ['string'],
        ], [], [
            'role_id' => 'Role',
        ]);

        /** @var \App\Models\Role $role */
        $role = Role::findOrFail($data['role_id']);

        $permissionNames = $data['permissions'] ?? [];

        // Sinkronisasi permission ke role
        $role->syncPermissions($permissionNames);

        // reset cache permission Spatie
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('access.permissions.index', ['role_id' => $role->id])
            ->with('success', 'Permission untuk role "' . $role->name . '" berhasil diperbarui.');
    }
}
