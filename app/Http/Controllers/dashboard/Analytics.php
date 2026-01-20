<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentAccessRequest;
use App\Models\Department;
use App\Models\JenisDokumen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Analytics extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();

            if (!$user) abort(401);

            $roleName     = optional($user->role)->name;
            $isSuperadmin = $roleName && strcasecmp($roleName, 'Superadmin') === 0;

            if ($isSuperadmin) return $next($request);

            $role = $user->role;
            $hasPermission = $role && $role->hasPermissionTo('dashboard.analytics.view');

            if (!$hasPermission) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses Dashboard Analytics.');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $me = $request->user();
        $roleName     = optional($me->role)->name;
        $isSuperadmin = $roleName && strcasecmp($roleName, 'Superadmin') === 0;

        // =========================================================
        // MODE 1: SUPERADMIN -> Dashboard Analytics (tetap seperti kamu)
        // =========================================================
        if ($isSuperadmin) {
            $today        = Carbon::today();
            $startOfMonth = $today->copy()->startOfMonth();
            $startOfYear  = $today->copy()->startOfYear();

            // 1) Ringkasan dokumen
            $totalDocuments    = Document::count();
            $activeDocuments   = Document::where('is_active', true)->count();
            $inactiveDocuments = $totalDocuments - $activeDocuments;

            $documentsThisMonth = Document::whereDate('publish_date', '>=', $startOfMonth)->count();
            $documentsThisYear  = Document::whereDate('publish_date', '>=', $startOfYear)->count();

            $newDocumentsThisMonth = Document::whereDate('publish_date', '>=', $startOfMonth)
                ->where('revision', 0)
                ->count();

            $revisionsThisMonth = Document::whereDate('publish_date', '>=', $startOfMonth)
                ->where('revision', '>', 0)
                ->count();

            $revisionPerDoc = Document::select('document_number', DB::raw('MAX(revision) AS max_rev'))
                ->groupBy('document_number')
                ->get();

            $avgRevisionPerDoc = $revisionPerDoc->avg('max_rev') ?: 0;

            // 2) Akses dokumen
            $pendingAccessRequests  = DocumentAccessRequest::where('status', 'pending')->count();
            $approvedAccessRequests = DocumentAccessRequest::where('status', 'approved')->count();
            $rejectedAccessRequests = DocumentAccessRequest::where('status', 'rejected')->count();

            $accessThisMonthRaw = DocumentAccessRequest::whereDate('requested_at', '>=', $startOfMonth)
                ->select('status', DB::raw('COUNT(*) AS total'))
                ->groupBy('status')
                ->get();

            $accessThisMonth = [
                'pending'  => (int) ($accessThisMonthRaw->firstWhere('status', 'pending')->total ?? 0),
                'approved' => (int) ($accessThisMonthRaw->firstWhere('status', 'approved')->total ?? 0),
                'rejected' => (int) ($accessThisMonthRaw->firstWhere('status', 'rejected')->total ?? 0),
            ];

            // 3) Master data
            $activeDepartments     = Department::where('is_active', true)->count();
            $inactiveDepartments   = Department::where('is_active', false)->count();
            $activeDocumentTypes   = JenisDokumen::where('is_active', true)->count();
            $inactiveDocumentTypes = JenisDokumen::where('is_active', false)->count();

            $documentsByDepartment = Document::select(
                    'departments.name AS department_name',
                    DB::raw('COUNT(documents.id) AS total')
                )
                ->join('departments', 'documents.department_id', '=', 'departments.id')
                ->groupBy('departments.name')
                ->orderBy('departments.name')
                ->get();

            $documentsByType = Document::select(
                    'jenis_dokumen.nama AS type_name',
                    DB::raw('COUNT(documents.id) AS total')
                )
                ->join('jenis_dokumen', 'documents.jenis_dokumen_id', '=', 'jenis_dokumen.id')
                ->groupBy('jenis_dokumen.nama')
                ->orderBy('jenis_dokumen.nama')
                ->get();

            // 4) Timeseries dokumen per bulan (12 bulan terakhir)
            $docsPerMonth = Document::select(
                    DB::raw("TO_CHAR(publish_date, 'YYYY-MM') AS ym"),
                    DB::raw('COUNT(*) AS total')
                )
                ->whereDate('publish_date', '>=', $today->copy()->subMonths(11)->startOfMonth())
                ->groupBy('ym')
                ->orderBy('ym')
                ->get();

            $docsPerMonthLabels = $docsPerMonth->pluck('ym');
            $docsPerMonthSeries = $docsPerMonth->pluck('total');

            $summaryCards = [
                'total_documents'       => $totalDocuments,
                'active_documents'      => $activeDocuments,
                'inactive_documents'    => $inactiveDocuments,
                'documents_this_month'  => $documentsThisMonth,
                'new_docs_this_month'   => $newDocumentsThisMonth,
                'revisions_this_month'  => $revisionsThisMonth,
                'documents_this_year'   => $documentsThisYear,
                'avg_revision_per_doc'  => round($avgRevisionPerDoc, 2),

                'pending_access'        => $pendingAccessRequests,
                'approved_access'       => $approvedAccessRequests,
                'rejected_access'       => $rejectedAccessRequests,

                'active_departments'    => $activeDepartments,
                'inactive_departments'  => $inactiveDepartments,
                'active_doc_types'      => $activeDocumentTypes,
                'inactive_doc_types'    => $inactiveDocumentTypes,
            ];

            return view('content.dashboard.dashboards-analytics', [
                'isSuperadmin'          => true,

                'summaryCards'          => $summaryCards,
                'documentsByDepartment' => $documentsByDepartment,
                'documentsByType'       => $documentsByType,
                'accessThisMonth'       => $accessThisMonth,
                'docsPerMonthLabels'    => $docsPerMonthLabels,
                'docsPerMonthSeries'    => $docsPerMonthSeries,
            ]);
        }

        // =========================================================
        // MODE 2: NON-SUPERADMIN -> Dashboard gaya "Library eBook"
        // =========================================================
        $q             = trim((string) $request->get('q'));
        $filterJenisId = $request->get('document_type_id');

        // lock departemen mengikuti user (seperti index dokumen kamu)
        $lockDeptId = $me?->department_id;

        $documentTypes = JenisDokumen::where('is_active', true)
            ->orderBy('nama')
            ->get(['id', 'kode', 'nama']);

        // Query dokumen yang boleh tampil:
        // - dokumen departemen sendiri ATAU didistribusikan ke departemen user
        // - filter jenis dan search
        $docs = Document::with([
                'jenisDokumen:id,kode,nama',
                'department:id,code,name',
                'clinic:id,code,name',
                'distributedDepartments:id',
            ])
            ->when($q !== '', function ($query) use ($q) {
                $needle = mb_strtolower($q);
                $query->where(function ($sub) use ($needle) {
                    $sub->whereRaw('LOWER(name) LIKE ?', ["%{$needle}%"])
                        ->orWhereRaw('LOWER(document_number) LIKE ?', ["%{$needle}%"]);
                });
            })
            ->when($filterJenisId, fn($qq) => $qq->where('jenis_dokumen_id', $filterJenisId))
            ->when($lockDeptId, function ($qq) use ($lockDeptId) {
                $qq->where(function ($sub) use ($lockDeptId) {
                    $sub->where('department_id', $lockDeptId)
                        ->orWhereHas('distributedDepartments', function ($qdep) use ($lockDeptId) {
                            $qdep->where('departments.id', $lockDeptId);
                        });
                });
            })
            // tampilkan yang paling relevan: aktif dulu, terbaru dulu
            ->orderByDesc('is_active')
            ->orderByDesc('publish_date')
            ->paginate(12)
            ->withQueryString();

        // mini summary untuk user biasa (opsional tapi berguna)
        $myTotalVisible = (clone $docs->getCollection())->count(); // hanya halaman ini
        // kalau mau total semua visible (tanpa paginate), bisa hitung query clone terpisah (lebih berat)

        return view('content.dashboard.dashboards-analytics', [
            'isSuperadmin'  => false,
            'documentTypes' => $documentTypes,
            'docs'          => $docs,
            'q'             => $q,
            'filterJenisId' => $filterJenisId,
            'lockDeptId'    => $lockDeptId,
        ]);
    }
}
