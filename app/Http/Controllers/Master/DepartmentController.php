<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));

        $items = Department::when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    // PostgreSQL ILIKE for case-insensitive search
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
<<<<<<< HEAD
            'code'        => [
                'required',
                'max:20',
                // izinkan huruf, angka, -, _, koma, titik, slash, dan spasi
                'regex:/^[A-Za-z0-9\-\_.,\/ ]+$/',
                'unique:departments,code',
            ],
=======
            'code'        => ['required', 'max:20', 'alpha_dash', 'unique:departments,code'],
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
            'name'        => ['required', 'max:100'],
            'description' => ['nullable', 'max:1000'],
            'is_active'   => ['required', 'boolean'],
        ]);

        Department::create($validated);

        return redirect()->route('master.departments.index')
<<<<<<< HEAD
            ->with('success', 'Divisi has been created.');
=======
            ->with('success', 'Department has been created.');
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
<<<<<<< HEAD
            'code'        => [
                'required',
                'max:20',
                // sama seperti di store()
                'regex:/^[A-Za-z0-9\-\_.,\/ ]+$/',
                'unique:departments,code,' . $department->id,
            ],
=======
            'code'        => ['required', 'max:20', 'alpha_dash', 'unique:departments,code,' . $department->id],
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
            'name'        => ['required', 'max:100'],
            'description' => ['nullable', 'max:1000'],
            'is_active'   => ['required', 'boolean'],
        ]);

        $department->update($validated);

        return redirect()->route('master.departments.index')
<<<<<<< HEAD
            ->with('success', 'Divisi has been updated.');
=======
            ->with('success', 'Department has been updated.');
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return redirect()->route('master.departments.index')
<<<<<<< HEAD
            ->with('success', 'Divisi has been deleted.');
=======
            ->with('success', 'Department has been deleted.');
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
    }
}
