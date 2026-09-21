<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\DocumentAiSearchService;
use Illuminate\Console\Command;

class IndexDocumentsForAi extends Command
{
    protected $signature = 'documents:ai-index {--id= : Indeks hanya satu UUID dokumen}';
    protected $description = 'Membaca PDF dan membangun indeks pencarian AI lokal';

    public function handle(DocumentAiSearchService $ai): int
    {
        $query = Document::query()->orderBy('created_at');
        if ($id = $this->option('id')) $query->whereKey($id);
        $total = $query->count();
        $bar = $this->output->createProgressBar($total);
        $failed = 0;
        $query->each(function (Document $document) use ($ai, $bar, &$failed) {
            if (!$ai->index($document)) $failed++;
            $bar->advance();
        });
        $bar->finish(); $this->newLine();
        $success = $total - $failed;
        $this->info("Selesai: {$success}/{$total} dokumen terindeks.");
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
