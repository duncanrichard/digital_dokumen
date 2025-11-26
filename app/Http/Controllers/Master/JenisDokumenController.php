<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\JenisDokumen;
use Illuminate\Http\Request;

class JenisDokumenController extends Controller
{
    public function __construct()
    {
        // Check user login
        $this->middleware(function ($request, $next) {
            $user = auth()->user();

            if (!$user) abort(401);

            // SUPERADMIN bypass
            $roleName = optional($user->role)->name;
            $isSuperadmin = $roleName && strcasecmp($roleName, 'Superadmin') === 0;

            if ($isSuperadmin) {
                return $next($request);
            }

            // Permission mapping
            $method = $request->route()->getActionMethod();
            $requiredPermission = match ($method) {
                'index'  => 'master.jenis-dokumen.view',
                'store'  => 'master.jenis-dokumen.create',
                'update' => 'master.jenis-dokumen.update',
                'destroy'=> 'master.jenis-dokumen.delete',
                default  => 'master.jenis-dokumen.view',
            };

            if (!$user->role || !$user->role->hasPermissionTo($requiredPermission)) {
                abort(403, 'Anda tidak memiliki izin untuk melakukan aksi ini.');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));

        $items = JenisDokumen::when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('kode', 'ilike', "%{$q}%")
                        ->orWhere('nama', 'ilike', "%{$q}%");
                });
            })
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return view('master.jenis-dokumen.index', compact('items', 'q'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode'      => [
                'required',
                'max:20',
                'regex:/^[A-Za-z0-9._-]+$/',
                'unique:jenis_dokumen,kode',
            ],
            'nama'      => ['required', 'max:100'],
            'deskripsi' => ['nullable', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ]);

        JenisDokumen::create($validated);

        return redirect()
            ->route('master.jenis-dokumen.index')
            ->with('success', 'Document type has been created.');
    }

    public function update(Request $request, JenisDokumen $jenisDokumen)
    {
        $validated = $request->validate([
            'kode'      => [
                'required',
                'max:20',
                'regex:/^[A-Za-z0-9._-]+$/',
                'unique:jenis_dokumen,kode,' . $jenisDokumen->id,
            ],
            'nama'      => ['required', 'max:100'],
            'deskripsi' => ['nullable', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ]);

        $jenisDokumen->update($validated);

        return redirect()
            ->route('master.jenis-dokumen.index')
            ->with('success', 'Document type has been updated.');
    }

    public function destroy(JenisDokumen $jenisDokumen)
    {
        $jenisDokumen->delete();

        return redirect()
            ->route('master.jenis-dokumen.index')
            ->with('success', 'Document type has been deleted.');
    }
}
