<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\Document;
use App\Models\DocumentDistribution;
use App\Models\Department;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DocumentDistributionController extends Controller
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

            // Pakai role->hasPermissionTo, sama seperti di controller lain
            $hasPermission = $role && $role->hasPermissionTo('documents.distribution.view');

            if (! $hasPermission) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses halaman Distribusi Dokumen.');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        // Bisa datang sebagai string atau array
        $documentIds = $request->query('document_ids', []);
        if (! is_array($documentIds)) {
            $documentIds = [$documentIds];
        }
        $documentIds = array_values(array_filter(array_map('strval', $documentIds)));

        $driver = DB::getDriverName();
        $likeOp = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';

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
                'department_id',
            ]);

        // Tidak ada default terpilih – user harus memilih sendiri
        $selectedDocs = $docs->whereIn('id', $documentIds)->values();

        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        // index by id untuk lookup di view (main division label)
        $departmentsById = $departments->keyBy('id');

        $selectedDepartmentsByDoc = [];

        if ($selectedDocs->isNotEmpty()) {
            $distributions = DocumentDistribution::whereIn('document_id', $selectedDocs->pluck('id'))
                ->get();

            $selectedDepartmentsByDoc = $distributions
                ->groupBy('document_id')
                ->map(function ($rows) {
                    return $rows->pluck('department_id')->all();
                })
                ->toArray();
        }

        return view('documents.distribution.index', [
            'q'                        => $q,
            'documents'                => $docs,
            'selectedDocumentIds'      => $documentIds,
            'selectedDocs'             => $selectedDocs,
            'departments'              => $departments,
            'departmentsById'          => $departmentsById,
            'selectedDepartmentsByDoc' => $selectedDepartmentsByDoc,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'document_ids'   => ['required', 'array', 'min:1'],
            'document_ids.*' => [
                'uuid',
                Rule::exists('documents', 'id')->where(fn($q) => $q->where('is_active', true)),
            ],
            'distribution'        => ['nullable', 'array'],
            'distribution.*'      => ['nullable', 'array'],
            'distribution.*.*'    => [
                'uuid',
                Rule::exists('departments', 'id')->where(fn($q) => $q->where('is_active', true)),
            ],
            'send_whatsapp'      => ['nullable', 'boolean'],
        ], [], [
            'document_ids' => 'Dokumen',
            'distribution' => 'Distribusi',
        ]);

        $documentIdsRaw = $data['document_ids'] ?? [];
        $documentIds    = array_values(array_unique(array_map('strval', $documentIdsRaw)));

        // Ambil dokumen yang valid & aktif
        $documents = Document::whereIn('id', $documentIds)
            ->where('is_active', true)
            ->get();

        if ($documents->isEmpty()) {
            return redirect()
                ->route('documents.distribution.index')
                ->withErrors(['document_ids' => 'Tidak ada dokumen yang valid/aktif.']);
        }

        $distributionInput = $data['distribution'] ?? [];

        // Kumpulkan list department per dokumen (termasuk main division)
        $perDocDeptIds = [];

        foreach ($documents as $doc) {
            $docId = (string) $doc->id;

            $idsFromForm = $distributionInput[$docId] ?? [];
            if (! is_array($idsFromForm)) {
                $idsFromForm = [];
            }
            $idsFromForm = array_map('strval', $idsFromForm);

            $primaryDeptId = $doc->department_id ? (string) $doc->department_id : null;

            if ($primaryDeptId) {
                $idsFromForm[] = $primaryDeptId;
            }

            $idsFromForm = array_values(array_unique($idsFromForm));

            $perDocDeptIds[$docId] = $idsFromForm;
        }

        // Simpan ke database
        DB::transaction(function () use ($perDocDeptIds) {
            foreach ($perDocDeptIds as $documentId => $deptIds) {
                DocumentDistribution::where('document_id', $documentId)->delete();

                if (! empty($deptIds)) {
                    $now  = now();
                    $rows = [];

                    foreach ($deptIds as $d) {
                        $rows[] = [
                            'document_id'   => $documentId,
                            'department_id' => $d,
                            'is_active'     => true,
                            'created_at'    => $now,
                            'updated_at'    => $now,
                        ];
                    }
                    DocumentDistribution::insert($rows);
                }
            }
        });

        // Apakah perlu kirim WA?
        $sendWa = $request->boolean('send_whatsapp');

        if ($sendWa) {
            // =======================
            //  Kirim notif Fonnte (1 pesan untuk semua dokumen yang diproses)
            // =======================
            try {
                // Ambil semua department unik dari semua dokumen
                $allDeptIds = [];
                foreach ($perDocDeptIds as $ids) {
                    $allDeptIds = array_merge($allDeptIds, $ids);
                }
                $allDeptIds = array_values(array_unique($allDeptIds));

                $deptNameLookup = Department::whereIn('id', $allDeptIds)
                    ->orderBy('name')
                    ->get()
                    ->pluck('name', 'id')
                    ->toArray();

                $groupId = '120363404395085332@g.us';
                $token   = 'nbrnAs1M8J94FxwTTgo2';

                $messageHeader =
                    "*Pemberitahuan Distribusi Dokumen Resmi*\n\n" .
                    "Yth. Bapak/Ibu Rekan Kerja\n" .
                    "Dengan hormat,\n\n" .
                    "Berikut ini adalah daftar dokumen yang baru saja dilakukan pengaturan distribusinya melalui sistem *Document Control*:\n\n";

                $messageDocs = "";
                $counter     = 1;

                foreach ($documents as $doc) {
                    $docId         = (string) $doc->id;
                    $deptIdsForDoc = $perDocDeptIds[$docId] ?? [];

                    $publishDate = $doc->publish_date
                        ? $doc->publish_date->format('d M Y')
                        : '-';

                    $divisiList = "";
                    $idx        = 1;
                    foreach ($deptIdsForDoc as $dId) {
                        if (! isset($deptNameLookup[$dId])) {
                            continue;
                        }
                        $divisiList .= "      {$idx}. {$deptNameLookup[$dId]}\n";
                        $idx++;
                    }

                    $messageDocs .=
                        "{$counter}. *{$doc->document_number}* - {$doc->name}\n" .
                        "   • Tanggal Terbit : {$publishDate}\n" .
                        "   • Divisi Tujuan  :\n" .
                        ($divisiList !== "" ? $divisiList : "      -\n") .
                        "\n";

                    $counter++;
                }

                $messageFooter =
                    "Dokumen-dokumen di atas dapat diakses melalui sistem *Document Control* pada tautan berikut:\n" .
                    "https://demo.dokumen.dsicorp.id/\n\n" .
                    "Dimohon kepada divisi yang terkait untuk meninjau, mendistribusikan, dan menindaklanjuti dokumen tersebut sesuai tugas, kewenangan, dan prosedur yang berlaku di lingkungan perusahaan.\n\n" .
                    "Demikian pemberitahuan ini kami sampaikan. Atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.\n\n" .
                    "Hormat kami,\n" .
                    "Divisi Legal\n";

                $message = $messageHeader . $messageDocs . $messageFooter;

                $httpOptions = [];
                if (app()->environment('local')) {
                    $httpOptions['verify'] = false;
                }

                $response = Http::withOptions($httpOptions)
                    ->withHeaders([
                        'Authorization' => $token,
                    ])
                    ->asForm()
                    ->post('https://api.fonnte.com/send', [
                        'target'  => $groupId,
                        'message' => $message,
                    ]);

                Log::info('Fonnte response (multi-doc distribution)', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                    'docs'   => $documentIds,
                ]);
            } catch (\Throwable $e) {
                Log::error('WA Fonnte Error (multi-doc distribution)', [
                    'error' => $e->getMessage(),
                    'docs'  => $documentIds,
                ]);
            }
        }

        $successMsg = $sendWa
            ? 'Distribusi beberapa dokumen berhasil disimpan & notifikasi WA telah dikirim dalam 1 pesan.'
            : 'Distribusi beberapa dokumen berhasil disimpan tanpa mengirim notifikasi WA.';

        return redirect()
            ->route('documents.distribution.index', ['document_ids' => $documentIds])
            ->with('success', $successMsg);
    }
}
