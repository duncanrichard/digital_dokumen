<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\Document;
use App\Models\DocumentDistribution;
use App\Models\Department;

class DocumentDistributionController extends Controller
{
    public function __construct()
    {
        // $this->middleware('permission:view documents-distribution')->only('index');
        // $this->middleware('permission:update documents-distribution')->only('store');
    }

    /**
     * Halaman utama: pilih dokumen dan centang departemen yang menerima distribusi.
     * HANYA menampilkan dokumen & departemen yang aktif.
     */
    public function index(Request $request)
    {
        $q          = trim((string) $request->query('q', ''));
        $documentId = (string) $request->query('document_id', '');

        // Driver-specific case-insensitive LIKE
        $driver = DB::getDriverName();
        $likeOp = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';

        // === Daftar dokumen AKTIF (bisa difilter quick search pada name / document_number)
        $docs = Document::query()
            ->where('is_active', true)
            ->when($q !== '', function ($qq) use ($q, $likeOp) {
                $qq->where(function ($sub) use ($q, $likeOp) {
                    $sub->where('name', $likeOp, "%{$q}%")
                        ->orWhere('document_number', $likeOp, "%{$q}%");
                });
            })
            ->orderBy('publish_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(500)
            ->get(['id','document_number','name','revision','publish_date','is_active']);

        // Default pilih dokumen pertama di list aktif bila parameter tidak ada
        if ($documentId === '' && $docs->isNotEmpty()) {
            $documentId = (string) $docs->first()->id;
        }

        // Ambil dokumen terpilih (HARUS aktif)
        $selectedDoc = $documentId
            ? Document::where('is_active', true)->find($documentId)
            : null;

        // Hanya departemen aktif
        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get(['id','name']);

        // Departemen yang sudah di-distribute utk dokumen terpilih
        $selectedDepartments = $selectedDoc
            ? DocumentDistribution::where('document_id', $selectedDoc->id)->pluck('department_id')->all()
            : [];

        return view('documents.distribution.index', [
            'q'                   => $q,
            'documents'           => $docs,
            'documentId'          => $documentId,
            'selectedDoc'         => $selectedDoc,
            'departments'         => $departments,
            'selectedDepartments' => $selectedDepartments,
        ]);
    }

    /**
     * Simpan mapping distribution (sinkron).
     * Validasi hanya mengizinkan dokumen & departemen yang aktif.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'document_id'     => [
                'required',
                'uuid',
                Rule::exists('documents', 'id')->where(fn($q) => $q->where('is_active', true)),
            ],
            'department_id'   => ['array'],
            'department_id.*' => [
                'uuid',
                Rule::exists('departments', 'id')->where(fn($q) => $q->where('is_active', true)),
            ],
        ], [], [
            'document_id'   => 'Dokumen',
            'department_id' => 'Departemen',
        ]);

        $documentId = (string) $data['document_id'];
        $deptIds    = array_values(array_unique(array_map('strval', $data['department_id'] ?? [])));

        DB::transaction(function () use ($documentId, $deptIds) {
            // Hapus semua distribusi lama dokumen ini
            DocumentDistribution::where('document_id', $documentId)->delete();

            if (!empty($deptIds)) {
                $now  = now();
                $rows = array_map(fn($d) => [
                    'document_id'   => $documentId,
                    'department_id' => $d,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ], $deptIds);

                // Bulk insert
                DocumentDistribution::insert($rows);
            }
        });

        return redirect()
            ->route('documents.distribution.index', ['document_id' => $documentId])
            ->with('success', 'Distribusi dokumen berhasil disimpan.');
    }
}
