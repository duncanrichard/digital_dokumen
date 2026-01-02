<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use Illuminate\Http\Request;

class ClinicController extends Controller
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
                'index'   => 'master.clinics.view',
                'store'   => 'master.clinics.create',
                'update'  => 'master.clinics.update',
                'destroy' => 'master.clinics.delete',
                default   => 'master.clinics.view',
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

        $items = Clinic::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('code', 'ilike', "%{$q}%")
                        ->orWhere('name', 'ilike', "%{$q}%")
                        ->orWhere('phone', 'ilike', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('master.clinics.index', compact('items', 'q'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'      => ['required','max:20','regex:/^[A-Za-z0-9\-\_.,\/ ]+$/','unique:clinics,code'],
            'name'      => ['required','max:150'],
            'address'   => ['nullable','max:500'],
            'phone'     => ['nullable','max:25','regex:/^[0-9+\-\s()]+$/'],
            'is_active' => ['required','boolean'],
        ]);

        Clinic::create($validated);

        return redirect()
            ->route('master.clinics.index')
            ->with('success', 'Klinik has been created.');
    }

    public function update(Request $request, Clinic $clinic)
    {
        $validated = $request->validate([
            'code'      => ['required','max:20','regex:/^[A-Za-z0-9\-\_.,\/ ]+$/','unique:clinics,code,' . $clinic->id],
            'name'      => ['required','max:150'],
            'address'   => ['nullable','max:500'],
            'phone'     => ['nullable','max:25','regex:/^[0-9+\-\s()]+$/'],
            'is_active' => ['required','boolean'],
        ]);

        $clinic->update($validated);

        return redirect()
            ->route('master.clinics.index')
            ->with('success', 'Klinik has been updated.');
    }

    public function destroy(Clinic $clinic)
    {
        $clinic->delete();

        return redirect()
            ->route('master.clinics.index')
            ->with('success', 'Klinik has been deleted.');
    }
}
