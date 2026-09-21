<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentAccessRequest;
use Illuminate\Http\Request;

class DocumentControlController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $departmentId = $user?->department_id;
        $isSuperadmin = $user && strcasecmp((string) optional($user->role)->name, 'Superadmin') === 0;

        $visible = Document::query()
            ->where('is_active', true)
            ->when(! $isSuperadmin, fn ($query) => $query->where(function ($scope) use ($departmentId, $user) {
                $scope->whereHas('distributedUsers', fn ($users) => $users->where('users.id', $user->id));
                if ($departmentId) {
                    $scope->orWhere('department_id', $departmentId)
                        ->orWhereHas('distributedDepartments', fn ($departments) => $departments->where('departments.id', $departmentId));
                }
            }));

        $stats = [
            'active' => (clone $visible)->count(),
            'visible' => (clone $visible)->count(),
            'distributed' => (clone $visible)->where(function ($documents) {
                $documents->whereHas('distributedDepartments')->orWhereHas('distributedUsers');
            })->count(),
            'pending' => DocumentAccessRequest::whereRaw('LOWER(status) = ?', ['pending'])
                ->whereIn('document_id', (clone $visible)->select('id'))
                ->count(),
        ];

        $recentDocuments = (clone $visible)
            ->with(['jenisDokumen:id,kode,nama', 'department:id,code,name'])
            ->orderByDesc('publish_date')
            ->limit(6)
            ->get();

        return view('documents.control.index', compact('stats', 'recentDocuments'));
    }
}
