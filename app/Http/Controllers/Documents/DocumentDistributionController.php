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
     * Halaman utama: pilih dokumen dan centang divisi yang menerima distribusi.
     * HANYA menampilkan dokumen & divisi yang aktif.
     */
    public function index(Request $request)
    {
        $q          = trim((string) $request->query('q', ''));
        $documentId = (string) $request->query('document_id', '');

        // Driver-specific case-insensitive LIKE
        $driver = DB::getDriverName();
        $likeOp = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';

        // Daftar dokumen aktif (bisa di-search)
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
            ->get([
                'id',
                'document_number',
                'name',
                'revision',
                'publish_date',
                'is_active',
            ]);

        // default dokumen pertama
        if ($documentId === '' && $docs->isNotEmpty()) {
            $documentId = (string) $docs->first()->id;
        }

        // dokumen terpilih (include department_id sebagai divisi utama)
        $selectedDoc = $documentId
            ? Document::where('is_active', true)->find($documentId)
            : null;

        // divisi aktif
        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        // distribusi yang sudah ada utk dokumen terpilih
        $distributions = $selectedDoc
            ? DocumentDistribution::where('document_id', $selectedDoc->id)->get()
            : collect();

        // divisi yang sudah dipilih
        $selectedDepartments = $distributions->pluck('department_id')->all();

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
     * TANPA kirim email.
     *
     * Catatan:
     * - Divisi utama dokumen (document->department_id) SELALU ikut distribusi,
     *   walaupun tidak dicentang di form (dipaksa di-backend).
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
            'department_id' => 'Divisi',
        ]);

        $documentId = (string) $data['document_id'];

        // Ambil dokumen untuk mengetahui divisi utama
        $document = Document::where('is_active', true)->findOrFail($documentId);
        $primaryDeptId = $document->department_id ? (string) $document->department_id : null;

        // Divisi dari form
        $deptIds = array_values(array_unique(array_map('strval', $data['department_id'] ?? [])));

        // Paksa tambahkan divisi utama ke distribusi (kalau ada)
        if ($primaryDeptId) {
            $deptIds[] = $primaryDeptId;
        }

        // Unik lagi setelah ditambah primary
        $deptIds = array_values(array_unique($deptIds));

        DB::transaction(function () use ($documentId, $deptIds) {
            // Hapus distribusi lama
            DocumentDistribution::where('document_id', $documentId)->delete();

            if (!empty($deptIds)) {
                $now  = now();
                $rows = [];

                foreach ($deptIds as $d) {
                    $rows[] = [
                        'document_id'   => $documentId,
                        'department_id' => $d,
                        // kalau tabel masih punya kolom is_active, kita set default true
                        'is_active'     => true,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }

                DocumentDistribution::insert($rows);
            }
        });

        return redirect()
            ->route('documents.distribution.index', ['document_id' => $documentId])
            ->with('success', 'Distribusi dokumen berhasil disimpan.');
    }
}
