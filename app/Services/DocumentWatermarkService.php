<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Department;
use App\Models\User;
use App\Models\WatermarkSetting;
use Illuminate\Support\Facades\Storage;
use App\Services\PdfWithRotation;

class DocumentWatermarkService
{
    /**
     * Generate copy PDF yang sudah diberi watermark.
     * Kalau watermark tidak aktif atau file tidak ada, kembalikan path asli.
     *
     * @return string full path file di disk (bisa langsung dipakai untuk attach)
     */
    public function makeWatermarkedCopy(Document $document, ?Department $department = null, ?User $user = null): string
    {
        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            // fallback: path asli (bisa saja null)
            return Storage::disk('public')->path($document->file_path);
        }

        $originalPath = Storage::disk('public')->path($document->file_path);

        // ambil setting watermark
        $setting = WatermarkSetting::query()->first();

        // kalau tidak ada setting / disabled → pakai file asli
        if (!$setting || !$setting->enabled) {
            return $originalPath;
        }

        // nama file sementara
        $tempFileName = 'wm_'
            . ($document->document_number ?: $document->id)
            . '_' . uniqid() . '.pdf';

        $tempRelativePath = 'temp/' . $tempFileName;
        $tempFullPath     = storage_path('app/public/' . $tempRelativePath);

        if (!is_dir(dirname($tempFullPath))) {
            mkdir(dirname($tempFullPath), 0775, true);
        }

        // ----- Build text watermark dari template -----
        $text = 'CONFIDENTIAL';
        if ($setting->mode === 'text' && $setting->text_template) {
            $text = strtr($setting->text_template, [
                '{user_name}'       => optional($user)->name ?? '',
                '{user_username}'   => optional($user)->username ?? '',
                '{department}'      => optional($department)->name ?? '',
                '{document_number}' => $document->document_number ?? '',
                '{document_name}'   => $document->name ?? '',
            ]);
        }

        // warna hex → RGB sederhana (abaikan alpha dulu)
        $hex = ltrim($setting->color_hex, '#');
        if (strlen($hex) === 8) {
            $hex = substr($hex, 0, 6);
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $fontSize = $setting->font_size ?: 32;
        $angle    = $setting->rotation ?: 0;

        // ----- PROSES PDF MENGGUNAKAN FPDI -----
     $pdf = new PdfWithRotation();

        $pageCount = $pdf->setSourceFile($originalPath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tplId = $pdf->importPage($pageNo);
            $size  = $pdf->getTemplateSize($tplId);

            $orientation = $size['width'] > $size['height'] ? 'L' : 'P';

            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($tplId);

            // set font dan warna watermark
            $pdf->SetFont('Helvetica', 'B', $fontSize);
            $pdf->SetTextColor($r, $g, $b);

            // hitung posisi (sederhana: tengah halaman)
            $x = $size['width'] / 2;
            $y = $size['height'] / 2;

            // rotasi sederhana
            if ($angle != 0) {
                $pdf->Rotate($angle, $x, $y); // butuh extension untuk Rotate, kalau tidak ada hapus saja
            }

            // tulis watermark
            $pdf->SetXY($x - ($pdf->GetStringWidth($text) / 2), $y);
            $pdf->Cell($pdf->GetStringWidth($text), 10, $text);

            // reset rotasi
            if ($angle != 0) {
                $pdf->Rotate(0);
            }
        }

        $pdf->Output($tempFullPath, 'F');

        return $tempFullPath;
    }
}
