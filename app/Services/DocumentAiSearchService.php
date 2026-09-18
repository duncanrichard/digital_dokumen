<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Http;

class DocumentAiSearchService
{
    public function search(string $query, int $limit = 8, $user = null): array
    {
        $query = trim($query);
        if ($query === '') return [];

        $terms = preg_split('/\s+/u', mb_strtolower($query), -1, PREG_SPLIT_NO_EMPTY);
        // Pencarian AI mencakup seluruh revisi, termasuk R0 yang sudah tidak aktif.
        $candidates = Document::query()->with('department:id,name')
            ->where(function ($q) use ($terms) {
                $q->where(function ($all) use ($terms) {
                foreach ($terms as $term) {
                    $like = '%' . addcslashes($term, '%_') . '%';
                    $all->orWhere(function ($w) use ($like) {
                        $w->whereRaw('LOWER(name) LIKE ?', [$like])
                          ->orWhereRaw('LOWER(document_number) LIKE ?', [$like])
                          ->orWhereRaw('LOWER(COALESCE(notes, \'\')) LIKE ?', [$like]);
                    });
                }
                });
            })->latest('publish_date')->limit(30)->get();

        // Jika satu revisi cocok, ikutkan seluruh revisi dengan nomor dokumen yang sama.
        $numbers = $candidates->pluck('document_number')->filter()->unique()->values();
        if ($numbers->isNotEmpty()) {
            $allRevisions = Document::query()->with('department:id,name')
                ->whereIn('document_number', $numbers)
                ->orderByDesc('revision')->get();
            $candidates = $candidates->concat($allRevisions)->unique('id')->sortByDesc('revision')->values();
        }

        if ($candidates->isEmpty() || !config('openrouter.key')) return $candidates->take($limit)->values()->all();

        $catalog = $candidates->map(fn ($d) => ['id' => $d->id, 'title' => $d->name, 'number' => $d->document_number, 'revision' => 'R' . ($d->revision ?? 0), 'description' => $d->notes, 'department' => $d->department?->name])->values();
        try {
        $response = Http::withToken(config('openrouter.key'))->withoutVerifying()->acceptJson()->post(rtrim(config('openrouter.base_url'), '/') . '/chat/completions', [
            'model' => config('openrouter.model'), 'temperature' => 0,
            'messages' => [['role' => 'system', 'content' => 'Return JSON only: {"ids":[document ids ordered by relevance]}. Select only ids from the catalog.'], ['role' => 'user', 'content' => "Query: {$query}\nCatalog:\n" . $catalog->toJson()]],
        ]);
        } catch (\Throwable $e) {
            report($e);
            return $candidates->take($limit)->values()->all();
        }
        $ids = data_get($response->json(), 'choices.0.message.content');
        $ids = is_string($ids) ? json_decode(trim(preg_replace('/^```json|```$/m', '', $ids)), true) : null;
        $ordered = collect($ids['ids'] ?? [])->map('strval')->values();
        if ($ordered->isEmpty()) return $candidates->take($limit)->values()->all();
        return $ordered->map(fn ($id) => $candidates->firstWhere('id', $id))->filter()->take($limit)->values()->all();
    }
}
