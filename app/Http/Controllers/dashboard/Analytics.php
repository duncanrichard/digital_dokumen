<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentAccessRequest;
use App\Models\Department;
use App\Models\JenisDokumen;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Analytics extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();

            if (!$user) {
                abort(401);
            }

            // ==============================
            // 1. CEK SUPERADMIN DARI role_id
            // ==============================
            $roleName     = optional($user->role)->name; // relasi role() di model User
            $isSuperadmin = $roleName && strcasecmp($roleName, 'Superadmin') === 0;

            if ($isSuperadmin) {
                // Superadmin bebas akses
                return $next($request);
            }

            // ========================================
            // 2. USER BIASA → CEK PERMISSION DARI ROLE
            // ========================================
            $role = $user->role; // App\Models\Role (extends Spatie Role)

            $hasPermission = $role && $role->hasPermissionTo('dashboard.analytics.view');

            if (!$hasPermission) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses Dashboard Analytics.');
            }

            return $next($request);
        });
    }

    public function index()
    {
        $today        = Carbon::today();
        $startOfMonth = $today->copy()->startOfMonth();
        $startOfYear  = $today->copy()->startOfYear();

        // =========================
        // 1. RINGKASAN DOKUMEN
        // =========================
        $totalDocuments    = Document::count();
        $activeDocuments   = Document::where('is_active', true)->count();
        $inactiveDocuments = $totalDocuments - $activeDocuments;

        $documentsThisMonth = Document::whereDate('publish_date', '>=', $startOfMonth)->count();
        $documentsThisYear  = Document::whereDate('publish_date', '>=', $startOfYear)->count();

        // Dokumen baru & revisi bulan ini
        $newDocumentsThisMonth = Document::whereDate('publish_date', '>=', $startOfMonth)
            ->where('revision', 0)
            ->count();

        $revisionsThisMonth = Document::whereDate('publish_date', '>=', $startOfMonth)
            ->where('revision', '>', 0)
            ->count();

        // Revisi per document_number (untuk rata-rata revisi)
        $revisionPerDoc = Document::select('document_number', DB::raw('MAX(revision) AS max_rev'))
            ->groupBy('document_number')
            ->get();

        $avgRevisionPerDoc = $revisionPerDoc->avg('max_rev') ?: 0;

        // =========================
        // 2. AKSES DOKUMEN
        // =========================
        $pendingAccessRequests  = DocumentAccessRequest::where('status', 'pending')->count();
        $approvedAccessRequests = DocumentAccessRequest::where('status', 'approved')->count();
        $rejectedAccessRequests = DocumentAccessRequest::where('status', 'rejected')->count();

        // distribusi status request bulan ini
        $accessThisMonthRaw = DocumentAccessRequest::whereDate('requested_at', '>=', $startOfMonth)
            ->select('status', DB::raw('COUNT(*) AS total'))
            ->groupBy('status')
            ->get();

        $accessThisMonth = [
            'pending'  => (int) ($accessThisMonthRaw->firstWhere('status', 'pending')->total ?? 0),
            'approved' => (int) ($accessThisMonthRaw->firstWhere('status', 'approved')->total ?? 0),
            'rejected' => (int) ($accessThisMonthRaw->firstWhere('status', 'rejected')->total ?? 0),
        ];

        // =========================
        // 3. MASTER DATA
        // =========================
        $activeDepartments     = Department::where('is_active', true)->count();
        $inactiveDepartments   = Department::where('is_active', false)->count();
        $activeDocumentTypes   = JenisDokumen::where('is_active', true)->count();
        $inactiveDocumentTypes = JenisDokumen::where('is_active', false)->count();

        // Dokumen per departemen
        $documentsByDepartment = Document::select(
                'departments.name AS department_name',
                DB::raw('COUNT(documents.id) AS total')
            )
            ->join('departments', 'documents.department_id', '=', 'departments.id')
            ->groupBy('departments.name')
            ->orderBy('departments.name')
            ->get();

        // Dokumen per jenis
        $documentsByType = Document::select(
                'jenis_dokumen.nama AS type_name',
                DB::raw('COUNT(documents.id) AS total')
            )
            ->join('jenis_dokumen', 'documents.jenis_dokumen_id', '=', 'jenis_dokumen.id')
            ->groupBy('jenis_dokumen.nama')
            ->orderBy('jenis_dokumen.nama')
            ->get();

        // =========================
        // 4. TIME SERIES: DOKUMEN / BULAN (12 bulan terakhir)
        // =========================
        $docsPerMonth = Document::select(
                DB::raw("TO_CHAR(publish_date, 'YYYY-MM') AS ym"),
                DB::raw('COUNT(*) AS total')
            )
            ->whereDate('publish_date', '>=', $today->copy()->subMonths(11)->startOfMonth())
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();

        $docsPerMonthLabels = $docsPerMonth->pluck('ym');   // ['2025-01', '2025-02', ...]
        $docsPerMonthSeries = $docsPerMonth->pluck('total'); // [10, 20, 5, ...]

        // =========================
        // 5. DATA UNTUK CARD UTAMA
        // =========================
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
            'summaryCards'          => $summaryCards,
            'documentsByDepartment' => $documentsByDepartment,
            'documentsByType'       => $documentsByType,
            'accessThisMonth'       => $accessThisMonth,
            'docsPerMonthLabels'    => $docsPerMonthLabels,
            'docsPerMonthSeries'    => $docsPerMonthSeries,
        ]);
    }
}
