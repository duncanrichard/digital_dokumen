<?php

namespace App\Http\Controllers\Access;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Department;
use App\Models\Role;
use App\Models\Hris\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
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

            if ($isSuperadmin) {
                return $next($request);
            }

            $method = $request->route()->getActionMethod();

            $requiredPermission = match ($method) {
                'index'  => 'access.users.view',
                'store'  => 'access.users.create',
                'update' => 'access.users.update',
                'destroy'=> 'access.users.delete',
                default  => 'access.users.view',
            };

            if (! $user->role || ! $user->role->hasPermissionTo($requiredPermission)) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses fitur ini.');
            }

            return $next($request);
        });
    }

    /**
     * Menampilkan daftar user dengan filter + form create/edit.
     */
    public function index(Request $request)
    {
        $q            = trim((string) $request->get('q'));
        $filterDeptId = $request->get('department_id');
        $filterStatus = $request->get('status');

        // auto sync HRIS
        $this->syncHrisUsers();

        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $roles = Role::orderBy('name')->get(['id', 'name']);

        $editUser      = null;
        $editingHrisId = null;
        if ($request->filled('edit')) {
            $editUser = User::with([
                    'department:id,code,name',
                    'role:id,name',
                    'hrisEmployee:id,name,email,office_phone',
                ])->findOrFail($request->input('edit'));

            $editingHrisId = $editUser->hris_employee_id;
        }

        $usedHrisIds = User::whereNotNull('hris_employee_id')
            ->when($editingHrisId, function ($q2) use ($editingHrisId) {
                $q2->where('hris_employee_id', '!=', $editingHrisId);
            })
            ->pluck('hris_employee_id')
            ->toArray();

        $employees = Employee::whereNotIn('id', $usedHrisIds)
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name', 'email', 'office_phone']);

        $items = User::with([
                'department:id,code,name',
                'role:id,name',
                'hrisEmployee:id,name,email,office_phone',
            ])
            ->search($q)
            ->when($filterDeptId, fn($qb) => $qb->where('department_id', $filterDeptId))
            ->status($filterStatus)
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('access.users.index', compact(
            'items',
            'departments',
            'roles',
            'employees',
            'q',
            'filterDeptId',
            'filterStatus',
            'editUser'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
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
            'password'      => [
                'nullable',
                'confirmed',
                Rules\Password::defaults(),
                'required_without:hris_employee_id',
            ],
            'is_active'     => ['nullable', 'in:1'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            $hrisEmployee = null;

            if (!empty($validated['hris_employee_id'])) {
                $hrisEmployee = Employee::find($validated['hris_employee_id']);
            }

            $name     = $validated['name']     ?? null;
            $username = $validated['username'] ?? null;
            $email    = $validated['email']    ?? null;
            $nomorWa  = $validated['nomor_wa'] ?? null;
            $password = $validated['password'] ?? null;

            if ($hrisEmployee) {
                $name     = $hrisEmployee->name;
                $username = $hrisEmployee->name;
                $email    = $hrisEmployee->email;
                $nomorWa  = $hrisEmployee->office_phone;
                $password = $hrisEmployee->password;
            }

            if (!$name || !$username || !$email || !$password) {
                throw ValidationException::withMessages([
                    'hris_employee_id' => [
                        'Nama, username, email, dan password harus terisi, baik dari HRIS maupun input manual.',
                    ],
                ]);
            }

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
            ]);
        });

        return redirect()->route('access.users.index')
            ->with('success', 'User successfully created.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
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
            'password'      => ['nullable', 'confirmed', Rules\Password::defaults()],
            'is_active'     => ['nullable', 'in:1'],
        ]);

        DB::transaction(function () use ($validated, $request, $user) {
            $hrisEmployeeId = $user->hris_employee_id ?: ($validated['hris_employee_id'] ?? null);
            $hrisEmployee   = null;

            if (!empty($hrisEmployeeId)) {
                $hrisEmployee = Employee::find($hrisEmployeeId);
            }

            $name     = $validated['name']     ?? $user->name;
            $username = $validated['username'] ?? $user->username;
            $email    = $validated['email']    ?? $user->email;
            $nomorWa  = $validated['nomor_wa'] ?? $user->nomor_wa;
            $password = null;

            if ($hrisEmployee) {
                $name     = $hrisEmployee->name;
                $username = $hrisEmployee->name;
                $email    = $hrisEmployee->email;
                $nomorWa  = $hrisEmployee->office_phone;
                $password = $hrisEmployee->password;
            } else {
                if (!empty($validated['password'])) {
                    $password = $validated['password'];
                }
            }

            if (!$name || !$username || !$email) {
                throw ValidationException::withMessages([
                    'name' => ['Nama, username dan email tidak boleh kosong.'],
                ]);
            }

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

            if ($password !== null) {
                $payload['password'] = $password;
            }

            $user->update($payload);
        });

        return redirect()->route('access.users.index', ['edit' => $user->id])
            ->with('success', 'User successfully updated.');
    }

    public function destroy(User $user)
    {
        DB::transaction(function () use ($user) {
            $user->delete();
        });

        return redirect()->route('access.users.index')
            ->with('success', 'User successfully deleted.');
    }

    protected function syncHrisUsers(): void
    {
        $users = User::whereNotNull('hris_employee_id')->get([
            'id',
            'hris_employee_id',
            'name',
            'username',
            'email',
            'password',
            'nomor_wa',
        ]);

        if ($users->isEmpty()) return;

        $hrisIds = $users->pluck('hris_employee_id')->filter()->unique()->values()->all();

        $employees = Employee::whereIn('id', $hrisIds)
            ->get(['id', 'name', 'email', 'password', 'office_phone'])
            ->keyBy('id');

        foreach ($users as $user) {
            $emp = $employees->get($user->hris_employee_id);
            if (!$emp) continue;

            $updateData = [];
            if ($user->name !== $emp->name)           $updateData['name']      = $emp->name;
            if ($user->username !== $emp->name)       $updateData['username']  = $emp->name;
            if ($user->email !== $emp->email)         $updateData['email']     = $emp->email;
            if ($user->password !== $emp->password)   $updateData['password']  = $emp->password;
            if ($user->nomor_wa !== $emp->office_phone) $updateData['nomor_wa'] = $emp->office_phone;

            if (!empty($updateData)) {
                $user->update($updateData);
            }
        }
    }
}
