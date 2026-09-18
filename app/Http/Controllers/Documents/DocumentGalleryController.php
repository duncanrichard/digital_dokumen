<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JenisDokumen;
use App\Models\Department;
use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use App\Events\DocumentNotificationUpdated;
use App\Services\DocumentAiSearchService;

class DocumentGalleryController extends Controller
{
    public function aiSearch(Request $request, DocumentAiSearchService $search)
    {
        $request->validate(['q' => ['required', 'string', 'max:200']]);
        return response()->json(['data' => $search->search($request->string('q')->toString(), 8, $request->user())]);
    }
    public function index(Request $request)
    {
        $q             = trim((string) $request->get('q'));
        $filterJenisId = $request->get('document_type_id');
        $filterDeptId  = $request->get('department_id');

        $me       = $request->user();
        $myDeptId = $me?->department_id;
        $myUserId = $me?->id;
        $isSuperadmin = $me && strcasecmp((string) optional($me->role)->name, 'Superadmin') === 0;

        $lockDeptId = null;

        $documentTypes = JenisDokumen::where('is_active', true)
            ->orderBy('nama')
            ->get(['id', 'kode', 'nama']);

        $departments = Department::when($lockDeptId, fn($q2) => $q2->where('id', $lockDeptId))
            ->when(!$lockDeptId, fn($q3) => $q3->where('is_active', true))
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $latest = Document::query()
            ->select('document_number', DB::raw('MAX(COALESCE(revision,0)) as max_rev'))
            ->whereNull('clinic_id')
            ->groupBy('document_number');

        $items = Document::query()
            ->joinSub($latest, 'latest', function ($join) {
                $join->on('documents.document_number', '=', 'latest.document_number')
                    ->on(DB::raw('COALESCE(documents.revision,0)'), '=', 'latest.max_rev');
            })
            ->with([
                'jenisDokumen:id,kode,nama',
                'department:id,code,name',
                'distributedDepartments:id,code,name',
                'distributedUsers:id,name',
            ])
            ->whereNull('documents.clinic_id')

            ->when($q !== '', function ($query) use ($q) {
                $needle = mb_strtolower($q);
                $query->where(function ($sub) use ($needle) {
                    $sub->whereRaw('LOWER(documents.name) LIKE ?', ["%{$needle}%"])
                        ->orWhereRaw('LOWER(documents.document_number) LIKE ?', ["%{$needle}%"]);
                });
            })

            ->when($filterJenisId, fn($qq) => $qq->where('documents.jenis_dokumen_id', $filterJenisId))

            ->when($filterDeptId, function ($qq) use ($filterDeptId) {
                $qq->where(function ($sub) use ($filterDeptId) {
                    $sub->where('documents.department_id', $filterDeptId)
                        ->orWhereHas('distributedDepartments', function ($d) use ($filterDeptId) {
                            $d->where('departments.id', $filterDeptId);
                        });
                });
            })

            ->when($lockDeptId, function ($qq) use ($lockDeptId) {
                $qq->where(function ($sub) use ($lockDeptId) {
                    $sub->where('documents.department_id', $lockDeptId)
                        ->orWhereHas('distributedDepartments', function ($d) use ($lockDeptId) {
                            $d->where('departments.id', $lockDeptId);
                        });
                });
            })

            ->orderByDesc('documents.publish_date')
            ->select('documents.*')
            ->paginate(12)
            ->withQueryString();

        $docIds = $items->getCollection()->pluck('id')->values();

        $latestReqRows = collect();
        if ($myUserId && $docIds->count() > 0) {
            $latestReqRows = DB::table('document_access_requests as r')
                ->select('r.document_id', 'r.status', 'r.created_at', 'r.access_expires_at as expires_at')
                ->where('r.requester_user_id', $myUserId)
                ->whereIn('r.document_id', $docIds)
                ->orderBy('r.document_id')
                ->orderByDesc('r.created_at')
                ->get()
                ->groupBy('document_id')
                ->map(fn($g) => $g->first());
        }

        $items->getCollection()->transform(function ($doc) use ($myDeptId, $myUserId, $latestReqRows, $isSuperadmin) {
            $isOwnerDept = $myDeptId && ((string)$doc->department_id === (string)$myDeptId);

            $isDistributedToMe = false;
            if ($myDeptId) {
                $isDistributedToMe = $doc->distributedDepartments
                    ? $doc->distributedDepartments->contains('id', $myDeptId)
                    : false;
            }

            $isDistributedToUser = $myUserId && $doc->distributedUsers->contains('id', $myUserId);

            $req = $latestReqRows->get($doc->id);

            // ✅ normalisasi status agar "Rejected" / "rejected" kebaca
            $status = $req?->status ? strtoupper(trim((string)$req->status)) : null;
            // kalau ada kemungkinan status lain misal "REJECT" jadikan "REJECTED"
            if ($status === 'REJECT') $status = 'REJECTED';

            $doc->access_request_status = $status;
            $doc->access_request_at     = $req?->created_at ?? null;
            $doc->access_expires_at     = $req?->expires_at ?? null;

            // approved valid?
            $reqApproved = ($status === 'APPROVED');
            if ($reqApproved && !empty($doc->access_expires_at)) {
                $exp = Carbon::parse($doc->access_expires_at);
                if ($exp->isPast()) $reqApproved = false;
            }

            // flags
            $doc->is_distributed_doc = (!$isOwnerDept && ($isDistributedToMe || $isDistributedToUser));

            // locked jika bukan owner, bukan distribusi, dan belum approved valid
            $hasDistribution = $doc->distributedDepartments->isNotEmpty() || $doc->distributedUsers->isNotEmpty();
            $superadminOpen = $isSuperadmin;
            $doc->is_locked_for_me = (!$superadminOpen && !$isOwnerDept && !$isDistributedToMe && !$isDistributedToUser && !$reqApproved);

            // ✅ boleh request lagi jika status REJECTED atau APPROVED tapi expired (controller index hanya tahu expired dari expires_at)
            $expiredApproved = ($status === 'APPROVED' && !$reqApproved);
            $doc->can_request_again = ($status === 'REJECTED' || $expiredApproved);

            return $doc;
        });

        return view('documents.gallery.index', compact(
            'items',
            'q',
            'documentTypes',
            'departments',
            'filterJenisId',
            'filterDeptId',
            'lockDeptId'
        ));
    }

    public function read(Request $request, Document $document)
    {
        $me       = $request->user();
        $myDeptId = $me?->department_id;
        $myUserId = $me?->id;
        $isSuperadmin = $me && strcasecmp((string) optional($me->role)->name, 'Superadmin') === 0;

        if ($myUserId) {
            $document->notificationReaders()->syncWithoutDetaching([
                $myUserId => ['read_at' => now()],
            ]);
            rescue(fn () => DocumentNotificationUpdated::dispatch((string) $myUserId, (string) $document->id), report: true);
        }

        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            return redirect()->route('documents.gallery.index')->with('error', 'File not found.');
        }

        // owner dept
        $isOwnerDept = $myDeptId && ((string)$document->department_id === (string)$myDeptId);

        // distributed to my dept
        $isDistributedToMe = false;
        if ($myDeptId) {
            $isDistributedToMe = $document->distributedDepartments()
                ->where('departments.id', $myDeptId)
                ->exists();
        }

        $isDistributedToUser = $myUserId && $document->distributedUsers()
            ->where('users.id', $myUserId)
            ->exists();

        $hasDistribution = $document->distributedDepartments()->exists() || $document->distributedUsers()->exists();

        // latest request
        $latestReq = null;
        if ($myUserId) {
            $latestReq = DB::table('document_access_requests')
                ->where('requester_user_id', $myUserId)
                ->where('document_id', $document->id)
                ->orderByDesc('created_at')
                ->first();
        }

        $status = $latestReq?->status ? strtoupper(trim((string)$latestReq->status)) : null;
        if ($status === 'REJECT') $status = 'REJECTED';

        $isApproved = ($status === 'APPROVED');
        if ($isApproved && !empty($latestReq?->access_expires_at)) {
            $exp = Carbon::parse($latestReq->access_expires_at);
            if ($exp->isPast()) $isApproved = false;
        }

        $isUnlocked = ($isSuperadmin || $isOwnerDept || $isDistributedToMe || $isDistributedToUser || $isApproved);

        $openCandidates = [
            'documents.access-requests.open',
            'documents.access-request.open',
            'documents.open',
        ];
        $pendingCandidates = [
            'documents.access-requests.pending',
            'documents.access-request.pending',
            'documents.pending',
        ];

        $openView    = collect($openCandidates)->first(fn($v) => view()->exists($v));
        $pendingView = collect($pendingCandidates)->first(fn($v) => view()->exists($v));

        if (!$openView) abort(500, 'Open view not found.');
        if (!$pendingView) abort(500, 'Pending view not found.');

        if ($isUnlocked) {
            $accessSource = $isOwnerDept ? 'OWNER' : (($isDistributedToMe || $isDistributedToUser) ? 'DISTRIBUTION' : 'APPROVAL');
            $hasTimer = ($accessSource === 'APPROVAL');

            $validUntil = null;
            $remainingSeconds = null;

            // timer hanya untuk approval
            if ($hasTimer && !empty($latestReq?->access_expires_at)) {
                $validUntil = Carbon::parse($latestReq->access_expires_at);
                if ($validUntil->isPast()) {
                    $hasTimer = false;
                    $validUntil = null;
                } else {
                    $remainingSeconds = now()->diffInSeconds($validUntil, false);
                    if ($remainingSeconds < 0) $remainingSeconds = 0;
                }
            } else {
                $hasTimer = false;
            }

            return view($openView, [
                'document'         => $document,
                'accessSource'     => $accessSource,
                'hasTimer'         => $hasTimer,
                'validUntil'       => $validUntil,
                'remainingSeconds' => $remainingSeconds,
            ]);
        }

        // kalau PENDING => tampil pending (tanpa insert)
        if ($status === 'PENDING' && $latestReq) {
            return view($pendingView, [
                'document'      => $document,
                'accessRequest' => $latestReq,
            ]);
        }

        // ✅ selain pending (REJECTED / expired / belum ada) => buat request baru
        if (!$myUserId) {
            return redirect()->route('documents.gallery.index')->with('error', 'User tidak valid.');
        }

        $insert = [
            'requester_user_id' => $myUserId,
            'requester_department_id' => $myDeptId,
            'document_id'  => $document->id,
            'status'       => 'pending',
            'created_at'   => now(),
            'updated_at'   => now(),
        ];

        if (Schema::hasColumn('document_access_requests', 'id')) {
            $insert['id'] = (string) Str::uuid();
        }

        DB::table('document_access_requests')->insert($insert);

        // Beri tahu approver secara realtime bahwa ada permintaan baru.
        $approverIds = \App\Models\User::whereHas('roles.permissions', function ($q) {
            $q->where('name', 'documents.access-approvals.view')->where('guard_name', 'web');
        })->pluck('id');
        foreach ($approverIds as $approverId) {
            rescue(fn () => DocumentNotificationUpdated::dispatch((string) $approverId, (string) $document->id), report: true);
        }

        $newReq = DB::table('document_access_requests')
            ->where('requester_user_id', $myUserId)
            ->where('document_id', $document->id)
            ->orderByDesc('created_at')
            ->first();

        return view($pendingView, [
            'document'      => $document,
            'accessRequest' => $newReq,
        ]);
    }

    public function raw(Request $request, Document $document)
    {
        $me       = $request->user();
        $myDeptId = $me?->department_id;
        $myUserId = $me?->id;

        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File not found.');
        }

        $isOwnerDept = $myDeptId && ((string)$document->department_id === (string)$myDeptId);

        $isDistributedToMe = false;
        if ($myDeptId) {
            $isDistributedToMe = $document->distributedDepartments()
                ->where('departments.id', $myDeptId)
                ->exists();
        }

        $isDistributedToUser = $myUserId && $document->distributedUsers()
            ->where('users.id', $myUserId)
            ->exists();

        $isSuperadmin = $me && strcasecmp((string) optional($me->role)->name, 'Superadmin') === 0;
        $hasDistribution = $document->distributedDepartments()->exists() || $document->distributedUsers()->exists();

        $latestReq = null;
        if ($myUserId) {
            $latestReq = DB::table('document_access_requests')
                ->where('requester_user_id', $myUserId)
                ->where('document_id', $document->id)
                ->orderByDesc('created_at')
                ->first();
        }

        $status = $latestReq?->status ? strtoupper(trim((string)$latestReq->status)) : null;
        if ($status === 'REJECT') $status = 'REJECTED';

        $isApproved = ($status === 'APPROVED');
        if ($isApproved && !empty($latestReq?->access_expires_at)) {
            $exp = Carbon::parse($latestReq->access_expires_at);
            if ($exp->isPast()) $isApproved = false;
        }

        $isUnlocked = ($isSuperadmin || $isOwnerDept || $isDistributedToMe || $isDistributedToUser || $isApproved);
        if (!$isUnlocked) abort(403, 'Unauthorized.');

        return Storage::disk('public')->response(
            $document->file_path,
            basename($document->file_path),
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . basename($document->file_path) . '"',
            ]
        );
    }
}
