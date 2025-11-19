<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Document;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use App\Services\DocumentWatermarkService;

class DocumentDistributionMail extends Mailable
{
    use Queueable, SerializesModels;

    public $document;
    public $department;
    public $user;

    public function __construct(Document $document, Department $department, User $user)
    {
        $this->document   = $document;
        $this->department = $department;
        $this->user       = $user;
    }

    public function build()
    {
        $email = $this->subject('Distribusi Dokumen: ' . $this->document->name)
                      ->markdown('emails.documents.distribution');

        if ($this->document->file_path && Storage::disk('public')->exists($this->document->file_path)) {

            /** @var DocumentWatermarkService $watermarkService */
            $watermarkService = app(DocumentWatermarkService::class);

            // >>>> Inilah bedanya: pakai file yang SUDAH watermarked
            $watermarkedPath = $watermarkService->makeWatermarkedCopy(
                $this->document,
                $this->department,
                $this->user
            );

            $email->attach(
                $watermarkedPath,
                [
                    'as'   => $this->document->document_number . '.pdf',
                    'mime' => 'application/pdf',
                ]
            );
        }

        return $email;
    }
}
