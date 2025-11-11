<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JenisDokumen;
use App\Models\Department;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DocumentUploadController extends Controller
{
    // ================== LIST / FILTER ==================
    public function index(Request $request)
    {
        $q             = trim((string) $request->get('q'));
        $filterJenisId = $request->get('document_type_id'); // uuid
        $filterDeptId  = $request->get('department_id');    // uuid

        $documentTypes = JenisDokumen::where('is_active', true)
            ->orderBy('nama')
            ->get(['id','kode','nama']);

        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get(['id','code','name']);

        $items = Document::with(['jenisDokumen:id,kode,nama', 'department:id,code,name', 'distributedDepartments:id'])
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
            ->when($filterDeptId,  fn($q3) => $q3->where('department_id',    $filterDeptId))
            ->orderByDesc('publish_date')
            ->paginate(10)
            ->withQueryString();

        return view('documents.upload.index', compact(
            'items','q','documentTypes','departments','filterJenisId','filterDeptId'
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

    // ================== STREAM PDF (same-origin) ==================
    public function stream(Document $document)
    {
        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'PDF file not found.');
        }

        $absolute = storage_path('app/public/'.$document->file_path);

        // Sajikan sebagai inline agar tampil di iframe
        return response()->file($absolute, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.basename($absolute).'"',
            // Opsi cache (boleh dihapus bila tidak perlu)
            'Cache-Control'       => 'private, max-age=3600',
        ]);
    }

    // ================== PREVIEW PDF (pakai PDF.js bila ada) ==================
    public function preview(Document $document)
    {
        // tandai notif sudah dibaca saat membuka preview
        if (Schema::hasColumn('documents', 'read_notifikasi') && (int)$document->read_notifikasi === 0) {
            $document->forceFill(['read_notifikasi' => 1])->saveQuietly();
        }

        if (!$document->file_path || !Storage::disk('public')->exists($document->file_path)) {
            return back()->with('error', 'PDF file not found or missing storage link.');
        }

        // URL stream same-origin
        $fileUrl = route('documents.file', $document->id);

        // Jika pdf.js terpasang di public/vendor/pdfjs
        $pdfjsViewerUrl = null;
        if (file_exists(public_path('vendor/pdfjs/web/viewer.html'))) {
            $pdfjsViewerUrl = asset('vendor/pdfjs/web/viewer.html') . '?file=' . urlencode($fileUrl);
        }

        return view('documents.upload.preview', [
            'document'       => $document,
            'fileUrl'        => $fileUrl,       // <— gunakan ini untuk native viewer
            'pdfjsViewerUrl' => $pdfjsViewerUrl // <— jika ada, pakai di iframe
        ]);
    }

    // ================== NOTIFICATIONS ==================
    public function markAllNotifications()
    {
        Document::where('read_notifikasi', 0)->update(['read_notifikasi' => 1]);
        return back()->with('success', 'All notifications marked as read.');
    }

    public function markNotificationRead(Document $document)
    {
        if ((int)$document->read_notifikasi === 0) {
            $document->forceFill(['read_notifikasi' => 1])->saveQuietly();
        }
        return response()->json(['ok' => true]);
    }
    
}
