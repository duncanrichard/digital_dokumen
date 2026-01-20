@extends('layouts/contentNavbarLayout')

@section('title', 'Dashboard - Legal Analytics')

{{-- Vendor Style --}}
@section('vendor-style')
  @if(($isSuperadmin ?? false) === true)
    <link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}">
  @endif
@endsection

{{-- Vendor Script --}}
@section('vendor-script')
  @if(($isSuperadmin ?? false) === true)
    <script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
  @endif
@endsection

{{-- Page Script --}}
@section('page-script')
  @if(($isSuperadmin ?? false) === true)
    <script>
      window.legalDashboard = {
        docsPerMonthLabels: @json($docsPerMonthLabels ?? []),
        docsPerMonthSeries: @json($docsPerMonthSeries ?? []),
        documentsByDepartment: @json($documentsByDepartment ?? []),
        documentsByType: @json($documentsByType ?? []),
        accessThisMonth: @json($accessThisMonth ?? []),
      };
    </script>
    <script src="{{ asset('assets/js/dashboards-analytics.js') }}"></script>
  @endif

  {{-- non-superadmin helper --}}
  @if(($isSuperadmin ?? false) === false)
  <script>
    // auto submit saat ganti per-page
    document.addEventListener('DOMContentLoaded', function() {
      const per = document.getElementById('per_page');
      if (per) {
        per.addEventListener('change', function() {
          const form = document.getElementById('filterForm');
          if (form) form.submit();
        });
      }
    });
  </script>
  @endif
@endsection

@push('styles')
<style>
  /* ====== Header mirip Document Library ====== */
  .page-header-card {
    background: #fff;
    border: 1px solid rgba(0,0,0,.06);
    border-radius: 14px;
  }
  .page-subtitle { color:#6c757d; font-size:.9rem; }

  /* ====== Ebook Library UI ====== */
  .ebook-cover{
    width:100%;
    aspect-ratio:3/4;
    border-radius:14px;
    background:
      radial-gradient(circle at 30% 20%, rgba(255,255,255,.85), rgba(255,255,255,0) 40%),
      linear-gradient(135deg, rgba(13,110,253,.18), rgba(0,0,0,.04));
    position:relative;
    overflow:hidden;
    border:1px solid rgba(0,0,0,.06);
  }
  .ebook-cover .icon{
    position:absolute;
    bottom:14px;
    right:14px;
    width:44px;
    height:44px;
    border-radius:12px;
    display:grid;
    place-items:center;
    background:rgba(255,255,255,.85);
    border:1px solid rgba(0,0,0,.06);
  }
  .ebook-badges{
    position:absolute;
    top:12px;
    left:12px;
    display:flex;
    gap:8px;
    flex-wrap:wrap;
  }
  .ebook-badges .badge{
    border-radius:999px;
    padding:.35rem .6rem;
    font-weight:600;
    font-size:.75rem;
  }

  /* ✅ title dipaksa bold */
  .ebook-title{
    font-weight:800 !important;
    font-size:1.02rem;
    color:#1f2d3d;
    line-height:1.25;
    display:-webkit-box;
    -webkit-line-clamp:2;
    -webkit-box-orient:vertical;
    overflow:hidden;
    min-height:2.6em;
    letter-spacing:.1px;
  }

  .ebook-meta{
    font-size:.85rem;
    color:#6c757d;
    display:grid;
    gap:.25rem;
  }
  .ebook-meta .mono{
    font-family:"SFMono-Regular", Menlo, Monaco, Consolas, "Liberation Mono","Courier New", monospace;
    font-size:.82rem;
    color:#495057;
  }

  .ebook-card{
    border:1px solid rgba(0,0,0,.06);
    border-radius:16px;
    transition:transform .12s ease, box-shadow .12s ease;
    overflow:hidden;
  }
  .ebook-card:hover{
    transform:translateY(-2px);
    box-shadow:0 .5rem 1.25rem rgba(0,0,0,.08);
  }

  /* ====== Pagination mirip DataTables ====== */
  .dt-footer{
    border-top:1px solid rgba(0,0,0,.06);
    background:#fff;
    border-radius:14px;
  }
  .dt-info{
    color:#6c757d;
    font-size:.875rem;
  }
  .pagination.pagination-sm .page-link{
    padding:.35rem .6rem;
  }
</style>
@endpush

@section('content')

@if(($isSuperadmin ?? false) === false)

@php
  $perPage = (int) request('per_page', 12);
  if (!in_array($perPage, [12,24,48,96])) $perPage = 12;
@endphp

<div class="row gy-4">
  <div class="col-12">
    <div class="card page-header-card shadow-sm">
      <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 py-3">
        <div>
          <h4 class="card-title mb-1">📚 Document Library</h4>
          <!-- <p class="page-subtitle mb-0">Browse controlled documents & assets available for your division</p> -->
        </div>
        <div class="d-flex gap-2">
          <!-- <a href="{{ route('documents.index') }}" class="btn btn-primary">
            <i class="mdi mdi-folder-outline me-1"></i> Open Library
          </a> -->
        </div>
      </div>

      <div class="card-header bg-white border-top">
        <form method="get" class="py-2" id="filterForm">
          <div class="row g-3 align-items-end">
            <div class="col-12 col-md-3">
              <label class="form-label small text-muted mb-1">Document Type</label>
              <select name="document_type_id" class="form-select">
                <option value="">All types</option>
                @foreach(($documentTypes ?? []) as $dt)
                  <option value="{{ $dt->id }}" {{ (string)($filterJenisId ?? '') === (string)$dt->id ? 'selected' : '' }}>
                    {{ $dt->kode }} - {{ $dt->nama }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="col-12 col-md-5">
              <label class="form-label small text-muted mb-1">Search</label>
              <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control" placeholder="Search by name or number...">
            </div>

            <div class="col-12 col-md-2">
              <label class="form-label small text-muted mb-1">Show</label>
              <select name="per_page" id="per_page" class="form-select">
                <option value="12" {{ $perPage===12 ? 'selected' : '' }}>12</option>
                <option value="24" {{ $perPage===24 ? 'selected' : '' }}>24</option>
                <option value="48" {{ $perPage===48 ? 'selected' : '' }}>48</option>
                <option value="96" {{ $perPage===96 ? 'selected' : '' }}>96</option>
              </select>
            </div>

            <div class="col-12 col-md-2 d-flex gap-2">
              <button class="btn btn-primary flex-fill" type="submit">
                <i class="mdi mdi-magnify me-1"></i> Search
              </button>

              @if(($filterJenisId ?? null) || (($q ?? '') !== '') || request('per_page'))
                <a class="btn btn-outline-secondary" href="{{ url('/') }}" title="Clear filters">
                  <i class="mdi mdi-close"></i>
                </a>
              @endif
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12">
    @if(($docs ?? null) && $docs->count())
      <div class="row g-3">
        @foreach($docs as $doc)
          <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
            <div class="card ebook-card h-100">
              <div class="p-3">
                <div class="ebook-cover">
                  <div class="ebook-badges">
                    @if($doc->is_active)
                      <span class="badge bg-success">Active</span>
                    @else
                      <span class="badge bg-secondary">Inactive</span>
                    @endif

                    @if(!$doc->read_notifikasi)
                      <span class="badge bg-danger">
                        <i class="mdi mdi-bell-ring-outline me-1"></i>New
                      </span>
                    @endif

                    <span class="badge bg-primary">R{{ $doc->revision ?? 0 }}</span>
                  </div>

                  <div class="icon">
                    <i class="mdi mdi-file-pdf-box mdi-24px text-danger"></i>
                  </div>
                </div>

                <div class="mt-3">
                  <div class="ebook-title">
                    <strong class="fw-bold">{{ $doc->name }}</strong>
                  </div>

                  <div class="ebook-meta mt-2">
                    <div class="mono">{{ $doc->document_number }}</div>

                    <div>
                      <i class="mdi mdi-tag-outline me-1"></i>
                      {{ $doc->jenisDokumen->kode ?? '—' }} - {{ $doc->jenisDokumen->nama ?? '—' }}
                    </div>

                    <div>
                      <i class="mdi mdi-office-building-outline me-1"></i>
                      {{ $doc->department->code ?? '—' }} - {{ $doc->department->name ?? '—' }}
                    </div>

                    <div>
                      <i class="mdi mdi-calendar-month-outline me-1"></i>
                      {{ optional(\Carbon\Carbon::parse($doc->publish_date))->format('d M Y') }}
                    </div>

                    @if($doc->clinic)
                      <div>
                        <i class="mdi mdi-hospital-building me-1"></i>
                        Clinic: {{ $doc->clinic->code ?? '' }} - {{ $doc->clinic->name ?? '' }}
                      </div>
                    @endif
                  </div>
                </div>
              </div>

              <div class="card-footer bg-white border-0 pt-0">
                <div class="d-grid gap-2">
                  <a href="{{ route('documents.file', $doc->id) }}" class="btn btn-outline-primary">
                    <i class="mdi mdi-eye-outline me-1"></i> Open
                  </a>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>

      {{-- ✅ Footer pagination ala DataTables --}}
      <div class="card dt-footer mt-4">
        <div class="card-body py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
          <div class="dt-info">
            Showing {{ $docs->firstItem() }} to {{ $docs->lastItem() }} of {{ $docs->total() }} documents
          </div>

          <div>
            {{-- pagination numerik + prev/next --}}
            {{ $docs->onEachSide(1)->links('pagination::bootstrap-5') }}
          </div>
        </div>
      </div>
    @else
      <div class="card">
        <div class="card-body text-center py-5">
          <i class="mdi mdi-book-open-page-variant-outline" style="font-size:48px;color:#dee2e6"></i>
          <h5 class="mt-3 mb-1 text-muted">No documents available</h5>
          <p class="text-muted mb-0">Try changing filters or searching with different keywords.</p>
        </div>
      </div>
    @endif
  </div>
</div>

@else
  {{-- SUPERADMIN tetap --}}
  <div class="row gy-4">
    <div class="col-md-12 col-lg-4">
      <div class="card">
        <div class="card-body">
          <h4 class="card-title mb-1">Legal Document Dashboard 📚</h4>
          <p class="pb-0">Ringkasan aktivitas dokumen legal</p>
          <h4 class="text-primary mb-1">
            {{ number_format($summaryCards['total_documents'] ?? 0) }} Dokumen
          </h4>
          <p class="mb-2 pb-1">
            Aktif:
            <span class="text-success fw-medium">
              {{ number_format($summaryCards['active_documents'] ?? 0) }}
            </span> |
            Nonaktif:
            <span class="text-muted fw-medium">
              {{ number_format($summaryCards['inactive_documents'] ?? 0) }}
            </span>
          </p>
          <a href="{{ route('documents.index') }}" class="btn btn-sm btn-primary">
            Buka Library Dokumen
          </a>
        </div>
        <img src="{{ asset('assets/img/icons/misc/triangle-light.png') }}" class="scaleX-n1-rtl position-absolute bottom-0 end-0" width="166" alt="triangle background">
        <img src="{{ asset('assets/img/illustrations/trophy.png') }}" class="scaleX-n1-rtl position-absolute bottom-0 end-0 me-4 mb-4 pb-2" width="83" alt="legal trophy">
      </div>
    </div>
    {{-- tempel sisa analytics superadmin --}}
  </div>
@endif

@endsection
