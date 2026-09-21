<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class DocumentAiSearchService
{
    public function search(string $query, int $limit = 8, $user = null): array
    {
        $query = trim($query);
        if ($query === '') return [];

        try {
            $request = Http::acceptJson()->timeout(config('document_ai.timeout'));
            if ($token = config('document_ai.token')) $request = $request->withToken($token);
            $response = $request->post(config('document_ai.url').'/search', ['query' => $query, 'limit' => $limit])->throw();
            $ranked = collect($response->json('results', []));
            $ids = $ranked->pluck('document_id')->filter()->values();
            if ($ids->isEmpty()) return $this->fallback($query, $limit, $user)->all();

            $documents = $this->visibleDocuments($user)->whereIn('id', $ids)->get()->keyBy('id');
            return $ranked->map(function ($result) use ($documents) {
                $document = $documents->get($result['document_id']);
                if (!$document) return null;
                $document->setAttribute('ai_score', $result['score'] ?? null);
                $document->setAttribute('ai_excerpt', $result['excerpt'] ?? null);
                return $document;
            })->filter()->take($limit)->values()->all();
        } catch (\Throwable $exception) {
            report($exception);
            return $this->fallback($query, $limit, $user)->all();
        }
    }

    public function index(Document $document): bool
    {
        $document->loadMissing('jenisDokumen:id,kode,nama', 'department:id,code,name');
        $path = storage_path('app/public/'.$document->file_path);
        if (!$document->file_path || !is_file($path)) return false;
        try {
            $request = Http::acceptJson()->timeout(config('document_ai.timeout'));
            if ($token = config('document_ai.token')) $request = $request->withToken($token);
            $request->post(config('document_ai.url').'/index', [
                'document_id' => (string) $document->id, 'file_path' => $path,
                'title' => $document->name, 'document_number' => $document->document_number,
                'document_type' => trim(($document->jenisDokumen?->kode ?? '').' '.($document->jenisDokumen?->nama ?? '')),
                'department' => trim(($document->department?->code ?? '').' '.($document->department?->name ?? '')),
                'publish_date' => optional($document->publish_date)->format('Y-m-d') ?? '',
                'notes' => $document->notes ?? '',
                'status' => $document->is_active ? 'aktif' : 'nonaktif',
            ])->throw();
            return true;
        } catch (\Throwable $exception) { report($exception); return false; }
    }

    private function fallback(string $query, int $limit, $user): Collection
    {
        return $this->visibleDocuments($user)->search($query)->latest('publish_date')->limit($limit)->get();
    }

    private function visibleDocuments($user)
    {
        $query = Document::with('department:id,name');
        if (!$user || strcasecmp((string) optional($user->role)->name, 'Superadmin') === 0) return $query;

        return $query->where(function ($allowed) use ($user) {
            $allowed->where('department_id', $user->department_id)
                ->orWhereHas('distributedDepartments', fn ($departments) => $departments->whereKey($user->department_id))
                ->orWhereHas('distributedUsers', fn ($users) => $users->whereKey($user->id))
                ->orWhereExists(function ($requests) use ($user) {
                    $requests->selectRaw('1')
                        ->from('document_access_requests')
                        ->whereColumn('document_access_requests.document_id', 'documents.id')
                        ->where('document_access_requests.requester_user_id', $user->id)
                        ->whereRaw("UPPER(document_access_requests.status) = 'APPROVED'")
                        ->where(function ($valid) {
                            $valid->whereNull('document_access_requests.access_expires_at')
                                ->orWhere('document_access_requests.access_expires_at', '>', now());
                        });
                });
        });
    }
}
