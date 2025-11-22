<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
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

        return redirect()->back()->with('success', 'Role berhasil ditambahkan.');
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

        return redirect()->back()->with('success', 'Role berhasil diperbarui.');
    }

    /**
     * Hapus role.
     */
    public function destroy(Role $role)
    {
        $role->delete();

        return redirect()->back()->with('success', 'Role berhasil dihapus.');
    }
}
