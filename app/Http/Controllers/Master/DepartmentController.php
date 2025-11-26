<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
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

            if ($isSuperadmin) {
                return $next($request);
            }

            $method = $request->route()->getActionMethod();

            $requiredPermission = match ($method) {
                'index'  => 'master.departments.view',
                'store'  => 'master.departments.create',
                'update' => 'master.departments.update',
                'destroy'=> 'master.departments.delete',
                default  => 'master.departments.view',
            };

            if (!$user->role || !$user->role->hasPermissionTo($requiredPermission)) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses fitur ini.');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));

        $items = Department::when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('code', 'ilike', "%{$q}%")
                        ->orWhere('name', 'ilike', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('master.departments.index', compact('items', 'q'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'        => [
                'required',
                'max:20',
                // huruf, angka, -, _, ., ,, /, dan spasi
                'regex:/^[A-Za-z0-9\-\_.,\/ ]+$/',
                'unique:departments,code',
            ],
            'name'        => ['required', 'max:100'],
            'description' => ['nullable', 'max:1000'],
            'is_active'   => ['required', 'boolean'],
        ]);

        Department::create($validated);

        return redirect()
            ->route('master.departments.index')
            ->with('success', 'Divisi has been created.');
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'code'        => [
                'required',
                'max:20',
                'regex:/^[A-Za-z0-9\-\_.,\/ ]+$/',
                'unique:departments,code,' . $department->id,
            ],
            'name'        => ['required', 'max:100'],
            'description' => ['nullable', 'max:1000'],
            'is_active'   => ['required', 'boolean'],
        ]);

        $department->update($validated);

        return redirect()
            ->route('master.departments.index')
            ->with('success', 'Divisi has been updated.');
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return redirect()
            ->route('master.departments.index')
            ->with('success', 'Divisi has been deleted.');
    }
}
