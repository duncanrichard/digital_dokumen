<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JenisDokumen;
use App\Models\Department;
use App\Models\Document;
use App\Models\WatermarkSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use setasign\Fpdi\Fpdi;

class DocumentUploadController extends Controller
{
    // ================== LIST / FILTER ==================
    public function index(Request $request)
    {
        $q             = trim((string) $request->get('q'));
        $filterJenisId = $request->get('document_type_id'); // uuid
        $filterDeptId  = $request->get('department_id');    // uuid

        // Ambil dept user login (boleh null untuk admin)
        $me         = $request->user();
        $myDeptId   = $me?->department_id;      // uuid | null
        $lockDeptId = $myDeptId;                // jika ada, kita kunci filter departemen

        // Dropdown Document Types (selalu tersedia)
        $documentTypes = JenisDokumen::where('is_active', true)
            ->orderBy('nama')
            ->get(['id','kode','nama']);

        // Dropdown Departments:
        // - Jika user punya dept → hanya dept itu yang tampil
        // - Jika tidak punya dept → tampil semua dept aktif
        $departments = Department::when($lockDeptId, fn($q) => $q->where('id', $lockDeptId))
            ->when(!$lockDeptId, fn($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->get(['id','code','name']);

        // Jika user punya dept & tidak memilih department_id di filter, set default ke dept user
        if ($lockDeptId && empty($filterDeptId)) {
            $filterDeptId = $lockDeptId;
        }

        $items = Document::with([
                'jenisDokumen:id,kode,nama',
                'department:id,code,name',
                'distributedDepartments:id'
            ])
            // Pencarian teks
            ->when($q !== '', function ($query) use ($q) {
                $needle = mb_strtolower($q);
                $query->where(function ($sub) use ($needle) {
                    $sub->whereRaw('LOWER(name) LIKE ?', ["%{$needle}%"])
                        ->orWhereRaw('LOWER(document_number) LIKE ?', ["%{$needle}%"])
                        ->orWhereHas('jenisDokumen', function ($q2) use ($needle) {
                            $q2->whereRaw('LOWER(kode) LIKE ?', ["%{$needle}%"])
                               ->orWhereRaw('LOWER(nama) LIKE ?', ["%{$needle}%"]);
                        })
                        ->orWhereHas('department', function ($q3) use ($needle) {
                            $q3->whereRaw('LOWER(code) LIKE ?', ["%{$needle}%"])
                               ->orWhereRaw('LOWER(name) LIKE ?', ["%{$needle}%"]);
                        });
                });
            })
            // Filter dari form
            ->when($filterJenisId, fn($q2) => $q2->where('jenis_dokumen_id', $filterJenisId))
            ->when($filterDeptId,  fn($q3) => $q3->where('department_id',    $filterDeptId))

            // PEMBATASAN BERDASARKAN DEPT USER YANG LOGIN
            ->when($lockDeptId, function ($q) use ($lockDeptId) {
                $q->where(function ($sub) use ($lockDeptId) {
                    $sub->where('department_id', $lockDeptId)
                        ->orWhereHas('distributedDepartments', function ($qq) use ($lockDeptId) {
                            $qq->where('departments.id', $lockDeptId);
                        });
                });
            })

            ->orderByDesc('publish_date')
            ->paginate(10)
            ->withQueryString();

        return view('documents.upload.index', compact(
            'items',
            'q',
            'documentTypes',
            'departments',
            'filterJenisId',
            'filterDeptId',
            'lockDeptId'
        ));
    }

    // ================== STORE (BARU / REVISI) ==================
    public function store(Request $request)
    {
        $validated = $request->validate([
            'document_type_id'           => ['nullable','uuid','exists:jenis_dokumen,id'],
            'department_id'              => ['nullable','uuid','exists:departments,id'],
            'document_name'              => ['required','string','max:255'],
            'publish_date'               => ['required','date'],
            'file'                       => ['required','file','mimes:pdf','max:10240'],
            'is_active'                  => ['nullable','in:1'],
            'distribute_mode'            => ['nullable','in:all,selected'],
            'distribution_departments'   => ['array'],
            'distribution_departments.*' => ['uuid','exists:departments,id'],
            'revise_of'                  => ['nullable','uuid','exists:documents,id'],
        ]);

        $storedPath = $request->file('file')->store('documents', 'public');

        // === MODE REVISI ===
        if (!empty($validated['revise_of'])) {
            $base = Document::with(['jenisDokumen:id,kode', 'department:id,code'])
                ->findOrFail($validated['revise_of']);

            $maxRevision  = (int) Document::where('document_number', $base->document_number)->max('revision');
            $nextRevision = $maxRevision + 1;

            DB::transaction(function () use ($validated, $request, $base, $storedPath, $nextRevision) {
                // Nonaktifkan semua versi lama
                Document::where('document_number', $base->document_number)
                    ->update(['is_active' => false]);

                // Buat versi baru
                $doc = Document::create([
                    'jenis_dokumen_id' => $base->jenis_dokumen_id,
                    'department_id'    => $base->department_id,
                    'sequence'         => $base->sequence,
                    'document_number'  => $base->document_number,
                    'revision'         => $nextRevision,
                    'name'             => $validated['document_name'],
                    'publish_date'     => $validated['publish_date'],
                    'file_path'        => $storedPath,
                    'is_active'        => $request->boolean('is_active', true),
                    'read_notifikasi'  => 0,
                ]);

                // Distribusi
                $mode = $validated['distribute_mode'] ?? 'all';
                if ($mode === 'all') {
                    $allDeptIds = Department::where('is_active', true)->pluck('id')->all();
                    $doc->distributedDepartments()->sync($allDeptIds);
                } else {
                    $selected = collect($validated['distribution_departments'] ?? [])->unique()->values()->all();
                    $doc->distributedDepartments()->sync($selected);
                }
            });

            $displayNumber = "{$base->document_number} R{$nextRevision}";
            return redirect()->route('documents.index')
                ->with('success', "Document revised. New number: {$displayNumber}");
        }

        // === MODE BARU (R0) ===
        $jenis = JenisDokumen::select('id','kode')->findOrFail($validated['document_type_id']);
        $dept  = Department::select('id','code')->findOrFail($validated['department_id']);
        $year  = Carbon::parse($validated['publish_date'])->format('Y');

        $nextSequence = (int) Document::where('jenis_dokumen_id', $jenis->id)
            ->where('department_id', $dept->id)
            ->max('sequence');
        $nextSequence = $nextSequence ? $nextSequence + 1 : 1;

        $documentNumber = "{$jenis->kode}-{$dept->code}/{$nextSequence}/{$year}";

        DB::transaction(function () use ($validated, $jenis, $dept, $nextSequence, $documentNumber, $storedPath, $request) {
            $doc = Document::create([
                'jenis_dokumen_id' => $jenis->id,
                'department_id'    => $dept->id,
                'sequence'         => $nextSequence,
                'document_number'  => $documentNumber,
                'revision'         => 0,
                'name'             => $validated['document_name'],
                'publish_date'     => $validated['publish_date'],
                'file_path'        => $storedPath,
                'is_active'        => $request->boolean('is_active', true),
                'read_notifikasi'  => 0,
            ]);

            // Distribusi
            $mode = $validated['distribute_mode'] ?? 'all';
            if ($mode === 'all') {
                $allDeptIds = Department::where('is_active', true)->pluck('id')->all();
                $doc->distributedDepartments()->sync($allDeptIds);
            } else {
                $selected = collect($validated['distribution_departments'] ?? [])->unique()->values()->all();
                $doc->distributedDepartments()->sync($selected);
            }
        });

        return redirect()->route('documents.index')
            ->with('success', "Document has been uploaded. Number: {$documentNumber} R0");
    }

    // ================== EDIT FORM ==================
    public function edit(Document $document)
    {
        $documentTypes = JenisDokumen::where('is_active', true)
            ->orderBy('nama')->get(['id','kode','nama']);

        $departments = Department::where('is_active', true)
            ->orderBy('name')->get(['id','code','name']);

        $selectedDistribution = $document->distributedDepartments()->pluck('departments.id')->all();

        return view('documents.upload.edit', compact(
            'document','documentTypes','departments','selectedDistribution'
        ));
    }

    // ================== UPDATE ==================
    public function update(Request $request, Document $document)
    {
        $validated = $request->validate([
            'document_type_id'           => ['required','uuid','exists:jenis_dokumen,id'],
            'department_id'              => ['required','uuid','exists:departments,id'],
            'document_name'              => ['required','string','max:255'],
            'publish_date'               => ['required','date'],
            'file'                       => ['nullable','file','mimes:pdf','max:10240'],
            'is_active'                  => ['nullable','in:1'],
            'distribute_mode'            => ['nullable','in:all,selected'],
            'distribution_departments'   => ['array'],
            'distribution_departments.*' => ['uuid','exists:departments,id'],
        ]);

        $jenis = JenisDokumen::select('id','kode')->findOrFail($validated['document_type_id']);
        $dept  = Department::select('id','code')->findOrFail($validated['department_id']);

        $oldPath = $document->file_path;
        $newPath = $request->hasFile('file')
            ? $request->file('file')->store('documents', 'public')
            : null;

        $renumber = ($document->jenis_dokumen_id !== $jenis->id) || ($document->department_id !== $dept->id);

        DB::transaction(function () use ($request, $validated, $document, $jenis, $dept, $newPath, $oldPath, $renumber) {
            $payload = [
                'jenis_dokumen_id' => $jenis->id,
                'department_id'    => $dept->id,
                'name'             => $validated['document_name'],
                'publish_date'     => $validated['publish_date'],
                'is_active'        => $request->boolean('is_active', true),
            ];

            if ($newPath) {
                $payload['file_path']       = $newPath;
                $payload['read_notifikasi'] = 0; // file baru: tampilkan notif
            }

            if ($renumber) {
                $year = Carbon::parse($validated['publish_date'])->format('Y');
                $nextSequence = (int) Document::where('jenis_dokumen_id', $jenis->id)
                    ->where('department_id', $dept->id)
                    ->max('sequence');
                $nextSequence = $nextSequence ? $nextSequence + 1 : 1;

                $payload['sequence']        = $nextSequence;
                $payload['document_number'] = "{$jenis->kode}-{$dept->code}/{$nextSequence}/{$year}";
                $payload['revision']        = 0;
            }

            $document->update($payload);

            if ($newPath && $oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }

            // Distribusi
            $mode = $validated['distribute_mode'] ?? 'all';
            if ($mode === 'all') {
                $allDeptIds = Department::where('is_active', true)->pluck('id')->all();
                $document->distributedDepartments()->sync($allDeptIds);
            } else {
                $selected = collect($validated['distribution_departments'] ?? [])->unique()->values()->all();
                $document->distributedDepartments()->sync($selected);
            }
        });

        return redirect()
            ->route('documents.index')
            ->with('success', 'Document updated successfully.');
    }

    // ================== DELETE ==================
    public function destroy(Document $document)
    {
        DB::transaction(function () use ($document) {
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }
            if (method_exists($document, 'distributedDepartments')) {
                $document->distributedDepartments()->detach();
            }
            $document->delete();
        });

        return redirect()
            ->route('documents.index')
            ->with('success', 'Document deleted.');
    }

    /**
     * OPEN: tandai notifikasi dibaca, lalu redirect ke stream (agar tampil watermark)
     */
    public function open(Document $document)
    {
        if (!$document->read_notifikasi) {
            $document->forceFill(['read_notifikasi' => 1])->save();
        }

        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File not found.');
        }

        // Redirect ke stream agar menggunakan watermark
        return redirect()->route('documents.file', $document->id);
    }

    public function markAllNotificationsRead()
    {
        Document::where('read_notifikasi', false)->update(['read_notifikasi' => true]);
        return back()->with('success', 'All notifications marked as read.');
    }

    // ================== STREAM (WITH WATERMARK) ==================
    public function stream(Document $document)
    {
        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File not found.');
        }

        $absolutePath = Storage::disk('public')->path($document->file_path);
        $setting = WatermarkSetting::first();
        $useWatermark = $setting && $setting->enabled;

        // Jika tidak ada watermark, file-kan langsung
        if (!$useWatermark) {
            $filename = basename($absolutePath);
            return response()->file($absolutePath, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
                'X-Accel-Buffering'   => 'no',
            ]);
        }

        // Render watermark on-the-fly
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($absolutePath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tplId = $pdf->importPage($pageNo);
            $size  = $pdf->getTemplateSize($tplId);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tplId);

            if ($setting->mode === 'image' && $setting->image_path) {
                $this->applyImageWatermark($pdf, $setting, $size['width'], $size['height']);
            } else {
                $this->applyTextWatermark($pdf, $setting, $size['width'], $size['height'], $document);
            }
        }

        $downloadName = basename($absolutePath);

        return new StreamedResponse(function() use ($pdf, $downloadName) {
            // Inline (I) agar tampil di tab
            $pdf->Output('I', $downloadName);
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$downloadName.'"',
        ]);
    }

    /** ---------- Helpers ---------- */

    protected function parseTemplateVars(string $tpl, ?Document $doc = null): string
    {
        $u = Auth::user();
        $replacements = [
            '{user.name}'     => $u?->name ?? '',
            '{user.username}' => $u?->username ?? '',
            '{date}'          => now()->format('Y-m-d'),
            '{datetime}'      => now()->format('Y-m-d H:i'),
        ];

        if ($doc) {
            $replacements['{doc.name}']     = $doc->name ?? '';
            $replacements['{doc.number}']   = $doc->document_number ?? '';
            $replacements['{doc.revision}'] = (string) ($doc->revision ?? '');
            $replacements['{doc.dept}']     = optional($doc->department)->code ?? '';
            $replacements['{doc.type}']     = optional($doc->jenisDokumen)->kode ?? '';
        }

        return strtr($tpl, $replacements);
    }

    protected function applyTextWatermark(Fpdi $pdf, WatermarkSetting $s, $w, $h, ?Document $doc = null)
    {
        [$r, $g, $b] = $this->hexToRgb($s->color_hex ?? '#A0A0A0');

        $text = $this->parseTemplateVars($s->text_template ?: 'CONFIDENTIAL', $doc);
        $pdf->SetFont('Helvetica', 'B', (int) ($s->font_size ?? 28));
        $pdf->SetTextColor($r, $g, $b);

        // Rotasi: gunakan jika tersedia (FPDI tertentu punya StartTransform/Rotate)
        $this->rotateStart($pdf, (float) ($s->rotation ?? 45), $w/2, $h/2);

        if ($s->repeat) {
            $step = max(200, (int) ($s->font_size * 6));
            for ($y = -$h; $y <= $h*2; $y += $step) {
                $pdf->Text($w/2 - $pdf->GetStringWidth($text)/2, $y, $text);
            }
        } else {
            [$x, $y] = $this->calcPosition(
                $s->position ?? 'center',
                $w, $h,
                $pdf->GetStringWidth($text),
                (int) ($s->font_size ?? 28)
            );
            $pdf->Text($x, $y, $text);
        }

        $this->rotateStop($pdf);
    }

    protected function applyImageWatermark(Fpdi $pdf, WatermarkSetting $s, $w, $h)
    {
        $path = public_path($s->image_path); // karena kita simpan 'storage/...'
        if (!file_exists($path)) return;

        $imgW = $w * 0.5; // skala 50% lebar halaman
        $imgH = 0;        // biar auto-scale

        $this->rotateStart($pdf, (float) ($s->rotation ?? 45), $w/2, $h/2);

        if ($s->repeat) {
            $step = max(300, (int)($w*0.7));
            for ($y = -$h; $y <= $h*2; $y += $step) {
                $pdf->Image($path, $w/2 - $imgW/2, $y, $imgW, $imgH);
            }
        } else {
            [$x, $y] = $this->calcPositionImage($s->position ?? 'center', $w, $h, $imgW, $imgH);
            $pdf->Image($path, $x, $y, $imgW, $imgH);
        }

        $this->rotateStop($pdf);
    }

    protected function hexToRgb($hex): array
    {
        $hex = str_replace('#','',$hex);
        if (strlen($hex) === 8) $hex = substr($hex,0,6); // abaikan alpha
        if (strlen($hex) !== 6) return [160,160,160];
        return [hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2))];
    }

    protected function calcPosition(string $pos, float $w, float $h, float $textW, float $fontSize): array
    {
        $margin = 20;
        switch ($pos) {
            case 'top-left':     return [$margin, $margin + $fontSize];
            case 'top-right':    return [$w - $textW - $margin, $margin + $fontSize];
            case 'bottom-left':  return [$margin, $h - $margin];
            case 'bottom-right': return [$w - $textW - $margin, $h - $margin];
            case 'center':
            default:             return [($w - $textW)/2, $h/2];
        }
    }

    protected function calcPositionImage(string $pos, float $w, float $h, float $imgW, float $imgH): array
    {
        $margin = 20;
        $x = $margin; $y = $margin;
        switch ($pos) {
            case 'top-left':     $x=$margin;               $y=$margin; break;
            case 'top-right':    $x=$w-$imgW-$margin;      $y=$margin; break;
            case 'bottom-left':  $x=$margin;               $y=$h-$imgH-$margin; break;
            case 'bottom-right': $x=$w-$imgW-$margin;      $y=$h-$imgH-$margin; break;
            case 'center':
            default:             $x=($w-$imgW)/2;          $y=($h-$imgH)/2; break;
        }
        return [$x, $y];
    }

    /**
     * Wrapper rotasi agar aman jika metode tidak tersedia pada FPDF/FPDI yang terpasang
     */
    protected function rotateStart(Fpdi $pdf, float $angle, float $cx, float $cy): void
    {
        if (method_exists($pdf, 'StartTransform') && method_exists($pdf, 'Rotate')) {
            $pdf->StartTransform();
            $pdf->Rotate($angle, $cx, $cy);
        }
        // jika tidak ada, biarkan tanpa rotasi (fallback)
    }

    protected function rotateStop(Fpdi $pdf): void
    {
        if (method_exists($pdf, 'StopTransform')) {
            $pdf->StopTransform();
        }
    }
}
