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
        $q          = trim((string) $request->query('q', ''));
        $documentId = (string) $request->query('document_id', '');

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
            ]);

        if ($documentId === '' && $docs->isNotEmpty()) {
            $documentId = (string) $docs->first()->id;
        }

        $selectedDoc = $documentId
            ? Document::where('is_active', true)->find($documentId)
            : null;

        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $distributions = $selectedDoc
            ? DocumentDistribution::where('document_id', $selectedDoc->id)->get()
            : collect();

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
        $document   = Document::where('is_active', true)->findOrFail($documentId);

        $primaryDeptId = $document->department_id ? (string) $document->department_id : null;
        $deptIds       = array_values(array_unique(array_map('strval', $data['department_id'] ?? [])));

        if ($primaryDeptId) {
            $deptIds[] = $primaryDeptId;
        }

        $deptIds = array_values(array_unique($deptIds));

        DB::transaction(function () use ($documentId, $deptIds) {
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
        });

        // =======================
        //  Kirim notif Fonnte
        // =======================
        try {
            $deptNames = Department::whereIn('id', $deptIds)
                ->orderBy('name')
                ->pluck('name')
                ->toArray();

            $groupId = '120363404395085332@g.us';
            $token   = 'nbrnAs1M8J94FxwTTgo2';

            $publishDate = $document->publish_date
                ? $document->publish_date->format('d M Y')
                : '-';

            $divisiList = "";
            foreach ($deptNames as $index => $name) {
                $no = $index + 1;
                $divisiList .= "   {$no}. {$name}\n";
            }

            $message =
                "*Pemberitahuan Distribusi Dokumen Resmi*\n\n" .
                "Yth. Bapak/Ibu Rekan Kerja\n" .
                "Dengan hormat,\n\n" .
                "Sebagai bagian dari pengelolaan dan pengendalian dokumen perusahaan, bersama ini kami sampaikan bahwa telah diterbitkan dokumen baru dengan rincian sebagai berikut:\n\n" .
                "• *Judul Dokumen*    : {$document->name}\n" .
                "• *Nomor Dokumen*    : {$document->document_number}\n" .
                "• *Tanggal Terbit*   : {$publishDate}\n" .
                "• *Divisi Distribution*    :\n" .
                $divisiList . "\n" .
                "• *Diterbitkan oleh* : Legal\n\n" .
                "Dokumen dimaksud dapat diakses melalui sistem *Document Control* pada tautan berikut:\n" .
                "https://demo.dokumen.dsicorp.id/\n\n" .
                "Dimohon kepada divisi yang terkait untuk segera meninjau, mendistribusikan, dan menindaklanjuti dokumen tersebut sesuai dengan tugas, kewenangan, dan prosedur yang berlaku di lingkungan perusahaan.\n\n" .
                "Demikian pemberitahuan ini kami sampaikan. Atas perhatian dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.\n\n" .
                "Hormat kami,\n" .
                "Divisi Legal\n";

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

            Log::info('Fonnte response', [
                'status'  => $response->status(),
                'body'    => $response->body(),
                'doc_id'  => $documentId,
            ]);
        } catch (\Throwable $e) {
            Log::error('WA Fonnte Error', [
                'error' => $e->getMessage(),
                'doc'   => $documentId,
            ]);
        }

        return redirect()
            ->route('documents.distribution.index', ['document_id' => $documentId])
            ->with('success', 'Distribusi dokumen berhasil disimpan & notifikasi telah dikirim.');
    }
}
