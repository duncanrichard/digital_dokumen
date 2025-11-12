<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    /**
     * Menampilkan daftar user dengan filter.
     */
    public function index(Request $request)
    {
        $q            = trim((string) $request->get('q'));
        $filterDeptId = $request->get('department_id');
        $filterStatus = $request->get('status'); // '1' | '0' | null

        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $items = User::with('department:id,code,name')
            ->search($q)                                 // scopeSearch (lihat model)
            ->when($filterDeptId, function ($qb) use ($filterDeptId) {
                $qb->where('department_id', $filterDeptId);
            })
            ->status($filterStatus)                      // scopeStatus (lihat model)
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('access.users.index', compact(
            'items', 'departments', 'q', 'filterDeptId', 'filterStatus'
        ));
    }

    /**
     * Menyimpan user baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'username'      => ['required', 'string', 'max:150', 'unique:users,username'],
            // jika ingin ketat: 'uuid' — bisa dihapus kalau pakai tipe lain di DB
            'department_id' => ['nullable', 'exists:departments,id'],
            'password'      => ['required', 'confirmed', Rules\Password::defaults()],
            // checkbox "1" saat dicentang; biarkan nullable agar unchecked tidak error
            'is_active'     => ['nullable', 'in:1'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            User::create([
                'name'          => $validated['name'],
                'username'      => $validated['username'],
                'department_id' => $validated['department_id'] ?? null,
                // password akan di-hash oleh mutator di Model User
                'password'      => $validated['password'],
                // PENTING: checkbox unchecked → tidak terkirim → harus jadi false
                'is_active'     => $request->has('is_active'),
            ]);
        });

        return redirect()->route('access.users.index')
            ->with('success', 'User successfully created.');
    }

    /**
     * Update data user.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'username'      => ['required', 'string', 'max:150', 'unique:users,username,' . $user->getKey()],
            'department_id' => ['nullable', 'exists:departments,id'],
            'password'      => ['nullable', 'confirmed', Rules\Password::defaults()],
            'is_active'     => ['nullable', 'in:1'],
        ]);

        DB::transaction(function () use ($validated, $request, $user) {
            $payload = [
                'name'          => $validated['name'],
                'username'      => $validated['username'],
                'department_id' => $validated['department_id'] ?? null,
                // PENTING: perbaikan status
                'is_active'     => $request->has('is_active'),
            ];

            // Hanya update password jika diisi; mutator di model akan meng-hash
            if (!empty($validated['password'])) {
                $payload['password'] = $validated['password'];
            }

            $user->update($payload);
        });

        return redirect()->route('access.users.index')
            ->with('success', 'User successfully updated.');
    }

    /**
     * Hapus user.
     */
    public function destroy(User $user)
    {
        DB::transaction(function () use ($user) {
            $user->delete();
        });

        return redirect()->route('access.users.index')
            ->with('success', 'User successfully deleted.');
    }
}
