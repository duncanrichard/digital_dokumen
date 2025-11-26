<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\Document;
use App\Models\JenisDokumen;
use App\Models\Department;
use Illuminate\Support\Facades\Storage;

class DocumentRevisionController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();

            if (! $user) {
                abort(401);
            }

            // ambil role dari relasi role() di model User
            $role     = $user->role;
            $roleName = $role->name ?? null;

            // Superadmin bebas akses
            $isSuperadmin = $roleName && strcasecmp($roleName, 'Superadmin') === 0;
            if ($isSuperadmin) {
                return $next($request);
            }

            // CEK PERMISSION BERDASARKAN ROLE
            // pastikan nama permission sama persis dg di seeder & di tabel permissions
            $hasPermission = $role && $role->hasPermissionTo('documents.revisions.view');

            if (! $hasPermission) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses halaman Revisi Dokumen.');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $q             = trim((string) $request->get('q', ''));
        $filterJenisId = $request->get('document_type_id'); // uuid
        $filterDeptId  = $request->get('department_id');    // uuid

        // Master data (aktif)
        $documentTypes = JenisDokumen::where('is_active', true)
            ->orderBy('nama')
            ->get(['id','kode','nama']);

        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get(['id','code','name']);

        // Ambil dokumen lalu kelompokkan per document_number
        $items = Document::with(['jenisDokumen:id,kode,nama', 'department:id,code,name'])
            ->when($q !== '', function ($query) use ($q) {
                $needle = mb_strtolower($q);
                $query->where(function ($sub) use ($needle) {
                    $sub->whereRaw('LOWER(name) LIKE ?', ["%{$needle}%"])
                        ->orWhereRaw('LOWER(document_number) LIKE ?', ["%{$needle}%"]);
                });
            })
            ->when($filterJenisId, fn($q) => $q->where('jenis_dokumen_id', $filterJenisId))
            ->when($filterDeptId,  fn($q) => $q->where('department_id',    $filterDeptId))
            ->orderByDesc('publish_date')
            ->paginate(10)
            ->withQueryString();

        // Grouping per nomor dasar
        $grouped = collect($items->items())->groupBy('document_number');

        return view('documents.revisions.index', compact(
            'items',
            'grouped',
            'q',
            'documentTypes',
            'departments',
            'filterJenisId',
            'filterDeptId'
        ));
    }

    /**
     * Create a new revision based on latest row of a document_number.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'base_id'        => ['required','uuid', Rule::exists('documents','id')],
            'document_name'  => ['required','string','max:255'],
            'publish_date'   => ['required','date'],
            'file'           => ['required','file','mimes:pdf','max:10240'],
            'is_active'      => ['nullable','in:1'],
        ]);

        $storedPath = $request->file('file')->store('documents', 'public');

        // Ambil baris base (untuk ambil nomor dasar, jenis & dept)
        $base = Document::with(['jenisDokumen:id,kode', 'department:id,code'])
            ->findOrFail($validated['base_id']);

        // Revisi maksimum dari nomor dasar ini
        $maxRevision  = (int) Document::where('document_number', $base->document_number)->max('revision');
        $nextRevision = $maxRevision + 1;

        DB::transaction(function () use ($validated, $base, $storedPath, $nextRevision, $request) {
            // Nonaktifkan semua versi lama
            Document::where('document_number', $base->document_number)->update(['is_active' => false]);

            // Buat versi baru
            Document::create([
                'jenis_dokumen_id' => $base->jenis_dokumen_id,
                'department_id'    => $base->department_id,
                'sequence'         => $base->sequence,
                'document_number'  => $base->document_number,
                'revision'         => $nextRevision,
                'name'             => $validated['document_name'],
                'publish_date'     => $validated['publish_date'],
                'file_path'        => $storedPath,
                'is_active'        => $request->boolean('is_active', true),
            ]);
        });

        return redirect()
            ->route('documents.revisions.index')
            ->with('success', "Revision created: {$base->document_number} R{$nextRevision}");
    }
}
