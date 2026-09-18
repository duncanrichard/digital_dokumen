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

        $visible = Document::query()
            ->where('is_active', true)
            ->when($departmentId, fn ($query) => $query->where(function ($scope) use ($departmentId, $user) {
                $scope->where('department_id', $departmentId)
                    ->orWhereHas('distributedDepartments', fn ($departments) => $departments->where('departments.id', $departmentId))
                    ->orWhereHas('distributedUsers', fn ($users) => $users->where('users.id', $user->id));
            }));

        $stats = [
            'active' => Document::where('is_active', true)->count(),
            'visible' => (clone $visible)->count(),
            'distributed' => Document::whereHas('distributedDepartments')->orWhereHas('distributedUsers')->count(),
            'pending' => DocumentAccessRequest::whereRaw('LOWER(status) = ?', ['pending'])->count(),
        ];

        $recentDocuments = (clone $visible)
            ->with(['jenisDokumen:id,kode,nama', 'department:id,code,name'])
            ->orderByDesc('publish_date')
            ->limit(6)
            ->get();

        return view('documents.control.index', compact('stats', 'recentDocuments'));
    }
}
