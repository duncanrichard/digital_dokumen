<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\JenisDokumen;
use Illuminate\Http\Request;

class JenisDokumenController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));

        $items = JenisDokumen::when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    // PostgreSQL case-insensitive search
                    $sub->where('kode', 'ilike', "%{$q}%")
                        ->orWhere('nama', 'ilike', "%{$q}%");
                });
            })
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return view('master.jenis-dokumen.index', compact('items', 'q'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
<<<<<<< HEAD
            // DIGANTI: alpha_dash → regex yang mengizinkan huruf, angka, -, _, dan .
            'kode'      => [
                'required',
                'max:20',
                'regex:/^[A-Za-z0-9._-]+$/',
                'unique:jenis_dokumen,kode',
            ],
            'nama'      => ['required', 'max:100'],
            'deskripsi' => ['nullable', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ], [
            'kode.regex' => 'Kode hanya boleh berisi huruf, angka, titik (.), strip (-), dan garis bawah (_).',
=======
            'kode'      => ['required', 'max:20', 'alpha_dash', 'unique:jenis_dokumen,kode'],
            'nama'      => ['required', 'max:100'],
            'deskripsi' => ['nullable', 'max:1000'],
            'is_active' => ['required', 'boolean'],
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
        ]);

        JenisDokumen::create($validated);

        return redirect()
            ->route('master.jenis-dokumen.index')
            ->with('success', 'Document type has been created.');
    }

    public function update(Request $request, JenisDokumen $jenisDokumen)
    {
        $validated = $request->validate([
<<<<<<< HEAD
            'kode'      => [
                'required',
                'max:20',
                'regex:/^[A-Za-z0-9._-]+$/',
                'unique:jenis_dokumen,kode,' . $jenisDokumen->id,
            ],
            'nama'      => ['required', 'max:100'],
            'deskripsi' => ['nullable', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ], [
            'kode.regex' => 'Kode hanya boleh berisi huruf, angka, titik (.), strip (-), dan garis bawah (_).',
=======
            'kode'      => ['required', 'max:20', 'alpha_dash', 'unique:jenis_dokumen,kode,' . $jenisDokumen->id],
            'nama'      => ['required', 'max:100'],
            'deskripsi' => ['nullable', 'max:1000'],
            'is_active' => ['required', 'boolean'],
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
        ]);

        $jenisDokumen->update($validated);

        return redirect()
            ->route('master.jenis-dokumen.index')
            ->with('success', 'Document type has been updated.');
    }

    public function destroy(JenisDokumen $jenisDokumen)
    {
        $jenisDokumen->delete();

        return redirect()
            ->route('master.jenis-dokumen.index')
            ->with('success', 'Document type has been deleted.');
    }
}
