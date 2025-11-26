<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct()
    {
        // Middleware permission per method
        $this->middleware(function ($request, $next) {
            $user = auth()->user();

            if (! $user) {
                abort(401);
            }

            $roleName     = optional($user->role)->name;
            $isSuperadmin = $roleName && strcasecmp($roleName, 'Superadmin') === 0;

            // Superadmin bebas
            if ($isSuperadmin) {
                return $next($request);
            }

            $method = $request->route()->getActionMethod();

            $requiredPermission = match ($method) {
                'index'  => 'access.roles.view',
                'store'  => 'access.roles.create',
                'update' => 'access.roles.update',
                'destroy'=> 'access.roles.delete',
                default  => 'access.roles.view',
            };

            if (! $user->role || ! $user->role->hasPermissionTo($requiredPermission)) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses fitur ini.');
            }

            return $next($request);
        });
    }

    /**
     * Tampilkan daftar role.
     */
    public function index(Request $request)
    {
        $roles = Role::orderBy('name')->paginate(10);

        $editRole = null;
        if ($request->filled('edit')) {
            $editRole = Role::findOrFail($request->input('edit'));
        }

        return view('access.roles.index', compact('roles', 'editRole'));
    }

    /**
     * Simpan role baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255|unique:roles,name',
            'guard_name' => 'nullable|string|max:255',
        ]);

        if (empty($validated['guard_name'])) {
            $validated['guard_name'] = 'web';
        }

        Role::create($validated);

        return redirect()
            ->route('access.roles.index')
            ->with('success', 'Role berhasil ditambahkan.');
    }

    /**
     * Update role.
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255|unique:roles,name,' . $role->id,
            'guard_name' => 'nullable|string|max:255',
        ]);

        if (empty($validated['guard_name'])) {
            $validated['guard_name'] = 'web';
        }

        $role->update($validated);

        return redirect()
            ->route('access.roles.index', ['edit' => $role->id])
            ->with('success', 'Role berhasil diperbarui.');
    }

    /**
     * Hapus role.
     */
    public function destroy(Role $role)
    {
        // opsional: cegah hapus Superadmin
        if (strcasecmp($role->name, 'Superadmin') === 0) {
            return redirect()
                ->route('access.roles.index')
                ->with('success', 'Role "Superadmin" tidak boleh dihapus.');
        }

        $role->delete();

        return redirect()
            ->route('access.roles.index')
            ->with('success', 'Role berhasil dihapus.');
    }
}
