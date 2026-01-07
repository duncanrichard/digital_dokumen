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
            if (!$user) abort(401);

            $roleName     = optional($user->role)->name;
            $isSuperadmin = $roleName && strcasecmp($roleName, 'Superadmin') === 0;

            if ($isSuperadmin) return $next($request);

            $method = $request->route()->getActionMethod();

            $requiredPermission = match ($method) {
                'index'         => 'master.departments.view',

                'store'         => 'master.departments.create',
                'storeDetail'   => 'master.departments.create',

                'update'        => 'master.departments.update',
                'updateDetail'  => 'master.departments.update',

                'destroy'       => 'master.departments.delete',
                'destroyDetail' => 'master.departments.delete',

                default         => 'master.departments.view',
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

        $officeType = $request->get('office_type', 'holding');
        if (!in_array($officeType, ['holding', 'djc'], true)) {
            $officeType = 'holding';
        }

        $items = Department::query()
            ->where('office_type', $officeType)
            ->whereNull('parent_id')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('code', 'ilike', "%{$q}%")
                        ->orWhere('name', 'ilike', "%{$q}%");
                });
            })
            ->with(['children' => function ($c) {
                $c->orderBy('name');
            }])
            ->withCount('children')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('master.departments.index', compact('items', 'q', 'officeType'));
    }

    /* ===========================================================
     * ✅ DIVISI UTAMA (PARENT)
     * =========================================================== */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'office_type'   => ['required', 'in:holding,djc'],
            'wa_send_type'  => ['required', 'in:group,personal'],

            // ✅ NEW (wajib kalau group)
            'fonnte_token'  => ['nullable', 'string', 'max:255', 'required_if:wa_send_type,group'],

            'code'          => ['required', 'max:20', 'regex:/^[A-Za-z0-9\-\_.,\/ ]+$/', 'unique:departments,code'],
            'name'          => ['required', 'max:100'],
            'description'   => ['nullable', 'max:1000'],
            'no_wa'         => ['nullable', 'max:25', 'regex:/^[0-9+\-\s()]+$/'],
            'is_active'     => ['required', 'boolean'],
        ]);

        $validated['parent_id'] = null;

        // kalau personal, token tidak dipakai
        if (($validated['wa_send_type'] ?? 'personal') !== 'group') {
            $validated['fonnte_token'] = null;
        }

        Department::create($validated);

        return redirect()
            ->route('master.departments.index', ['office_type' => $validated['office_type']])
            ->with('success', 'Divisi utama berhasil dibuat.');
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'office_type'   => ['required', 'in:holding,djc'],
            'wa_send_type'  => ['required', 'in:group,personal'],

            // ✅ NEW (wajib kalau group)
            'fonnte_token'  => ['nullable', 'string', 'max:255', 'required_if:wa_send_type,group'],

            'code'          => ['required', 'max:20', 'regex:/^[A-Za-z0-9\-\_.,\/ ]+$/', 'unique:departments,code,' . $department->id],
            'name'          => ['required', 'max:100'],
            'description'   => ['nullable', 'max:1000'],
            'no_wa'         => ['nullable', 'max:25', 'regex:/^[0-9+\-\s()]+$/'],
            'is_active'     => ['required', 'boolean'],
        ]);

        $validated['parent_id'] = $department->parent_id;

        if (($validated['wa_send_type'] ?? 'personal') !== 'group') {
            $validated['fonnte_token'] = null;
        }

        $department->update($validated);

        return redirect()
            ->route('master.departments.index', ['office_type' => $validated['office_type']])
            ->with('success', 'Divisi utama berhasil diupdate.');
    }

    public function destroy(Request $request, Department $department)
    {
        $officeType = $request->get('office_type', $department->office_type ?? 'holding');
        if (!in_array($officeType, ['holding', 'djc'], true)) $officeType = 'holding';

        if (is_null($department->parent_id)) {
            Department::where('parent_id', $department->id)->delete();
        }

        $department->delete();

        return redirect()
            ->route('master.departments.index', ['office_type' => $officeType])
            ->with('success', 'Divisi berhasil dihapus.');
    }

    /* ===========================================================
     * ✅ DETAIL / CABANG (CHILD)
     * =========================================================== */

    public function storeDetail(Request $request, Department $department)
    {
        if (!is_null($department->parent_id)) {
            abort(422, 'Department yang dipilih bukan divisi utama.');
        }

        $validated = $request->validate([
            'name'         => ['required', 'max:100'],
            'wa_send_type' => ['required', 'in:group,personal'],

            // ✅ NEW (wajib kalau group)
            'fonnte_token' => ['nullable', 'string', 'max:255', 'required_if:wa_send_type,group'],

            'description'  => ['nullable', 'max:1000'],
            'no_wa'        => ['nullable', 'max:25', 'regex:/^[0-9+\-\s()]+$/'],
            'is_active'    => ['required', 'boolean'],
        ]);

        $fonnteToken = ($validated['wa_send_type'] === 'group')
            ? ($validated['fonnte_token'] ?? null)
            : null;

        Department::create([
            'office_type'   => $department->office_type,
            'parent_id'     => $department->id,
            'code'          => null,
            'name'          => $validated['name'],
            'wa_send_type'  => $validated['wa_send_type'],
            'fonnte_token'  => $fonnteToken, // ✅ NEW
            'description'   => $validated['description'] ?? null,
            'no_wa'         => $validated['no_wa'] ?? null,
            'is_active'     => $validated['is_active'],
        ]);

        return redirect()
            ->route('master.departments.index', ['office_type' => $department->office_type])
            ->with('success', 'Detail divisi berhasil ditambahkan.');
    }

    public function updateDetail(Request $request, Department $detail)
    {
        if (is_null($detail->parent_id)) {
            abort(422, 'Yang dipilih bukan detail divisi.');
        }

        $validated = $request->validate([
            'name'         => ['required', 'max:100'],
            'wa_send_type' => ['required', 'in:group,personal'],

            // ✅ NEW (wajib kalau group)
            'fonnte_token' => ['nullable', 'string', 'max:255', 'required_if:wa_send_type,group'],

            'description'  => ['nullable', 'max:1000'],
            'no_wa'        => ['nullable', 'max:25', 'regex:/^[0-9+\-\s()]+$/'],
            'is_active'    => ['required', 'boolean'],
        ]);

        $fonnteToken = ($validated['wa_send_type'] === 'group')
            ? ($validated['fonnte_token'] ?? null)
            : null;

        $detail->update([
            'name'         => $validated['name'],
            'wa_send_type' => $validated['wa_send_type'],
            'fonnte_token' => $fonnteToken, // ✅ NEW
            'description'  => $validated['description'] ?? null,
            'no_wa'        => $validated['no_wa'] ?? null,
            'is_active'    => $validated['is_active'],
            'code'         => null,
        ]);

        return redirect()
            ->route('master.departments.index', ['office_type' => $detail->office_type])
            ->with('success', 'Detail divisi berhasil diupdate.');
    }

    public function destroyDetail(Request $request, Department $detail)
    {
        if (is_null($detail->parent_id)) {
            abort(422, 'Yang dipilih bukan detail divisi.');
        }

        $officeType = $detail->office_type;
        $detail->delete();

        return redirect()
            ->route('master.departments.index', ['office_type' => $officeType])
            ->with('success', 'Detail divisi berhasil dihapus.');
    }
}
