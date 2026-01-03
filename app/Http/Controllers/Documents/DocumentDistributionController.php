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
    /**
     * ✅ Token Fonnte (Authorization)
     * Ini TOKEN untuk API-nya (bukan target).
     */
    private string $FONNTE_TOKEN = 'XMYGkswnarUgtUu6BSnh';

    /**
     * ✅ Target Group (jika wa_send_type = group)
     */
    private string $FONNTE_GROUP_TARGET = '120363404395085332@g.us';

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user) abort(401);

            $role     = $user->role;
            $roleName = $role->name ?? null;

            $isSuperadmin = $roleName && strcasecmp($roleName, 'Superadmin') === 0;
            if ($isSuperadmin) return $next($request);

            $hasPermission = $role && $role->hasPermissionTo('documents.distribution.view');
            if (!$hasPermission) abort(403, 'Anda tidak memiliki izin untuk mengakses halaman Distribusi Dokumen.');

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $documentIds = $request->query('document_ids', []);
        if (!is_array($documentIds)) $documentIds = [$documentIds];
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

        $selectedDocs = $docs->whereIn('id', $documentIds)->values();

        // ✅ ambil PARENT + children (aktif) + wa_send_type
        $parents = Department::query()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->where('is_active', true)->orderBy('name');
            }])
            ->withCount(['children' => function ($q) {
                $q->where('is_active', true);
            }])
            ->orderBy('office_type')
            ->orderBy('name')
            ->get(['id', 'name', 'office_type', 'no_wa', 'wa_send_type']);

        $departmentsHolding = $parents->where('office_type', 'holding')->values();
        $departmentsDjc     = $parents->where('office_type', 'djc')->values();
        $departmentsOther   = $parents->whereNotIn('office_type', ['holding', 'djc'])->values();

        // ✅ untuk badge "Main Division" dll + include wa_send_type
        $departmentsById = Department::where('is_active', true)
            ->get(['id','name','office_type','parent_id','no_wa','wa_send_type'])
            ->keyBy('id');

        $selectedDepartmentsByDoc = [];
        if ($selectedDocs->isNotEmpty()) {
            $distributions = DocumentDistribution::whereIn('document_id', $selectedDocs->pluck('id'))->get();
            $selectedDepartmentsByDoc = $distributions
                ->groupBy('document_id')
                ->map(fn($rows) => $rows->pluck('department_id')->all())
                ->toArray();
        }

        return view('documents.distribution.index', [
            'q'                        => $q,
            'documents'                => $docs,
            'selectedDocumentIds'      => $documentIds,
            'selectedDocs'             => $selectedDocs,
            'departmentsById'          => $departmentsById,
            'departmentsHolding'       => $departmentsHolding,
            'departmentsDjc'           => $departmentsDjc,
            'departmentsOther'         => $departmentsOther,
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

        'distribution'     => ['nullable', 'array'],
        'distribution.*'   => ['nullable', 'array'],
        'distribution.*.*' => [
            'uuid',
            Rule::exists('departments', 'id')->where(fn($q) => $q->where('is_active', true)),
        ],

        'send_whatsapp' => ['nullable', 'boolean'],
    ]);

    $documentIds = array_values(array_unique(array_map('strval', $data['document_ids'] ?? [])));

    $documents = Document::whereIn('id', $documentIds)
        ->where('is_active', true)
        ->get();

    if ($documents->isEmpty()) {
        return redirect()
            ->route('documents.distribution.index')
            ->withErrors(['document_ids' => 'Tidak ada dokumen yang valid/aktif.']);
    }

    $distributionInput = $data['distribution'] ?? [];

    $perDocDeptIds = [];
    foreach ($documents as $doc) {
        $docId = (string) $doc->id;

        $idsFromForm = $distributionInput[$docId] ?? [];
        if (!is_array($idsFromForm)) $idsFromForm = [];

        // ✅ Normalisasi: string, unique, buang kosong
        $idsFromForm = array_values(array_unique(array_filter(array_map('strval', $idsFromForm))));

        /**
         * ✅ PENTING:
         * - Main division TIDAK dipaksa ikut lagi.
         * - Jadi kalau user hanya pilih cabang, parent tidak akan tersimpan & tidak akan terkirim WA.
         */

        $perDocDeptIds[$docId] = $idsFromForm;
    }

    DB::transaction(function () use ($perDocDeptIds) {
        foreach ($perDocDeptIds as $documentId => $deptIds) {
            // hapus dulu semua distribusi lama
            DocumentDistribution::where('document_id', $documentId)->delete();

            // insert yang baru (sesuai pilihan user)
            if (!empty($deptIds)) {
                $now = now();
                $rows = [];
                foreach ($deptIds as $deptId) {
                    $rows[] = [
                        'document_id'   => $documentId,
                        'department_id' => $deptId,
                        'is_active'     => true,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }
                DocumentDistribution::insert($rows);
            }
        }
    });

    $sendWa = $request->boolean('send_whatsapp');
    if ($sendWa) {
        // ✅ WA hanya ke deptIds yang dipilih user
        $this->sendWhatsappNotifications($documents, $perDocDeptIds);
    }

    $successMsg = $sendWa
        ? 'Distribusi dokumen berhasil disimpan & notifikasi WA terkirim.'
        : 'Distribusi dokumen berhasil disimpan tanpa mengirim WA.';

    return redirect()
        ->route('documents.distribution.index', ['document_ids' => $documentIds])
        ->with('success', $successMsg);
}


    /**
     * ✅ Normalisasi nomor WA:
     * - 0812xxx -> 62812xxx
     * - 812xxx  -> 62812xxx
     * - +62812xxx -> 62812xxx
     * - hapus spasi / strip / dll
     * - kalau target group @g.us: biarkan
     */
    protected function normalizeWaTarget(string $target): string
    {
        $target = trim($target);
        if ($target === '') return '';

        if (str_contains($target, '@g.us')) return $target;

        $digits = preg_replace('/\D+/', '', $target) ?? '';
        if ($digits === '') return '';

        if (str_starts_with($digits, '62')) return $digits;
        if (str_starts_with($digits, '0')) return '62' . substr($digits, 1);
        if (str_starts_with($digits, '8')) return '62' . $digits;

        return $digits;
    }

    /**
     * ✅ Kirim WA berdasarkan wa_send_type:
     * - group    -> kirim ke $this->FONNTE_GROUP_TARGET
     * - personal -> kirim ke no_wa dept
     */
    protected function sendWhatsappNotifications($documents, array $perDocDeptIds): void
    {
        try {
            $token   = trim($this->FONNTE_TOKEN);
            $groupId = trim($this->FONNTE_GROUP_TARGET);
            $appUrl  = rtrim(config('app.url') ?: url('/'), '/') . '/';

            if ($token === '') {
                Log::warning('Fonnte token missing');
                return;
            }

            // ambil semua dept unik
            $allDeptIds = [];
            foreach ($perDocDeptIds as $ids) $allDeptIds = array_merge($allDeptIds, $ids);
            $allDeptIds = array_values(array_unique($allDeptIds));

            // ✅ ambil wa_send_type juga
            $deptMap = Department::whereIn('id', $allDeptIds)
                ->where('is_active', true)
                ->get(['id','name','no_wa','wa_send_type'])
                ->keyBy('id');

            // deptId => docs[]
            $docsByDept = [];
            foreach ($documents as $doc) {
                $deptIds = $perDocDeptIds[(string)$doc->id] ?? [];
                foreach ($deptIds as $deptId) {
                    $docsByDept[$deptId][] = $doc;
                }
            }

            /**
             * 1) GROUP: gabungkan semua doc untuk dept wa_send_type=group
             */
            $groupDocsMap = [];        // docId => doc
            $groupDeptNames = [];      // list dept name

            foreach ($docsByDept as $deptId => $docList) {
                $dept = $deptMap[$deptId] ?? null;
                if (!$dept) continue;

                $type = strtolower((string)($dept->wa_send_type ?? 'personal'));
                if ($type !== 'group') continue;

                $groupDeptNames[] = $dept->name;

                foreach ($docList as $d) {
                    $groupDocsMap[(string)$d->id] = $d; // unique docs
                }
            }

            if (!empty($groupDocsMap)) {
                if ($groupId === '') {
                    Log::warning('Skip group send: group target empty');
                } else {
                    $msg = $this->buildGroupMessage(
                        array_values($groupDocsMap),
                        array_values(array_unique($groupDeptNames)),
                        $appUrl
                    );

                    Log::info('Send GROUP by wa_send_type', [
                        'group_target' => $groupId,
                        'dept_names'   => $groupDeptNames,
                        'docs_count'   => count($groupDocsMap),
                    ]);

                    $this->sendFonnte($token, $groupId, $msg);
                }
            }

            /**
             * 2) PERSONAL: kirim per dept (wa_send_type=personal)
             */
            foreach ($docsByDept as $deptId => $docList) {
                $dept = $deptMap[$deptId] ?? null;
                if (!$dept) continue;

                $type = strtolower((string)($dept->wa_send_type ?? 'personal'));
                if ($type === 'group') {
                    // sudah dibroadcast ke group, skip personal
                    continue;
                }

                $raw = trim((string)($dept->no_wa ?? ''));
                $target = $this->normalizeWaTarget($raw);

                if ($target === '') {
                    Log::warning('Skip personal: no_wa empty/invalid', [
                        'dept_id' => $deptId,
                        'dept'    => $dept->name,
                        'raw'     => $raw,
                    ]);
                    continue;
                }

                $msg = $this->buildDeptMessage($dept->name, $docList, $appUrl);

                Log::info('Send PERSONAL by wa_send_type', [
                    'dept'   => $dept->name,
                    'target' => $target,
                ]);

                $this->sendFonnte($token, $target, $msg);
            }

        } catch (\Throwable $e) {
            Log::error('WA Fonnte Error (distribution)', ['error' => $e->getMessage()]);
        }
    }

    /**
     * ✅ Message untuk GROUP (broadcast)
     */
    protected function buildGroupMessage(array $docs, array $deptNames, string $appUrl): string
    {
        $header =
            "*Pemberitahuan Distribusi Dokumen Resmi*\n\n".
            "Yth. Group\n".
            "Berikut dokumen yang didistribusikan:\n\n";

        $body = "";
        $i = 1;
        foreach ($docs as $doc) {
            $publishDate = $doc->publish_date ? $doc->publish_date->format('d M Y') : '-';
            $body .= "{$i}. *{$doc->document_number}* - {$doc->name}\n".
                     "   • Tanggal Terbit : {$publishDate}\n".
                     (!is_null($doc->revision) ? "   • Revisi : {$doc->revision}\n" : "").
                     "\n";
            $i++;
        }

        $deptList = "";
        $j = 1;
        foreach ($deptNames as $nm) {
            $deptList .= "   {$j}. {$nm}\n";
            $j++;
        }

        return $header.$body.
            "*Divisi Tujuan (Group):*\n{$deptList}\n".
            "Akses dokumen: {$appUrl}\n\n".
            "Terima kasih.\n";
    }

    /**
     * ✅ Message untuk PERSONAL
     */
    protected function buildDeptMessage(string $deptName, array $docs, string $appUrl): string
    {
        $header =
            "*Pemberitahuan Distribusi Dokumen Resmi*\n\n".
            "Yth. *{$deptName}*\n".
            "Berikut dokumen yang didistribusikan untuk divisi Anda:\n\n";

        $body = "";
        $i = 1;
        foreach ($docs as $doc) {
            $publishDate = $doc->publish_date ? $doc->publish_date->format('d M Y') : '-';
            $body .= "{$i}. *{$doc->document_number}* - {$doc->name}\n".
                     "   • Tanggal Terbit : {$publishDate}\n".
                     (!is_null($doc->revision) ? "   • Revisi : {$doc->revision}\n" : "").
                     "\n";
            $i++;
        }

        return $header.$body.
            "Akses dokumen: {$appUrl}\n\n".
            "Terima kasih.\n";
    }

    protected function sendFonnte(string $token, string $target, string $message): void
    {
        $httpOptions = [];
        if (app()->environment('local')) $httpOptions['verify'] = false;

        $res = Http::withOptions($httpOptions)
            ->withHeaders(['Authorization' => $token])
            ->asForm()
            ->post('https://api.fonnte.com/send', [
                'target'  => $target,
                'message' => $message,
            ]);

        if (!$res->successful()) {
            Log::warning('Fonnte send FAILED', [
                'target' => $target,
                'status' => $res->status(),
                'body'   => $res->body(),
            ]);
            return;
        }

        Log::info('Fonnte send OK', [
            'target' => $target,
            'status' => $res->status(),
            'body'   => $res->body(),
        ]);
    }
}
