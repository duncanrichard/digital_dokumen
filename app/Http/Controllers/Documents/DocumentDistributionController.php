<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
<<<<<<< HEAD
=======
use Illuminate\Support\Facades\Mail;
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
use Illuminate\Validation\Rule;
use App\Models\Document;
use App\Models\DocumentDistribution;
use App\Models\Department;
<<<<<<< HEAD
=======
use App\Models\User;
use App\Mail\DocumentDistributionMail;
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0

class DocumentDistributionController extends Controller
{
    public function __construct()
    {
        // $this->middleware('permission:view documents-distribution')->only('index');
        // $this->middleware('permission:update documents-distribution')->only('store');
    }

    /**
<<<<<<< HEAD
     * Halaman utama: pilih dokumen dan centang divisi yang menerima distribusi.
     * HANYA menampilkan dokumen & divisi yang aktif.
=======
     * Halaman utama: pilih dokumen dan centang departemen yang menerima distribusi.
     * HANYA menampilkan dokumen & departemen yang aktif.
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
     */
    public function index(Request $request)
    {
        $q          = trim((string) $request->query('q', ''));
        $documentId = (string) $request->query('document_id', '');

        // Driver-specific case-insensitive LIKE
        $driver = DB::getDriverName();
        $likeOp = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';

<<<<<<< HEAD
        // Daftar dokumen aktif (bisa di-search)
=======
        // === Daftar dokumen AKTIF (bisa difilter quick search pada name / document_number)
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
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

<<<<<<< HEAD
        // default dokumen pertama
=======
        // Default pilih dokumen pertama di list aktif bila parameter tidak ada
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
        if ($documentId === '' && $docs->isNotEmpty()) {
            $documentId = (string) $docs->first()->id;
        }

<<<<<<< HEAD
        // dokumen terpilih (include department_id sebagai divisi utama)
=======
        // Ambil dokumen terpilih (HARUS aktif)
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
        $selectedDoc = $documentId
            ? Document::where('is_active', true)->find($documentId)
            : null;

<<<<<<< HEAD
        // divisi aktif
=======
        // Hanya departemen aktif
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

<<<<<<< HEAD
        // distribusi yang sudah ada utk dokumen terpilih
=======
        // Distribusi yang sudah ada utk dokumen terpilih
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
        $distributions = $selectedDoc
            ? DocumentDistribution::where('document_id', $selectedDoc->id)->get()
            : collect();

<<<<<<< HEAD
        // divisi yang sudah dipilih
        $selectedDepartments = $distributions->pluck('department_id')->all();

        return view('documents.distribution.index', [
            'q'                   => $q,
            'documents'           => $docs,
            'documentId'          => $documentId,
            'selectedDoc'         => $selectedDoc,
            'departments'         => $departments,
            'selectedDepartments' => $selectedDepartments,
=======
        // Array id departemen yang sudah di-distribute (untuk checkbox)
        $selectedDepartments = $distributions->pluck('department_id')->all();

        // Map distribution per departemen (supaya di view bisa cek is_active, dsb)
        $distributionsByDepartment = $distributions->keyBy('department_id');

        return view('documents.distribution.index', [
            'q'                       => $q,
            'documents'               => $docs,
            'documentId'              => $documentId,
            'selectedDoc'             => $selectedDoc,
            'departments'             => $departments,
            'selectedDepartments'     => $selectedDepartments,
            'distributionsByDepartment' => $distributionsByDepartment,
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
        ]);
    }

    /**
<<<<<<< HEAD
     * Simpan mapping distribution (sinkron).
     * TANPA kirim email.
     *
     * Catatan:
     * - Divisi utama dokumen (document->department_id) SELALU ikut distribusi,
     *   walaupun tidak dicentang di form (dipaksa di-backend).
=======
     * Simpan mapping distribution (sinkron) + kirim email ke user di departemen terkait.
     * Validasi hanya mengizinkan dokumen & departemen yang aktif.
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
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
<<<<<<< HEAD
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
=======
            'department_id' => 'Departemen',
        ]);

        $documentId   = (string) $data['document_id'];
        $deptIds      = array_values(array_unique(array_map('strval', $data['department_id'] ?? [])));
        $activeStatus = $request->input('active_status', []); // [department_id => 'on']

        // 1. Simpan distribusi di DB (sinkron: hapus lama, insert baru)
        DB::transaction(function () use ($documentId, $deptIds, $activeStatus) {
            // Hapus semua distribusi lama dokumen ini
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
            DocumentDistribution::where('document_id', $documentId)->delete();

            if (!empty($deptIds)) {
                $now  = now();
                $rows = [];

                foreach ($deptIds as $d) {
                    $rows[] = [
                        'document_id'   => $documentId,
                        'department_id' => $d,
<<<<<<< HEAD
                        // kalau tabel masih punya kolom is_active, kita set default true
                        'is_active'     => true,
=======
                        'is_active'     => isset($activeStatus[$d]), // TRUE kalau switch aktif
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }

<<<<<<< HEAD
=======
                // Bulk insert
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
                DocumentDistribution::insert($rows);
            }
        });

<<<<<<< HEAD
        return redirect()
            ->route('documents.distribution.index', ['document_id' => $documentId])
            ->with('success', 'Distribusi dokumen berhasil disimpan.');
=======
        // 2. Kirim email ke user aktif di departemen terpilih yang distribusinya AKTIF
        if (!empty($deptIds)) {
            $document = Document::find($documentId);

            if ($document) {
                // Ambil hanya departemen yang distribusinya is_active = true
                $activeDeptIds = DocumentDistribution::where('document_id', $documentId)
                    ->whereIn('department_id', $deptIds)
                    ->where('is_active', true)
                    ->pluck('department_id')
                    ->all();

                if (!empty($activeDeptIds)) {
                    $departments = Department::with(['users' => function ($q) {
                            $q->where('is_active', true)
                              ->whereNotNull('email');
                        }])
                        ->whereIn('id', $activeDeptIds)
                        ->get();

                    foreach ($departments as $department) {
                        foreach ($department->users as $user) {
                            Mail::to($user->email)->send(
                                new DocumentDistributionMail($document, $department, $user)
                            );
                        }
                    }
                }
            }
        }

        return redirect()
            ->route('documents.distribution.index', ['document_id' => $documentId])
            ->with('success', 'Distribusi dokumen berhasil disimpan. Email hanya dikirim ke departemen yang distribusinya aktif.');
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
    }
}
