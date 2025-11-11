@extends('layouts/contentNavbarLayout')

@section('title','Document Control - Distribution')

{{-- ========== Select2 Styles (Bootstrap 5 theme) ========== --}}
@section('vendor-style')
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endsection

@push('styles')
<style>
  /* Biar Select2 lebar penuh dan rapi di dalam card */
  .select2-container { width: 100% !important; }
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="py-3 mb-4">
    <span class="text-muted fw-light">Document Control /</span> Distribution
  </h4>

  {{-- Flash --}}
  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      <i class="mdi mdi-check-circle-outline me-1"></i>{{ session('success') }}
      <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif
  @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
      <i class="mdi mdi-alert-circle-outline me-1"></i> Periksa kembali form Anda.
      <ul class="mb-0 mt-2 ps-3">
        @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
      </ul>
      <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
      <span>Pengaturan Distribusi Dokumen</span>

      <form method="GET" action="{{ route('documents.distribution.index') }}" class="d-flex gap-2">
        <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm"
               placeholder="Cari no / nama dokumen...">
        <button class="btn btn-outline-secondary btn-sm" title="Cari">
          <i class="mdi mdi-magnify"></i>
        </button>
        @if($q !== '')
          <a href="{{ route('documents.distribution.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset">
            <i class="mdi mdi-refresh"></i>
          </a>
        @endif
      </form>
    </div>

    <div class="card-body">
      {{-- Pilih Dokumen --}}
      <form method="GET" action="{{ route('documents.distribution.index') }}" class="mb-4" id="formPickDoc">
        <label class="form-label">Pilih Dokumen</label>
        <div class="d-flex gap-2 w-100">
          <select class="form-select select2" name="document_id" id="selectDocument"
                  data-placeholder="Pilih dokumen..."
                  style="min-width: 280px">
            <option value=""></option>
            @foreach($documents as $d)
              <option value="{{ $d->id }}" @selected($documentId===$d->id)>
                {{ $d->document_number ?? '-' }} — {{ $d->name }}
                @if(!is_null($d->revision)) (Rev {{ $d->revision }}) @endif
              </option>
            @endforeach
          </select>

          @if($documentId)
            <a href="{{ route('documents.distribution.index') }}" class="btn btn-outline-secondary">Ganti</a>
          @endif
        </div>
      </form>

      @if(!$selectedDoc)
        <div class="text-muted">Silakan pilih dokumen terlebih dahulu.</div>
      @else
        {{-- Info Dokumen --}}
        <div class="alert alert-info py-2">
          <div><strong>Dokumen:</strong> {{ $selectedDoc->document_number }} — {{ $selectedDoc->name }}</div>
          <div class="small mb-0">
            Publish: {{ optional($selectedDoc->publish_date)->format('Y-m-d') ?? '-' }},
            Status: {!! $selectedDoc->is_active ? '<span class="badge bg-label-success">Aktif</span>' : '<span class="badge bg-label-danger">Nonaktif</span>' !!}
            @if(!is_null($selectedDoc->revision)) , Rev {{ $selectedDoc->revision }} @endif
          </div>
        </div>

        {{-- Form Distribusi --}}
        <form method="POST" action="{{ route('documents.distribution.store') }}">
          @csrf
          <input type="hidden" name="document_id" value="{{ $selectedDoc->id }}">

          <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
            <label class="form-label mb-0">Pilih Departemen Penerima</label>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="checkAllDeps">
              <label class="form-check-label" for="checkAllDeps">Pilih semua departemen</label>
            </div>
          </div>

          <div class="row" id="depsGrid">
            @forelse($departments as $dep)
              <div class="col-md-4 col-lg-3 mb-2">
                <div class="form-check">
                  <input class="form-check-input dep-checkbox" type="checkbox"
                         name="department_id[]"
                         id="dep_{{ $dep->id }}"
                         value="{{ $dep->id }}"
                         @checked(in_array($dep->id, $selectedDepartments))>
                  <label class="form-check-label" for="dep_{{ $dep->id }}">{{ $dep->name }}</label>
                </div>
              </div>
            @empty
              <div class="col-12 text-muted">Belum ada data departemen.</div>
            @endforelse
          </div>

          <div class="mt-4 d-flex gap-2">
            <button class="btn btn-primary">
              <i class="mdi mdi-content-save-outline me-1"></i> Simpan Distribusi
            </button>
            <a href="{{ route('documents.distribution.index', ['document_id'=>$selectedDoc->id]) }}"
               class="btn btn-outline-secondary">Reset</a>
          </div>
        </form>
      @endif
    </div>
  </div>
</div>
@endsection

{{-- ========== Select2 Scripts ========== --}}
@section('vendor-script')
  {{-- Jika layout sudah memuat jQuery, Anda boleh hapus baris ini --}}
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>

  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endsection

@section('page-script')
<script>
  (function() {
    // Inisialisasi Select2 untuk dropdown dokumen
    function initSelect2() {
      const $el = $('#selectDocument');
      if ($el.length) {
        if ($el.hasClass('select2-hidden-accessible')) {
          $el.select2('destroy');
        }
        $el.select2({
          theme: 'bootstrap-5',
          width: '100%',
          placeholder: $el.data('placeholder') || 'Pilih dokumen...',
          allowClear: true,
          // Supaya dropdown muncul di atas card dan tidak terpotong,
          // gunakan container card-body sebagai parent.
          dropdownParent: $el.closest('.card')
        });

        // Auto submit saat user memilih dokumen
        $el.on('change', function () {
          document.getElementById('formPickDoc').submit();
        });
      }
    }

    // ====== Check All Departemen ======
    function initCheckAll() {
      const master = document.getElementById('checkAllDeps');
      const depBoxes = Array.from(document.querySelectorAll('.dep-checkbox'));

      if (!master || depBoxes.length === 0) return;

      // Set status awal master checkbox
      function refreshMasterState() {
        const total = depBoxes.length;
        const checked = depBoxes.filter(cb => cb.checked).length;

        if (checked === 0) {
          master.checked = false;
          master.indeterminate = false;
        } else if (checked === total) {
          master.checked = true;
          master.indeterminate = false;
        } else {
          master.checked = false;
          master.indeterminate = true;
        }
      }

      // Saat master diubah -> set semua
      master.addEventListener('change', function() {
        const targetChecked = master.checked;
        depBoxes.forEach(cb => { cb.checked = targetChecked; });
        // setelah set semua, pastikan indeterminate false
        master.indeterminate = false;
      });

      // Saat checkbox per departemen diubah -> sync master
      depBoxes.forEach(cb => {
        cb.addEventListener('change', refreshMasterState);
      });

      // Inisialisasi awal
      refreshMasterState();
    }

    // Ready
    document.addEventListener('DOMContentLoaded', function() {
      initSelect2();
      initCheckAll();
    });
  })();
</script>
@endsection
