@extends('layouts/contentNavbarLayout')

@section('title', 'Document Control - Document Gallery')

@section('vendor-style')
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endsection

@push('styles')
<style>
  .gallery-hero{
    background: linear-gradient(135deg, rgba(13,110,253,.08), rgba(255,193,7,.08));
    border: 1px solid rgba(0,0,0,.06);
  }

  .doc-card{
    border: 1px solid rgba(0,0,0,.08);
    transition: transform .12s ease, box-shadow .12s ease;
    overflow: hidden;
    border-radius: 14px;
  }
  .doc-card:hover{
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0,0,0,.08);
  }

  .doc-cover{
    height: 160px;
    background:
      radial-gradient(circle at 30% 20%, rgba(13,110,253,.18), transparent 45%),
      radial-gradient(circle at 70% 80%, rgba(255,193,7,.18), transparent 45%),
      #fff;
    display:flex;
    align-items:center;
    justify-content:center;
    position: relative;
  }
  .doc-cover i{ font-size: 56px; color: rgba(13,110,253,.55); }

  .cover-badges{
    position:absolute;
    top:10px;
    left:10px;
    display:flex;
    gap:.35rem;
    flex-wrap:wrap;
  }
  .cover-badges .badge{
    border-radius:999px;
    font-weight:700;
    padding:.35rem .6rem;
    box-shadow: 0 6px 16px rgba(0,0,0,.08);
  }

  .doc-title{ font-weight: 700; color:#1f2d3d; }
  .doc-number{
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-size:.85rem;
  }
  .badge-pill{ border-radius: 999px; }
  .meta{ color:#6c757d; font-size:.85rem; }

  .is-distributed{
    border-color: rgba(255,193,7,.35) !important;
  }
  .is-distributed .doc-cover{
    background:
      radial-gradient(circle at 30% 20%, rgba(255,193,7,.22), transparent 45%),
      radial-gradient(circle at 70% 80%, rgba(13,110,253,.12), transparent 45%),
      #fff;
  }
</style>
@endpush

@section('content')
  @include('documents._legal_workflow')
  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="mdi mdi-check-circle-outline me-2"></i>
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="mdi mdi-alert-circle-outline me-2"></i>
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  <div class="card gallery-hero mb-4">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
      <div>
        <h4 class="mb-1">📚 Document Gallery</h4>
        <div class="text-muted small">Browse documents like an eBook library</div>
      </div>

      <form method="get" class="d-flex flex-wrap gap-2 align-items-center">
        <div style="min-width: 220px;">
          <select name="document_type_id" class="form-select select2" data-placeholder="All types">
            <option value=""></option>
            @foreach($documentTypes as $dt)
              <option value="{{ $dt->id }}" {{ ($filterJenisId ?? '') == $dt->id ? 'selected' : '' }}>
                {{ $dt->kode }} - {{ $dt->nama }}
              </option>
            @endforeach
          </select>
        </div>

        <div style="min-width: 220px;">
          <select name="department_id" class="form-select select2" data-placeholder="All divisions">
            <option value=""></option>
            @foreach($departments as $dep)
              <option value="{{ $dep->id }}" {{ ($filterDeptId ?? '') == $dep->id ? 'selected' : '' }}>
                {{ $dep->code }} - {{ $dep->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div style="min-width: 260px;">
          <input type="text" class="form-control" name="q" value="{{ $q ?? '' }}" placeholder="Search name or number...">
        </div>

        <button class="btn btn-primary" type="submit">
          <i class="mdi mdi-magnify me-1"></i> Search
        </button>

        @if(($filterJenisId ?? null) || ($filterDeptId ?? null) || ($q ?? ''))
          <a href="{{ route('documents.gallery.index') }}" class="btn btn-outline-secondary" title="Clear filters">
            <i class="mdi mdi-close"></i>
          </a>
        @endif
      </form>
    </div>
  </div>

  <div class="card mb-4 border-primary-subtle">
    <div class="card-body">
      <div class="d-flex align-items-center gap-2 mb-2">
        <i class="mdi mdi-creation text-primary fs-4"></i>
        <h5 class="mb-0">Pencarian AI</h5>
      </div>
      <p class="text-muted small mb-3">Cari berdasarkan maksud, judul, deskripsi, atau nama orang.</p>
      <form id="ai-search-form" class="d-flex gap-2">
        <input id="ai-search-input" class="form-control" placeholder="Contoh: SOP kenaikan jabatan Duncan" maxlength="200" required>
        <button class="btn btn-primary text-nowrap" type="submit"><i class="mdi mdi-magnify me-1"></i>Cari dengan AI</button>
      </form>
      <div id="ai-search-status" class="small text-muted mt-3"></div>
      <div id="ai-search-results" class="list-group mt-2"></div>
    </div>
  </div>

  @if($items->count() === 0)
    <div class="card">
      <div class="card-body text-center py-5">
        <i class="mdi mdi-library-shelves" style="font-size:64px;color:#dee2e6;"></i>
        <h5 class="text-muted mt-2 mb-1">No documents found</h5>
        <div class="text-muted small">Try changing filters or keywords.</div>
      </div>
    </div>
  @else
    <div class="row g-4">
      @foreach($items as $doc)
        @php
          // =========================
          // ✅ NORMALISASI & STATE
          // =========================
          $reqStatusRaw = $doc->access_request_status ?? null;
          $reqStatus = $reqStatusRaw ? strtoupper(trim((string)$reqStatusRaw)) : null;
          if ($reqStatus === 'REJECT') $reqStatus = 'REJECTED';

          $isDistributed = (bool)($doc->is_distributed_doc ?? false);

          // approved valid ditentukan controller -> tapi UI harus aman juga
          $isApproved = ($reqStatus === 'APPROVED');

          // FINAL LOCK STATE:
          // - distributed / approved => unlocked
          // - selain itu => lihat flag controller
          $isLocked = (!$isDistributed && !$isApproved) ? (bool)($doc->is_locked_for_me ?? false) : false;

          // action href
          $btnHref = route('documents.gallery.read', $doc->id);

          // Tombol request ulang:
          // - kalau rejected => selalu boleh request ulang
          // - kalau controller mengirim can_request_again => ikut
          $canRequestAgain = (bool)($doc->can_request_again ?? false) || ($reqStatus === 'REJECTED');
        @endphp

        <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
          <div class="card doc-card h-100 {{ $isDistributed ? 'is-distributed' : '' }}">
            <div class="doc-cover">
              <div class="cover-badges">

                {{-- Lock/Unlock --}}
                @if($isLocked)
                  <span class="badge bg-danger">
                    <i class="mdi mdi-lock-outline me-1"></i> Locked
                  </span>
                @else
                  <span class="badge bg-success">
                    <i class="mdi mdi-lock-open-variant-outline me-1"></i> Unlocked
                  </span>
                @endif

                {{-- Distributed --}}
                @if($isDistributed)
                  <span class="badge bg-warning text-dark">
                    <i class="mdi mdi-share-variant-outline me-1"></i> Distributed
                  </span>
                @endif

                {{-- Request Status badge (hanya saat locked) --}}
                @if($isLocked && $reqStatus === 'PENDING')
                  <span class="badge bg-info text-dark">
                    <i class="mdi mdi-timer-sand me-1"></i> Pending
                  </span>
                @elseif($isLocked && $reqStatus === 'REJECTED')
                  <span class="badge bg-dark">
                    <i class="mdi mdi-close-octagon-outline me-1"></i> Rejected
                  </span>
                @endif
              </div>

              <i class="mdi mdi-file-pdf-box"></i>
            </div>

            <div class="card-body">
              <div class="doc-title text-truncate" title="{{ $doc->name }}">
                {{ $doc->name }}
              </div>

              <div class="doc-number text-muted mt-1 text-truncate" title="{{ $doc->document_number }}">
                {{ $doc->document_number }}
                <span class="text-muted ms-1">R{{ $doc->revision ?? 0 }}</span>
              </div>

              <div class="d-flex flex-wrap gap-1 mt-2">
                <span class="badge bg-light text-dark border badge-pill">
                  <i class="mdi mdi-tag-outline me-1"></i>{{ $doc->jenisDokumen->kode ?? '—' }}
                </span>

                <span class="badge bg-light text-dark border badge-pill">
                  <i class="mdi mdi-office-building-outline me-1"></i>{{ $doc->department->code ?? '—' }}
                </span>

                @if($doc->is_active)
                  <span class="badge bg-success badge-pill">
                    <i class="mdi mdi-check-circle-outline me-1"></i>Active
                  </span>
                @else
                  <span class="badge bg-danger badge-pill">
                    <i class="mdi mdi-close-circle-outline me-1"></i>Inactive
                  </span>
                @endif
              </div>

              {{-- Distributed info --}}
              @if(!empty($doc->distributedDepartments) && $doc->distributedDepartments->count() > 0)
                <div class="meta mt-2 text-truncate"
                     title="Distributed to: {{ $doc->distributedDepartments->pluck('code')->join(', ') }}">
                  <i class="mdi mdi-share-variant-outline me-1"></i>
                  Distributed to: {{ $doc->distributedDepartments->pluck('code')->take(3)->join(', ') }}
                  @if($doc->distributedDepartments->count() > 3)
                    +{{ $doc->distributedDepartments->count() - 3 }}
                  @endif
                </div>
              @endif

              <div class="meta mt-2">
                <i class="mdi mdi-calendar-month-outline me-1"></i>
                {{ optional(\Carbon\Carbon::parse($doc->publish_date))->format('d M Y') }}
              </div>

              {{-- =========================
                   ✅ BUTTON LOGIC
                   ========================= --}}
              <div class="d-grid gap-2 mt-3">

                {{-- UNLOCKED => Open --}}
                @if(!$isLocked)
                  <a class="btn btn-primary" href="{{ $btnHref }}">
                    <i class="mdi mdi-eye-outline me-1"></i> Open
                  </a>

                {{-- LOCKED + PENDING => disable --}}
                @elseif($reqStatus === 'PENDING')
                  <button type="button" class="btn btn-outline-info" disabled>
                    <i class="mdi mdi-timer-sand me-1"></i> Waiting Approval
                  </button>

                {{-- LOCKED + REJECTED => Request Again --}}
                @elseif($canRequestAgain)
                  <a class="btn btn-outline-dark" href="{{ $btnHref }}">
                    <i class="mdi mdi-close-octagon-outline me-1"></i> Rejected (Request Again)
                  </a>

                {{-- LOCKED belum pernah request => Request/Open --}}
                @else
                  <a class="btn btn-outline-danger" href="{{ $btnHref }}">
                    <i class="mdi mdi-lock-outline me-1"></i> Request / Open
                  </a>
                @endif

              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>

    <div class="mt-4">
      {{ $items->links() }}
    </div>
  @endif
@endsection

<script>
document.getElementById('ai-search-form')?.addEventListener('submit', async function (event) {
  event.preventDefault();
  const input = document.getElementById('ai-search-input');
  const status = document.getElementById('ai-search-status');
  const results = document.getElementById('ai-search-results');
  status.textContent = 'Mencari dokumen yang paling relevan...'; results.innerHTML = '';
  try {
    const response = await fetch('{{ route('documents.gallery.ai-search') }}?q=' + encodeURIComponent(input.value), {headers: {'X-Requested-With':'XMLHttpRequest'}});
    const payload = await response.json();
    if (!response.ok) throw new Error();
    if (!payload.data.length) { status.textContent = 'Dokumen yang sesuai belum ditemukan.'; return; }
    status.textContent = payload.data.length + ' dokumen ditemukan';
    payload.data.forEach(doc => {
      const link = document.createElement('a'); link.className = 'list-group-item list-group-item-action';
      link.href = '{{ url('/documents/gallery') }}/' + doc.id + '/read';
      link.innerHTML = '<div class="fw-semibold">' + (doc.name || doc.title || '-') + ' <span class="badge bg-secondary">R' + (doc.revision ?? 0) + '</span></div><small class="text-muted">' + (doc.document_number || doc.number || '') + ' · ' + (doc.department?.name || '') + '</small>' + (doc.notes ? '<div class="small mt-1 text-muted">' + doc.notes + '</div>' : '');
      results.appendChild(link);
    });
  } catch (e) { status.textContent = 'Pencarian AI gagal. Coba lagi atau gunakan pencarian biasa.'; }
});
</script>

@section('vendor-script')
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endsection

@section('page-script')
<script>
  document.getElementById('ai-search-form')?.addEventListener('submit', async function (event) {
    event.preventDefault();
    const input = document.getElementById('ai-search-input'), status = document.getElementById('ai-search-status'), results = document.getElementById('ai-search-results');
    status.textContent = 'Mencari dokumen yang paling relevan...'; results.innerHTML = '';
    try {
      const response = await fetch('{{ route('documents.gallery.ai-search') }}?q=' + encodeURIComponent(input.value), {headers: {'X-Requested-With':'XMLHttpRequest'}}), payload = await response.json();
      if (!response.ok) throw new Error();
      status.textContent = payload.data.length ? payload.data.length + ' dokumen ditemukan' : 'Dokumen yang sesuai belum ditemukan.';
      payload.data.forEach(doc => { const link = document.createElement('a'); link.className = 'list-group-item list-group-item-action'; link.href = '{{ url('/documents/gallery') }}/' + doc.id + '/read'; link.innerHTML = '<div class="fw-semibold">' + (doc.name || doc.title || '-') + ' <span class="badge bg-secondary">R' + (doc.revision ?? 0) + '</span></div><small class="text-muted">' + (doc.document_number || doc.number || '') + ' · ' + (doc.department?.name || '') + '</small>' + (doc.notes ? '<div class="small mt-1 text-muted">' + doc.notes + '</div>' : ''); results.appendChild(link); });
    } catch (e) { status.textContent = 'Pencarian AI gagal. Coba lagi atau gunakan pencarian biasa.'; }
  });
  (function() {
    function initSelect2() {
      $('.select2').each(function () {
        const $el = $(this);
        if ($el.hasClass('select2-hidden-accessible')) {
          $el.select2('destroy');
        }
        $el.select2({
          theme: 'bootstrap-5',
          width: '100%',
          placeholder: $el.data('placeholder') || '',
          allowClear: true,
          dropdownParent: $el.closest('.card')
        });
      });
    }
    $(document).ready(function () { initSelect2(); });
  })();
</script>
@endsection
