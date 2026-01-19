<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JenisDokumen;
use App\Models\Department;
use App\Models\Document;
use App\Models\Clinic;
use App\Models\WatermarkSetting;
use App\Models\DocumentAccessRequest;
use App\Models\DocumentAccessSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\Log;

class DocumentUploadController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user) abort(401);

            // 1) Superadmin bypass
            $roleName     = optional($user->role)->name;
            $isSuperadmin = $roleName && strcasecmp($roleName, 'Superadmin') === 0;
            if ($isSuperadmin) return $next($request);

            // 2) Role-based permission check
            $role   = $user->role;
            $method = $request->route()->getActionMethod();

            $requiredPermission = null;

            switch ($method) {
                case 'index':
                case 'open':
                case 'stream':
                case 'rawFile':
                case 'markAllNotificationsRead':
                    $requiredPermission = 'documents.upload.view';
                    break;

                case 'store':
                    $from = (string) $request->input('_from', '');

                    if ($from === 'change') {
                        $requiredPermission = 'documents.upload.change';
                    } elseif ($from === 'derive_clinic') {
                        $requiredPermission = 'documents.upload.derive_clinic';
                    } else {
                        $requiredPermission = 'documents.upload.create';
                    }
                    break;

                case 'edit':
                case 'update':
                    $requiredPermission = 'documents.upload.update';
                    break;

                case 'destroy':
                    $requiredPermission = 'documents.upload.delete';
                    break;

                default:
                    $requiredPermission = 'documents.upload.view';
                    break;
            }

            if ($requiredPermission) {
                $hasPermission = $role && $role->hasPermissionTo($requiredPermission);
                if (!$hasPermission) abort(403, 'Anda tidak memiliki izin untuk mengakses fitur ini.');
            }

            return $next($request);
        });
    }

    /**
     * ✅ Format nomer_dokumen: 001/002/025/026...
     */
    protected function formatNomerDokumen(int $seq, int $pad = 3): string
    {
        return str_pad((string) $seq, $pad, '0', STR_PAD_LEFT);
    }

    /**
     * ✅ Ambil next nomor dokumen berdasarkan:
     * jenis_dokumen_id + department_id + tahun
     *
     * - Sumber utama: MAX(nomer_dokumen::int)
     * - Fallback: MAX(sequence)
     * - Untuk create/change kita hanya hitung dokumen NON clinic (clinic_id IS NULL) agar turunan klinik tidak mengganggu urutan.
     */
    protected function nextNomerDokumenFor(string $jenisId, string $deptId, string $year): int
    {
        $base = Document::where('jenis_dokumen_id', $jenisId)
            ->where('department_id', $deptId)
            ->whereNull('clinic_id')                 // ✅ hanya base doc
            ->where('document_number', 'like', "%/{$year}%"); // aman jika ada suffix

        // 1) ambil dari nomer_dokumen
        $maxFromNomer = (int) (clone $base)
            ->selectRaw("MAX(NULLIF(nomer_dokumen,'')::int) as mx")
            ->value('mx');

        // 2) fallback dari sequence (kalau ada data lama belum kebackfill)
        $maxFromSeq = (int) (clone $base)->max('sequence');

        $max = max($maxFromNomer, $maxFromSeq);

        return $max > 0 ? ($max + 1) : 1;
    }

    // ================== LIST / FILTER ==================
    public function index(Request $request)
    {
        $q             = trim((string) $request->get('q'));
        $filterJenisId = $request->get('document_type_id');
        $filterDeptId  = $request->get('department_id');

        $me         = $request->user();
        $myDeptId   = $me?->department_id;
        $lockDeptId = $myDeptId;

        $documentTypes = JenisDokumen::where('is_active', true)
            ->orderBy('nama')
            ->get(['id', 'kode', 'nama']);

        $departments = Department::when($lockDeptId, fn($q2) => $q2->where('id', $lockDeptId))
            ->when(!$lockDeptId, fn($q3) => $q3->where('is_active', true))
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $clinics = Clinic::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        $items = Document::with([
                'jenisDokumen:id,kode,nama',
                'department:id,code,name',
                'clinic:id,code,name',
                'distributedDepartments:id',
                'changedToDocuments:id,document_number,revision',
                'changedFromDocuments:id,document_number,revision',
            ])
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
            ->when($filterJenisId, fn($q2) => $q2->where('jenis_dokumen_id', $filterJenisId))
            ->when($filterDeptId, function ($q3) use ($filterDeptId) {
                $q3->where(function ($sub) use ($filterDeptId) {
                    $sub->where('department_id', $filterDeptId)
                        ->orWhereHas('distributedDepartments', function ($qq) use ($filterDeptId) {
                            $qq->where('departments.id', $filterDeptId);
                        });
                });
            })
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
            'clinics',
            'filterJenisId',
            'filterDeptId',
            'lockDeptId'
        ));
    }

    // ================== STORE (BARU / REVISI / CHANGE / DERIVE CLINIC) ==================
    public function store(Request $request)
    {
        $from = (string) $request->input('_from', 'create');

        $rules = [
            'document_name' => ['required', 'string', 'max:255'],
            'publish_date'  => ['required', 'date'],
            'file'          => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'is_active'     => ['nullable', 'in:1'],
            'notes'         => ['nullable', 'string'],

            'distribute_mode'            => ['nullable', 'in:all,selected'],
            'distribution_departments'   => ['nullable', 'array'],
            'distribution_departments.*' => ['uuid', 'exists:departments,id'],

            'revise_of' => ['nullable', 'uuid', 'exists:documents,id'],
            'change_of' => ['nullable', 'uuid', 'exists:documents,id'],
            'derive_of' => ['nullable', 'uuid', 'exists:documents,id'],
            'clinic_id' => ['nullable', 'uuid', 'exists:clinics,id'],
        ];

        if ($from === 'derive_clinic') {
            $rules['derive_of'] = ['required', 'uuid', 'exists:documents,id'];
            $rules['clinic_id'] = ['required', 'uuid', 'exists:clinics,id'];

            $rules['document_type_id'] = ['nullable'];
            $rules['department_id']    = ['nullable'];
        } elseif ($request->filled('revise_of')) {
            $rules['revise_of'] = ['required', 'uuid', 'exists:documents,id'];

            $rules['document_type_id'] = ['nullable'];
            $rules['department_id']    = ['nullable'];
        } else {
            $rules['document_type_id'] = ['required', 'uuid', 'exists:jenis_dokumen,id'];
            $rules['department_id']    = ['required', 'uuid', 'exists:departments,id'];
        }

        $validated = $request->validate($rules);

        $storedPath = $request->file('file')->store('documents', 'public');
        $isActive   = $request->has('is_active');

        // =========================================================
        // MODE: REVISE
        // =========================================================
        if (!empty($validated['revise_of'])) {
            $base = Document::with(['distributedDepartments:id'])->findOrFail($validated['revise_of']);

            $maxRevision  = (int) Document::where('document_number', $base->document_number)->max('revision');
            $nextRevision = $maxRevision + 1;

            DB::transaction(function () use ($validated, $base, $storedPath, $nextRevision, $isActive) {
                Document::where('document_number', $base->document_number)
                    ->update(['is_active' => false]);

                $doc = Document::create([
                    'jenis_dokumen_id' => $base->jenis_dokumen_id,
                    'department_id'    => $base->department_id,
                    'clinic_id'        => $base->clinic_id,
                    'sequence'         => $base->sequence,
                    'nomer_dokumen'    => $base->nomer_dokumen,     // ✅ ikut base
                    'document_number'  => $base->document_number,   // ✅ sama
                    'revision'         => $nextRevision,
                    'name'             => $validated['document_name'],
                    'publish_date'     => $validated['publish_date'],
                    'file_path'        => $storedPath,
                    'is_active'        => $isActive,
                    'read_notifikasi'  => 0,
                    'notes'            => $validated['notes'] ?? null,
                ]);

                $baseDistIds = $base->distributedDepartments()->pluck('departments.id')->all();
                $doc->distributedDepartments()->sync(!empty($baseDistIds) ? $baseDistIds : [$base->department_id]);
            });

            return redirect()->route('documents.index')
                ->with('success', "Document revised. New number: {$base->document_number} R{$nextRevision}");
        }

        // =========================================================
        // MODE: DERIVE CLINIC
        // =========================================================
        if ($from === 'derive_clinic') {
            try {
                $base   = Document::with(['distributedDepartments:id'])->findOrFail($validated['derive_of']);
                $clinic = Clinic::select('id', 'code', 'name')->findOrFail($validated['clinic_id']);

                $derivedNumber = "{$base->document_number}-{$clinic->code}";

                DB::transaction(function () use ($validated, $base, $clinic, $storedPath, $isActive, $derivedNumber) {
                    $exists = Document::where('document_number', $derivedNumber)
                        ->where('revision', 0)
                        ->exists();

                    if ($exists) {
                        throw new \RuntimeException("Nomor turunan sudah ada: {$derivedNumber}");
                    }

                    $doc = Document::create([
                        'jenis_dokumen_id' => $base->jenis_dokumen_id,
                        'department_id'    => $base->department_id,
                        'clinic_id'        => $clinic->id,
                        'sequence'         => $base->sequence,
                        'nomer_dokumen'    => $base->nomer_dokumen,   // ✅ ikut base (025)
                        'document_number'  => $derivedNumber,
                        'revision'         => 0,
                        'name'             => $validated['document_name'],
                        'publish_date'     => $validated['publish_date'],
                        'file_path'        => $storedPath,
                        'is_active'        => $isActive,
                        'read_notifikasi'  => 0,
                        'notes'            => $validated['notes'] ?? null,
                    ]);

                    $baseDistIds = $base->distributedDepartments()->pluck('departments.id')->all();
                    $doc->distributedDepartments()->sync(!empty($baseDistIds) ? $baseDistIds : [$base->department_id]);

                    $base->changedToDocuments()->syncWithoutDetaching([
                        $doc->id => ['relation_type' => 'derived_clinic'],
                    ]);
                });

                return redirect()->route('documents.index')
                    ->with('success', "Turunan klinik berhasil dibuat. Number: {$derivedNumber} R0");
            } catch (\Throwable $e) {
                if ($storedPath && Storage::disk('public')->exists($storedPath)) {
                    Storage::disk('public')->delete($storedPath);
                }

                return redirect()->route('documents.index')
                    ->with('error', $e->getMessage());
            }
        }

        // =========================================================
        // MODE: CREATE / CHANGE  ✅ pakai nomer_dokumen
        // =========================================================
        $jenis = JenisDokumen::select('id', 'kode')->findOrFail($validated['document_type_id']);
        $dept  = Department::select('id', 'code')->findOrFail($validated['department_id']);
        $year  = Carbon::parse($validated['publish_date'])->format('Y');

        $nextSeqInt     = $this->nextNomerDokumenFor($jenis->id, $dept->id, $year);
        $nomerDokumen   = $this->formatNomerDokumen($nextSeqInt); // 001/002/025/026
        $documentNumber = "{$jenis->kode}-{$dept->code}/{$nomerDokumen}/{$year}";

        $changeOfId = $validated['change_of'] ?? null;

        DB::transaction(function () use (
            $validated,
            $jenis,
            $dept,
            $nextSeqInt,
            $nomerDokumen,
            $documentNumber,
            $storedPath,
            $isActive,
            $changeOfId
        ) {
            $doc = Document::create([
                'jenis_dokumen_id' => $jenis->id,
                'department_id'    => $dept->id,
                'clinic_id'        => null,
                'sequence'         => $nextSeqInt,      // tetap simpan angka
                'nomer_dokumen'    => $nomerDokumen,    // ✅ sumber /025/
                'document_number'  => $documentNumber,  // ✅ full PKWTT-DM/025/2026
                'revision'         => 0,
                'name'             => $validated['document_name'],
                'publish_date'     => $validated['publish_date'],
                'file_path'        => $storedPath,
                'is_active'        => $isActive,
                'read_notifikasi'  => 0,
                'notes'            => $validated['notes'] ?? null,
            ]);

            // DISTRIBUSI
            if (!empty($validated['distribute_mode'])) {
                $mode = $validated['distribute_mode'];

                if ($mode === 'all') {
                    $allDeptIds = Department::where('is_active', true)->pluck('id')->all();
                    $doc->distributedDepartments()->sync($allDeptIds);
                } else {
                    $selected = collect($validated['distribution_departments'] ?? [])
                        ->unique()
                        ->values()
                        ->all();

                    $doc->distributedDepartments()->sync(!empty($selected) ? $selected : [$dept->id]);
                }
            } else {
                $doc->distributedDepartments()->sync([$dept->id]);
            }

            // MODE CHANGE: relasi + copy distribusi lama
            if ($changeOfId) {
                $old = Document::with('distributedDepartments:id')->find($changeOfId);

                if ($old) {
                    $old->changedToDocuments()->syncWithoutDetaching([
                        $doc->id => ['relation_type' => 'changed_to'],
                    ]);

                    $oldDistIds = $old->distributedDepartments->pluck('id')->all();
                    if (!empty($oldDistIds)) {
                        $doc->distributedDepartments()->sync($oldDistIds);
                    }
                }
            }
        });

        if ($changeOfId) {
            return redirect()->route('documents.index')
                ->with('success', "Document has been changed to new document. New number: {$documentNumber} R0");
        }

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
            'distribution_departments'   => ['nullable','array'],
            'distribution_departments.*' => ['uuid','exists:departments,id'],

            'notes'                      => ['nullable','string'],
        ]);

        $jenis = JenisDokumen::select('id','kode')->findOrFail($validated['document_type_id']);
        $dept  = Department::select('id','code')->findOrFail($validated['department_id']);

        $oldPath = $document->file_path;
        $newPath = $request->hasFile('file')
            ? $request->file('file')->store('documents', 'public')
            : null;

        $renumber = ($document->jenis_dokumen_id !== $jenis->id) || ($document->department_id !== $dept->id);
        $isActive = $request->has('is_active');

        DB::transaction(function () use ($validated, $document, $jenis, $dept, $newPath, $oldPath, $renumber, $isActive) {
            $payload = [
                'jenis_dokumen_id' => $jenis->id,
                'department_id'    => $dept->id,
                'name'             => $validated['document_name'],
                'publish_date'     => $validated['publish_date'],
                'is_active'        => $isActive,
                'notes'            => $validated['notes'] ?? null,
            ];

            if ($newPath) {
                $payload['file_path']       = $newPath;
                $payload['read_notifikasi'] = 0;
            }

            // ✅ Jika jenis/divisi berubah => generate nomor baru pakai nomer_dokumen
            if ($renumber) {
                $year = Carbon::parse($validated['publish_date'])->format('Y');

                $nextSeqInt   = $this->nextNomerDokumenFor($jenis->id, $dept->id, $year);
                $nomerDokumen = $this->formatNomerDokumen($nextSeqInt);

                $payload['sequence']        = $nextSeqInt;
                $payload['nomer_dokumen']   = $nomerDokumen;
                $payload['document_number'] = "{$jenis->kode}-{$dept->code}/{$nomerDokumen}/{$year}";
                $payload['revision']        = 0;
            }

            $document->update($payload);

            if ($newPath && $oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }

            if (!empty($validated['distribute_mode'])) {
                $mode = $validated['distribute_mode'];

                if ($mode === 'all') {
                    $allDeptIds = Department::where('is_active', true)->pluck('id')->all();
                    $document->distributedDepartments()->sync($allDeptIds);
                } else {
                    $selected = collect($validated['distribution_departments'] ?? [])
                        ->unique()->values()->all();

                    $document->distributedDepartments()->sync(!empty($selected) ? $selected : [$dept->id]);
                }
            }
        });

        return redirect()->route('documents.index')
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

        return redirect()->route('documents.index')
            ->with('success', 'Document deleted.');
    }

    /**
     * OPEN
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

    // ================== GATE: CEK AKSES ==================
    public function stream(Document $document)
    {
        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File not found.');
        }

        $check = $this->checkDocumentAccess($document, true);

        if (!$check['allowed'] && $check['pending'] && $check['pendingRequest']) {
            return view('documents.access-requests.pending', [
                'document'      => $document,
                'accessRequest' => $check['pendingRequest'],
            ]);
        }

        return view('documents.access-requests.open', [
            'document'         => $document,
            'remainingSeconds' => $check['remainingSeconds'],
        ]);
    }

    // ================== RAW FILE ==================
    public function rawFile(Document $document)
    {
        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File not found.');
        }

        $check = $this->checkDocumentAccess($document, false);

        if (!$check['allowed']) {
            return redirect()
                ->route('documents.index')
                ->with('error', 'Waktu akses dokumen sudah habis. Silakan ajukan permintaan akses lagi jika diperlukan.');
        }

        $asDownload   = request()->boolean('dl', false);
        $absolutePath = Storage::disk('public')->path($document->file_path);
        $setting      = WatermarkSetting::first();
        $useWatermark = $setting && $setting->enabled;

        $sendOriginal = function () use ($absolutePath, $asDownload) {
            $filename    = basename($absolutePath);
            $disposition = $asDownload ? 'attachment' : 'inline';

            return response()->file($absolutePath, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
                'X-Accel-Buffering'   => 'no',
            ]);
        };

        if (!$useWatermark) {
            return $sendOriginal();
        }

        try {
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
            $dest         = $asDownload ? 'D' : 'I';

            return new StreamedResponse(function () use ($pdf, $downloadName, $dest) {
                $pdf->Output($dest, $downloadName);
            }, 200, [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (\Throwable $e) {
            Log::warning('FPDI gagal memproses PDF, fallback ke file asli tanpa watermark.', [
                'document_id' => $document->id,
                'message'     => $e->getMessage(),
            ]);

            return $sendOriginal();
        }
    }

    // ================== HELPER: CEK AKSES DOKUMEN ==================
    protected function checkDocumentAccess(Document $document, bool $createPending = true): array
    {
        $user = Auth::user();

        if (!$user || !$user->department_id) {
            return [
                'allowed'          => true,
                'pending'          => false,
                'remainingSeconds' => null,
                'pendingRequest'   => null,
            ];
        }

        $accessSetting  = DocumentAccessSetting::first();
        $useTimedAccess = $accessSetting && $accessSetting->enabled && $accessSetting->default_duration_minutes;

        $approvedRequest = DocumentAccessRequest::where('user_id', $user->id)
            ->where('document_id', $document->id)
            ->where('status', 'approved')
            ->orderByDesc('decided_at')
            ->first();

        $hasApprovedAccess = false;
        $remainingSeconds  = null;

        if ($approvedRequest) {
            if ($useTimedAccess) {
                $startTime  = $approvedRequest->decided_at ?? $approvedRequest->created_at;
                $validUntil = $startTime
                    ? (clone $startTime)->addMinutes($accessSetting->default_duration_minutes)
                    : now()->addMinutes($accessSetting->default_duration_minutes);

                if (!$approvedRequest->expires_at || !$approvedRequest->expires_at->eq($validUntil)) {
                    $approvedRequest->expires_at = $validUntil;
                    $approvedRequest->save();
                }

                if ($validUntil->isFuture()) {
                    $hasApprovedAccess = true;
                    $remainingSeconds  = now()->diffInSeconds($validUntil, false);
                    if ($remainingSeconds < 0) $remainingSeconds = 0;
                }
            } else {
                $hasApprovedAccess = true;
            }
        }

        if (!$hasApprovedAccess) {
            $pendingRequest = null;

            if ($createPending) {
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

    /* ==========================
     | Watermark helper (tetap)
     ========================== */

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
        if (!file_exists($path)) return;

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
        if (strlen($hex) === 8) $hex = substr($hex, 0, 6);
        if (strlen($hex) !== 6) return [160,160,160];

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
                $x = $margin; $y = $margin; break;
            case 'top-right':
                $x = $w - $imgW - $margin; $y = $margin; break;
            case 'bottom-left':
                $x = $margin; $y = $h - $imgH - $margin; break;
            case 'bottom-right':
                $x = $w - $imgW - $margin; $y = $h - $imgH - $margin; break;
            case 'center':
            default:
                $x = ($w - $imgW) / 2; $y = ($h - $imgH) / 2; break;
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
