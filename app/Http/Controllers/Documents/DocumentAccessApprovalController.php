<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\DocumentAccessRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentAccessApprovalController extends Controller
{
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
