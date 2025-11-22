<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Department;
<<<<<<< HEAD
use App\Models\Role;
use App\Models\Hris\Employee; // model employees di koneksi mysql_hris
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
=======
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0

class UserController extends Controller
{
    /**
<<<<<<< HEAD
     * Menampilkan daftar user dengan filter + form create/edit.
     * Data utama disimpan di PostgreSQL (tabel users),
     * tetapi bisa punya link ke karyawan HRIS lewat hris_employee_id.
=======
     * Menampilkan daftar user dengan filter.
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
     */
    public function index(Request $request)
    {
        $q            = trim((string) $request->get('q'));
        $filterDeptId = $request->get('department_id');
        $filterStatus = $request->get('status'); // '1' | '0' | null

<<<<<<< HEAD
        // --- AUTO SYNC DATA HRIS KE USERS SETIAP BUKA HALAMAN ---
        $this->syncHrisUsers();
        // --------------------------------------------------------

        // Departemen dari DB utama (Postgres)
=======
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

<<<<<<< HEAD
        // Roles dari DB utama (Postgres)
        $roles = Role::orderBy('name')
            ->get(['id', 'name']);

        // cek apakah sedang edit user tertentu
        $editUser = null;
        $editingHrisId = null;
        if ($request->filled('edit')) {
            $editUser = User::with([
                    'department:id,code,name',
                    'role:id,name',
                    'hrisEmployee:id,name,email,office_phone',
                ])->findOrFail($request->input('edit'));

            $editingHrisId = $editUser->hris_employee_id;
        }

        // ambil semua hris_employee_id yang sudah dipakai user lain
        $usedHrisIds = User::whereNotNull('hris_employee_id')
            ->when($editingHrisId, function ($q) use ($editingHrisId) {
                // saat edit user HRIS, boleh tetap pakai dirinya sendiri
                $q->where('hris_employee_id', '!=', $editingHrisId);
            })
            ->pluck('hris_employee_id')
            ->toArray();

        // Daftar karyawan HRIS untuk dropdown -> exclude yang sudah dipakai user lain
        $employees = Employee::whereNotIn('id', $usedHrisIds)
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name', 'email', 'office_phone']);

        // Query users dari DB utama, dengan relasi department, role, dan hrisEmployee
        $items = User::with([
                'department:id,code,name',
                'role:id,name',
                'hrisEmployee:id,name,email,office_phone',
            ])
            ->search($q)
            ->when($filterDeptId, function ($qb) use ($filterDeptId) {
                $qb->where('department_id', $filterDeptId);
            })
            ->status($filterStatus)
=======
        $items = User::with('department:id,code,name')
            ->search($q)                                 // scopeSearch (lihat model)
            ->when($filterDeptId, function ($qb) use ($filterDeptId) {
                $qb->where('department_id', $filterDeptId);
            })
            ->status($filterStatus)                      // scopeStatus (lihat model)
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('access.users.index', compact(
<<<<<<< HEAD
            'items',
            'departments',
            'roles',
            'employees',
            'q',
            'filterDeptId',
            'filterStatus',
            'editUser'
=======
            'items', 'departments', 'q', 'filterDeptId', 'filterStatus'
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
        ));
    }

    /**
     * Menyimpan user baru.
<<<<<<< HEAD
     *
     * - Jika diisi hris_employee_id, maka name / username / email / password / nomor_wa
     *   diambil dari tabel employees (HRIS) dan tidak boleh diubah dari form.
     * - Kalau tidak pakai HRIS, semuanya pakai input form.
=======
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
<<<<<<< HEAD
            'hris_employee_id' => [
                'nullable',
                'integer',
                Rule::exists('mysql_hris.employees', 'id'),
            ],

            // kalau pakai HRIS, field2 ini boleh kosong (akan diisi dari HRIS)
            'name'          => ['nullable', 'string', 'max:100'],
            'username'      => ['nullable', 'string', 'max:150'],
            'email'         => ['nullable', 'string', 'email', 'max:191'],
            'nomor_wa'      => ['nullable', 'string', 'max:30'],

            'department_id' => ['nullable', 'exists:departments,id'],
            'role_id'       => ['nullable', 'exists:roles,id'],

            // password Wajib jika TIDAK pakai HRIS
            'password'      => [
                'nullable',
                'confirmed',
                Rules\Password::defaults(),
                'required_without:hris_employee_id',
            ],
=======
            'name'          => ['required', 'string', 'max:100'],
            'username'      => ['required', 'string', 'max:150', 'unique:users,username'],
            'email'         => ['required', 'string', 'email', 'max:191', 'unique:users,email'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'password'      => ['required', 'confirmed', Rules\Password::defaults()],
            // checkbox "1" saat dicentang; biarkan nullable agar unchecked tidak error
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
            'is_active'     => ['nullable', 'in:1'],
        ]);

        DB::transaction(function () use ($validated, $request) {
<<<<<<< HEAD
            $hrisEmployee = null;

            if (!empty($validated['hris_employee_id'])) {
                $hrisEmployee = Employee::find($validated['hris_employee_id']);
            }

            // Default dari input
            $name     = $validated['name']     ?? null;
            $username = $validated['username'] ?? null;
            $email    = $validated['email']    ?? null;
            $nomorWa  = $validated['nomor_wa'] ?? null;
            $password = $validated['password'] ?? null;

            // Jika pakai HRIS → override name/username/email/password/nomor_wa
            if ($hrisEmployee) {
                $name     = $hrisEmployee->name;
                $username = $hrisEmployee->name;           // username diambil dari name HRIS
                $email    = $hrisEmployee->email;
                $nomorWa  = $hrisEmployee->office_phone;   // WA dari HRIS.office_phone
                $password = $hrisEmployee->password;       // password dari tabel HRIS (sudah hash)
            }

            // Pastikan name / username / email / password terisi
            if (!$name || !$username || !$email || !$password) {
                throw ValidationException::withMessages([
                    'hris_employee_id' => [
                        'Nama, username, email, dan password harus terisi, ' .
                        'baik dari HRIS maupun input manual.',
                    ],
                ]);
            }

            // Cek unik username & email berdasarkan nilai akhir (setelah dari HRIS / manual)
            if (User::where('username', $username)->exists()) {
                throw ValidationException::withMessages([
                    'username' => ['Username sudah dipakai oleh user lain.'],
                ]);
            }

            if (User::where('email', $email)->exists()) {
                throw ValidationException::withMessages([
                    'email' => ['Email sudah dipakai oleh user lain.'],
                ]);
            }

            User::create([
                'hris_employee_id' => $validated['hris_employee_id'] ?? null,
                'name'             => $name,
                'username'         => $username,
                'email'            => $email,
                'nomor_wa'         => $nomorWa,
                'department_id'    => $validated['department_id'] ?? null,
                'role_id'          => $validated['role_id'] ?? null,
                'password'         => $password,
                'is_active'        => $request->has('is_active'),
=======
            User::create([
                'name'          => $validated['name'],
                'username'      => $validated['username'],
                'email'         => $validated['email'],
                'department_id' => $validated['department_id'] ?? null,
                // password akan di-hash oleh mutator di Model User
                'password'      => $validated['password'],
                // PENTING: checkbox unchecked → tidak terkirim → harus jadi false
                'is_active'     => $request->has('is_active'),
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
            ]);
        });

        return redirect()->route('access.users.index')
            ->with('success', 'User successfully created.');
    }

    /**
     * Update data user.
<<<<<<< HEAD
     *
     * - Kalau user sudah terhubung HRIS (hris_employee_id tidak null):
     *   name, username, email, password, nomor_wa diambil dari HRIS dan TIDAK bisa diubah di sini.
     *   Yang bisa diubah: role, is_active, department.
     * - Kalau user tidak pakai HRIS:
     *   boleh ubah name/username/email/nomor_wa manual, password opsional.
=======
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
<<<<<<< HEAD
            'hris_employee_id' => [
                'nullable',
                'integer',
                Rule::exists('mysql_hris.employees', 'id'),
            ],
            'name'          => ['nullable', 'string', 'max:100'],
            'username'      => ['nullable', 'string', 'max:150'],
            'email'         => ['nullable', 'string', 'email', 'max:191'],
            'nomor_wa'      => ['nullable', 'string', 'max:30'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'role_id'       => ['nullable', 'exists:roles,id'],
=======
            'name'          => ['required', 'string', 'max:100'],
            'username'      => ['required', 'string', 'max:150', 'unique:users,username,' . $user->getKey()],
            'email'         => ['required', 'string', 'email', 'max:191', 'unique:users,email,' . $user->getKey()],
            'department_id' => ['nullable', 'exists:departments,id'],
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
            'password'      => ['nullable', 'confirmed', Rules\Password::defaults()],
            'is_active'     => ['nullable', 'in:1'],
        ]);

        DB::transaction(function () use ($validated, $request, $user) {
<<<<<<< HEAD
            // kalau user SUDAH HRIS, kita pakai hris_employee_id lama
            $hrisEmployeeId = $user->hris_employee_id ?: ($validated['hris_employee_id'] ?? null);
            $hrisEmployee   = null;

            if (!empty($hrisEmployeeId)) {
                $hrisEmployee = Employee::find($hrisEmployeeId);
            }

            // Default ke nilai lama user
            $name     = $validated['name']     ?? $user->name;
            $username = $validated['username'] ?? $user->username;
            $email    = $validated['email']    ?? $user->email;
            $nomorWa  = $validated['nomor_wa'] ?? $user->nomor_wa;
            $password = null;

            if ($hrisEmployee) {
                // User HRIS → pakai data HRIS, email & password tidak bisa diubah di sini
                $name     = $hrisEmployee->name;
                $username = $hrisEmployee->name;
                $email    = $hrisEmployee->email;
                $nomorWa  = $hrisEmployee->office_phone;
                $password = $hrisEmployee->password; // sinkron dari HRIS
            } else {
                // Non-HRIS → boleh ubah manual, password opsional
                if (!empty($validated['password'])) {
                    $password = $validated['password'];
                }
            }

            if (!$name || !$username || !$email) {
                throw ValidationException::withMessages([
                    'name' => ['Nama, username dan email tidak boleh kosong.'],
                ]);
            }

            // Cek unik username & email berdasarkan nilai akhir (ignore diri sendiri)
            if (User::where('id', '!=', $user->id)->where('username', $username)->exists()) {
                throw ValidationException::withMessages([
                    'username' => ['Username sudah dipakai oleh user lain.'],
                ]);
            }

            if (User::where('id', '!=', $user->id)->where('email', $email)->exists()) {
                throw ValidationException::withMessages([
                    'email' => ['Email sudah dipakai oleh user lain.'],
                ]);
            }

            $payload = [
                'hris_employee_id' => $hrisEmployeeId,
                'name'             => $name,
                'username'         => $username,
                'email'            => $email,
                'nomor_wa'         => $nomorWa,
                'department_id'    => $validated['department_id'] ?? $user->department_id,
                'role_id'          => $validated['role_id'] ?? $user->role_id,
                'is_active'        => $request->has('is_active'),
            ];

            // Hanya set password jika ada nilai baru (HRIS atau manual)
            if ($password !== null) {
                $payload['password'] = $password;
=======
            $payload = [
                'name'          => $validated['name'],
                'username'      => $validated['username'],
                'email'         => $validated['email'],
                'department_id' => $validated['department_id'] ?? null,
                // PENTING: perbaikan status
                'is_active'     => $request->has('is_active'),
            ];

            // Hanya update password jika diisi; mutator di model akan meng-hash
            if (!empty($validated['password'])) {
                $payload['password'] = $validated['password'];
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
            }

            $user->update($payload);
        });

<<<<<<< HEAD
        return redirect()->route('access.users.index', ['edit' => $user->id])
=======
        return redirect()->route('access.users.index')
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
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
<<<<<<< HEAD

    /**
     * Sinkronisasi semua user HRIS dengan data terbaru di tabel employees (HRIS).
     *
     * - Hanya menyentuh user yang punya hris_employee_id.
     * - Field yang disinkronkan: name, username, email, password, nomor_wa.
     * - department_id & role_id TIDAK diubah.
     */
    protected function syncHrisUsers(): void
    {
        // ambil semua user yang terhubung HRIS
        $users = User::whereNotNull('hris_employee_id')->get([
            'id',
            'hris_employee_id',
            'name',
            'username',
            'email',
            'password',
            'nomor_wa',
        ]);

        if ($users->isEmpty()) {
            return;
        }

        $hrisIds = $users->pluck('hris_employee_id')->filter()->unique()->values()->all();

        // ambil semua employee HRIS yg diperlukan
        $employees = Employee::whereIn('id', $hrisIds)->get(['id', 'name', 'email', 'password', 'office_phone']);

        // jadikan map [id => Employee]
        $employeesMap = $employees->keyBy('id');

        foreach ($users as $user) {
            $emp = $employeesMap->get($user->hris_employee_id);
            if (!$emp) {
                // kalau employee sudah hilang di HRIS, skip saja
                continue;
            }

            $newName     = $emp->name;
            $newUsername = $emp->name;          // sesuai aturan: username = name HRIS
            $newEmail    = $emp->email;
            $newPass     = $emp->password;      // diasumsikan sudah hash di HRIS
            $newWa       = $emp->office_phone;

            // cek kalau ada perubahan
            $dirty = false;
            $updateData = [];

            if ($user->name !== $newName) {
                $updateData['name'] = $newName;
                $dirty = true;
            }
            if ($user->username !== $newUsername) {
                $updateData['username'] = $newUsername;
                $dirty = true;
            }
            if ($user->email !== $newEmail) {
                $updateData['email'] = $newEmail;
                $dirty = true;
            }
            if ($user->password !== $newPass) {
                $updateData['password'] = $newPass;
                $dirty = true;
            }
            if ($user->nomor_wa !== $newWa) {
                $updateData['nomor_wa'] = $newWa;
                $dirty = true;
            }

            if ($dirty) {
                $user->update($updateData);
            }
        }
    }
=======
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
}
