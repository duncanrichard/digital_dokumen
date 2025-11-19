<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use App\Models\Document;
use App\Models\DocumentDistribution;
use App\Models\Department;
use App\Models\User;
use App\Mail\DocumentDistributionMail;

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
            ->get([
                'id',
                'document_number',
                'name',
                'revision',
                'publish_date',
                'is_active',
            ]);

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
            ->get(['id', 'name']);

        // Distribusi yang sudah ada utk dokumen terpilih
        $distributions = $selectedDoc
            ? DocumentDistribution::where('document_id', $selectedDoc->id)->get()
            : collect();

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
        ]);
    }

    /**
     * Simpan mapping distribution (sinkron) + kirim email ke user di departemen terkait.
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

        $documentId   = (string) $data['document_id'];
        $deptIds      = array_values(array_unique(array_map('strval', $data['department_id'] ?? [])));
        $activeStatus = $request->input('active_status', []); // [department_id => 'on']

        // 1. Simpan distribusi di DB (sinkron: hapus lama, insert baru)
        DB::transaction(function () use ($documentId, $deptIds, $activeStatus) {
            // Hapus semua distribusi lama dokumen ini
            DocumentDistribution::where('document_id', $documentId)->delete();

            if (!empty($deptIds)) {
                $now  = now();
                $rows = [];

                foreach ($deptIds as $d) {
                    $rows[] = [
                        'document_id'   => $documentId,
                        'department_id' => $d,
                        'is_active'     => isset($activeStatus[$d]), // TRUE kalau switch aktif
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }

                // Bulk insert
                DocumentDistribution::insert($rows);
            }
        });

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
    }
}
