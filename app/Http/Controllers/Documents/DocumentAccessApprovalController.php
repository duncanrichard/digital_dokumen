<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\DocumentAccessRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentAccessApprovalController extends Controller
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

            // cek permission pada ROLE (bukan $user->can)
            // nama permission HARUS sama dengan di seeder
            $hasPermission = $role && $role->hasPermissionTo('documents.access-approvals.view');

            if (! $hasPermission) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses halaman Persetujuan Akses Dokumen.');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $status = $request->get('status'); // optional filter: pending/approved/rejected

        $items = DocumentAccessRequest::with([
                'user:id,name,username,department_id',
                'document:id,document_number,name,department_id',
                'document.department:id,code,name',
                'decider:id,name',
            ])
            ->when($status, fn($q) => $q->where('status', $status))
            ->orderByDesc('requested_at')
            ->paginate(20)
            ->withQueryString();

        return view('documents.access-approvals.index', compact('items', 'status'));
    }

    public function approve(Request $request, DocumentAccessRequest $accessRequest)
    {
        if ($accessRequest->status !== 'pending') {
            return back()->with('error', 'Permintaan ini sudah diproses.');
        }

        $validated = $request->validate([
            'expires_at' => ['nullable', 'date'],
        ]);

        $accessRequest->update([
            'status'     => 'approved',
            'decided_by' => Auth::id(),
            'decided_at' => now(),
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return back()->with('success', 'Permintaan akses sudah disetujui.');
    }

    public function reject(DocumentAccessRequest $accessRequest)
    {
        if ($accessRequest->status !== 'pending') {
            return back()->with('error', 'Permintaan ini sudah diproses.');
        }

        $accessRequest->update([
            'status'     => 'rejected',
            'decided_by' => Auth::id(),
            'decided_at' => now(),
        ]);

        return back()->with('success', 'Permintaan akses sudah ditolak.');
    }
}
