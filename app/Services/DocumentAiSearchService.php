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
            if ($ids->isEmpty()) return $this->fallback($query, $limit)->all();

            $documents = Document::with('department:id,name')->whereIn('id', $ids)->get()->keyBy('id');
            return $ranked->map(function ($result) use ($documents) {
                $document = $documents->get($result['document_id']);
                if (!$document) return null;
                $document->setAttribute('ai_score', $result['score'] ?? null);
                $document->setAttribute('ai_excerpt', $result['excerpt'] ?? null);
                return $document;
            })->filter()->take($limit)->values()->all();
        } catch (\Throwable $exception) {
            report($exception);
            return $this->fallback($query, $limit)->all();
        }
    }

    public function index(Document $document): bool
    {
        $path = storage_path('app/public/'.$document->file_path);
        if (!$document->file_path || !is_file($path)) return false;
        try {
            $request = Http::acceptJson()->timeout(config('document_ai.timeout'));
            if ($token = config('document_ai.token')) $request = $request->withToken($token);
            $request->post(config('document_ai.url').'/index', [
                'document_id' => (string) $document->id, 'file_path' => $path,
                'title' => $document->name, 'document_number' => $document->document_number,
            ])->throw();
            return true;
        } catch (\Throwable $exception) { report($exception); return false; }
    }

    private function fallback(string $query, int $limit): Collection
    {
        return Document::with('department:id,name')->search($query)->latest('publish_date')->limit($limit)->get();
    }
}
