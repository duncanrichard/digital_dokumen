<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JenisDokumen;
use App\Models\Department;
use App\Models\Document;
use App\Models\WatermarkSetting;
use App\Models\DocumentAccessRequest;
use App\Models\DocumentAccessSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use setasign\Fpdi\Fpdi;

class DocumentUploadController extends Controller
{
    public function __construct()
    {
        // Semua action butuh user login
        $this->middleware(function ($request, $next) {
            $user = auth()->user();

            if (!$user) {
                abort(401);
            }

            // ==============================
            // 1. CEK SUPERADMIN DARI role_id
            // ==============================
            $roleName     = optional($user->role)->name; // relasi role() di model User
            $isSuperadmin = $roleName && strcasecmp($roleName, 'Superadmin') === 0;

            if ($isSuperadmin) {
                // Superadmin bebas akses semua method
                return $next($request);
            }

            // ========================================
            // 2. USER BIASA → CEK PERMISSION DARI ROLE
            // ========================================
            $role   = $user->role; // App\Models\Role (extends Spatie Role)
            $method = $request->route()->getActionMethod(); // nama method controller

            // Mapping method -> permission yang dibutuhkan
            $requiredPermission = null;

            switch ($method) {
                // Aksi-aksi yang hanya "view / akses" dokumen
                case 'index':
                case 'open':
                case 'stream':
                case 'rawFile':
                case 'markAllNotificationsRead':
                    $requiredPermission = 'documents.upload.view';
                    break;

                // Simpan dokumen baru / revisi
                case 'store':
                    $requiredPermission = 'documents.upload.create';
                    break;

                // Edit / update dokumen
                case 'edit':
                case 'update':
                    $requiredPermission = 'documents.upload.update';
                    break;

                // Hapus dokumen
                case 'destroy':
                    $requiredPermission = 'documents.upload.delete';
                    break;

                default:
                    // Default bisa kamu set ke view, atau dibiarkan null
                    $requiredPermission = 'documents.upload.view';
                    break;
            }

            if ($requiredPermission) {
                $hasPermission = $role && $role->hasPermissionTo($requiredPermission);

                if (!$hasPermission) {
                    abort(403, 'Anda tidak memiliki izin untuk mengakses fitur ini.');
                }
            }

            return $next($request);
        });
    }

    // ================== LIST / FILTER ==================
    public function index(Request $request)
    {
        $q             = trim((string) $request->get('q'));
        $filterJenisId = $request->get('document_type_id'); // uuid
        $filterDeptId  = $request->get('department_id');    // uuid

        // Ambil dept user login (boleh null untuk admin)
        $me         = $request->user();
        $myDeptId   = $me?->department_id;      // uuid | null
        $lockDeptId = $myDeptId;                // jika ada, kita kunci akses dokumen hanya utk divisi ini

        // Dropdown Document Types (selalu tersedia)
        $documentTypes = JenisDokumen::where('is_active', true)
            ->orderBy('nama')
            ->get(['id','kode','nama']);

        // Dropdown Departments:
        // - Jika user punya dept → hanya dept itu yang tampil
        // - Jika tidak punya dept → tampil semua dept aktif
        $departments = Department::when($lockDeptId, fn($q2) => $q2->where('id', $lockDeptId))
            ->when(!$lockDeptId, fn($q3) => $q3->where('is_active', true))
            ->orderBy('name')
            ->get(['id','code','name']);

        // CATATAN PENTING:
        // Jangan lagi auto-set $filterDeptId = $lockDeptId.
        // Kalau dipaksa, filter "department_id = divisi user" akan membunuh dokumen
        // yang hanya datang dari distribusi (document_distributions).
        // Jadi, $filterDeptId sekarang murni dari request user saja.

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
            // Filter jenis dokumen dari form
            ->when($filterJenisId, fn($q2) => $q2->where('jenis_dokumen_id', $filterJenisId))

            // Filter DIVISI dari form:
            // artinya: dokumen milik divisi tsb ATAU didistribusikan ke divisi tsb.
            ->when($filterDeptId, function ($q3) use ($filterDeptId) {
                $q3->where(function ($sub) use ($filterDeptId) {
                    $sub->where('department_id', $filterDeptId)
                        ->orWhereHas('distributedDepartments', function ($qq) use ($filterDeptId) {
                            $qq->where('departments.id', $filterDeptId);
                        });
                });
            })

            // PEMBATASAN BERDASARKAN DIVISI USER YANG LOGIN (security gate)
            // User hanya boleh melihat:
            //  - dokumen milik divisinya sendiri, ATAU
            //  - dokumen dari divisi lain tapi didistribusikan ke divisinya.
            ->when($lockDeptId, function ($q4) use ($lockDeptId) {
                $q4->where(function ($sub) use ($lockDeptId) {
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
     * OPEN: tandai notifikasi dibaca, lalu redirect ke gate stream()
     */
    public function open(Document $document)
    {
        if (!$document->read_notifikasi) {
            $document->forceFill(['read_notifikasi' => 1])->save();
        }

        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File not found.');
        }

        return redirect()->route('documents.file', $document->id);
    }

    public function markAllNotificationsRead()
    {
        Document::where('read_notifikasi', false)->update(['read_notifikasi' => true]);
        return back()->with('success', 'All notifications marked as read.');
    }

    // ================== GATE: CEK AKSES, TENTUKAN BUKA TAB BARU / PENDING ==================
    public function stream(Document $document)
    {
        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File not found.');
        }

        // Di gate: kalau tidak punya akses → SEKALIGUS buat request pending
        $check = $this->checkDocumentAccess($document, true);

        // Jika belum di-approve → tampil halaman pending (tidak buka tab baru)
        if (!$check['allowed'] && $check['pending'] && $check['pendingRequest']) {
            return view('documents.access-requests.pending', [
                'document'      => $document,
                'accessRequest' => $check['pendingRequest'],
            ]);
        }

        // Sudah approved → tampil halaman kecil yang akan buka tab baru ke rawFile + tampil timer
        return view('documents.access-requests.open', [
            'document'         => $document,
            'remainingSeconds' => $check['remainingSeconds'], // bisa null kalau setting tidak pakai timer
        ]);
    }

    // ================== RAW FILE: DIPANGGIL DARI TAB BARU ==================
    public function rawFile(Document $document)
    {
        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File not found.');
        }

        // Di tab PDF: HANYA cek akses, JANGAN buat request pending baru
        $check = $this->checkDocumentAccess($document, false);

        if (!$check['allowed']) {
            // Kalau waktu habis / akses sudah tidak berlaku → balik ke library dengan pesan
            return redirect()
                ->route('documents.index')
                ->with('error', 'Waktu akses dokumen sudah habis. Silakan ajukan permintaan akses lagi jika diperlukan.');
        }

        $asDownload = request()->boolean('dl', false);

        $absolutePath = Storage::disk('public')->path($document->file_path);
        $setting      = WatermarkSetting::first();
        $useWatermark = $setting && $setting->enabled;

        // Jika tidak ada watermark, file-kan langsung
        if (!$useWatermark) {
            $filename    = basename($absolutePath);
            $disposition = $asDownload ? 'attachment' : 'inline';

            return response()->file($absolutePath, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
                'X-Accel-Buffering'   => 'no',
            ]);
        }

        // Render watermark on-the-fly
        $pdf       = new Fpdi();
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
        $dest         = $asDownload ? 'D' : 'I'; // D = attachment, I = inline

        return new StreamedResponse(function () use ($pdf, $downloadName, $dest) {
            $pdf->Output($dest, $downloadName);
        }, 200, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    // ================== HELPER: CEK AKSES DOKUMEN ==================
    /**
     * @param  \App\Models\Document  $document
     * @param  bool  $createPending  true = boleh buat request pending baru,
     *                               false = hanya cek (dipakai di rawFile)
     * @return array{
     *   allowed: bool,
     *   pending: bool,
     *   remainingSeconds: int|null,
     *   pendingRequest: \App\Models\DocumentAccessRequest|null
     * }
     */
    protected function checkDocumentAccess(Document $document, bool $createPending = true): array
    {
        $user = Auth::user();

        // Admin / user tanpa department → bebas akses
        if (!$user || !$user->department_id) {
            return [
                'allowed'          => true,
                'pending'          => false,
                'remainingSeconds' => null,
                'pendingRequest'   => null,
            ];
        }

        // Setting durasi akses
        $accessSetting  = DocumentAccessSetting::first();
        $useTimedAccess = $accessSetting && $accessSetting->enabled && $accessSetting->default_duration_minutes;

        // Ambil approval terakhir
        $approvedRequest = DocumentAccessRequest::where('user_id', $user->id)
            ->where('document_id', $document->id)
            ->where('status', 'approved')
            ->orderByDesc('decided_at')
            ->first();

        $hasApprovedAccess = false;
        $remainingSeconds  = null;

        if ($approvedRequest) {
            if ($useTimedAccess) {
                // Hitung batas waktu dari decided_at + default_duration_minutes
                $startTime  = $approvedRequest->decided_at ?? $approvedRequest->created_at;
                $validUntil = $startTime
                    ? (clone $startTime)->addMinutes($accessSetting->default_duration_minutes)
                    : now()->addMinutes($accessSetting->default_duration_minutes);

                // Simpan ke expires_at supaya kelihatan di log
                if (!$approvedRequest->expires_at || !$approvedRequest->expires_at->eq($validUntil)) {
                    $approvedRequest->expires_at = $validUntil;
                    $approvedRequest->save();
                }

                if ($validUntil->isFuture()) {
                    $hasApprovedAccess = true;
                    $remainingSeconds  = now()->diffInSeconds($validUntil, false);
                    if ($remainingSeconds < 0) {
                        $remainingSeconds = 0;
                    }
                }
            } else {
                // Tanpa pembatasan waktu
                $hasApprovedAccess = true;
            }
        }

        // Kalau tidak ada akses approved yang masih berlaku
        if (!$hasApprovedAccess) {
            $pendingRequest = null;

            if ($createPending) {
                // Dipanggil dari gate (stream) → boleh buat / ambil pending
                $pendingRequest = DocumentAccessRequest::firstOrCreate(
                    [
                        'user_id'     => $user->id,
                        'document_id' => $document->id,
                        'status'      => 'pending',
                    ],
                    [
                        'reason'       => null,
                        'requested_at' => now(),
                    ]
                );
            } else {
                // Dipanggil dari rawFile → JANGAN buat baru, cukup cek kalau ada pending lama
                $pendingRequest = DocumentAccessRequest::where('user_id', $user->id)
                    ->where('document_id', $document->id)
                    ->where('status', 'pending')
                    ->latest('requested_at')
                    ->first();
            }

            return [
                'allowed'          => false,
                'pending'          => (bool) $pendingRequest,
                'remainingSeconds' => null,
                'pendingRequest'   => $pendingRequest,
            ];
        }

        return [
            'allowed'          => true,
            'pending'          => false,
            'remainingSeconds' => $remainingSeconds,
            'pendingRequest'   => null,
        ];
    }

    /** ---------- Helpers watermark ---------- */

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

        $this->rotateStart($pdf, (float) ($s->rotation ?? 45), $w/2, $h/2);

        if ($s->repeat) {
            $step = max(200, (int) ($s->font_size * 6));
            for ($y = -$h * 1; $y <= $h * 2; $y += $step) {
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
        $path = public_path($s->image_path);
        if (!file_exists($path)) {
            return;
        }

        $imgW = $w * 0.5;
        $imgH = 0;

        $this->rotateStart($pdf, (float) ($s->rotation ?? 45), $w/2, $h/2);

        if ($s->repeat) {
            $step = max(300, (int) ($w * 0.7));
            for ($y = -$h; $y <= $h * 2; $y += $step) {
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
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) === 8) {
            $hex = substr($hex, 0, 6); // abaikan alpha
        }
        if (strlen($hex) !== 6) {
            return [160,160,160];
        }
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        ];
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
            default:             return [($w - $textW) / 2, $h / 2];
        }
    }

    protected function calcPositionImage(string $pos, float $w, float $h, float $imgW, float $imgH): array
    {
        $margin = 20;
        $x = $margin;
        $y = $margin;

        switch ($pos) {
            case 'top-left':
                $x = $margin;
                $y = $margin;
                break;
            case 'top-right':
                $x = $w - $imgW - $margin;
                $y = $margin;
                break;
            case 'bottom-left':
                $x = $margin;
                $y = $h - $imgH - $margin;
                break;
            case 'bottom-right':
                $x = $w - $imgW - $margin;
                $y = $h - $imgH - $margin;
                break;
            case 'center':
            default:
                $x = ($w - $imgW) / 2;
                $y = ($h - $imgH) / 2;
                break;
        }

        return [$x, $y];
    }

    protected function rotateStart(Fpdi $pdf, float $angle, float $cx, float $cy): void
    {
        if (method_exists($pdf, 'StartTransform') && method_exists($pdf, 'Rotate')) {
            $pdf->StartTransform();
            $pdf->Rotate($angle, $cx, $cy);
        }
    }

    protected function rotateStop(Fpdi $pdf): void
    {
        if (method_exists($pdf, 'StopTransform')) {
            $pdf->StopTransform();
        }
    }
}
