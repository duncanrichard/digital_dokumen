@extends('layouts/contentNavbarLayout')

@section('title','Document Control - Distribution')

{{-- ========== Select2 Styles (Bootstrap 5 theme) ========== --}}
@section('vendor-style')
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endsection

@push('styles')
<style>
  .select2-container {
    width: 100% !important;
  }

  .document-card {
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  }

  .document-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
    transform: translateY(-2px);
  }

  .header-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 8px;
    padding: 2rem;
    margin-bottom: 2rem;
  }

  .search-box {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  }

  .dept-card {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    transition: all 0.3s ease;
    background: white;
    height: 100%;
  }

  .dept-card:hover {
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
    transform: translateY(-2px);
  }

  .dept-card.selected {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
  }

  .dept-card .form-check-input:checked {
    background-color: #667eea;
    border-color: #667eea;
  }

  .info-card {
    background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
    border: none;
    border-left: 4px solid #667eea;
    border-radius: 8px;
  }

  .stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
  }

  .stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.12);
  }

  .stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    margin-bottom: 0.75rem;
  }

  .action-btn {
    padding: 0.75rem 2rem;
    border-radius: 8px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
  }

  .action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }

  .section-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #667eea;
    display: inline-block;
  }

  .empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: #6c757d;
  }

  .empty-state-icon {
    font-size: 4rem;
    color: #dee2e6;
    margin-bottom: 1rem;
  }

  @media (max-width: 768px) {
    .header-gradient {
      padding: 1.5rem;
    }

    .stat-card {
      margin-bottom: 1rem;
    }
  }
</style>
@endpush

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

  {{-- Header Section --}}
  <div class="header-gradient">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div>
        <h3 class="mb-2 fw-bold">
          <i class="mdi mdi-file-document-multiple-outline me-2"></i>
          Document Distribution
        </h3>
        <p class="mb-0 opacity-90">Manage document distribution to divisions</p>
      </div>
      <div class="search-box p-2">
        <form method="GET" action="{{ route('documents.distribution.index') }}" class="d-flex gap-2">
          <input type="text"
                 name="q"
                 value="{{ $q }}"
                 class="form-control border-0"
                 placeholder="Search document number or name..."
                 style="min-width: 280px;">
          <button class="btn btn-primary" title="Search documents">
            <i class="mdi mdi-magnify"></i>
          </button>
          @if($q !== '')
            <a href="{{ route('documents.distribution.index') }}"
               class="btn btn-outline-secondary"
               title="Reset search">
              <i class="mdi mdi-refresh"></i>
            </a>
          @endif
        </form>
      </div>
    </div>
  </div>

  {{-- Flash Messages --}}
  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mt-3">
      <div class="d-flex align-items-center">
        <i class="mdi mdi-check-circle-outline me-2" style="font-size: 1.5rem;"></i>
        <div>{{ session('success') }}</div>
      </div>
      <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mt-3">
      <div class="d-flex align-items-start">
        <i class="mdi mdi-alert-circle-outline me-2 mt-1" style="font-size: 1.5rem;"></i>
        <div>
          <strong>Please check your form again.</strong>
          <ul class="mb-0 mt-2 ps-3">
            @foreach($errors->all() as $err)
              <li>{{ $err }}</li>
            @endforeach
          </ul>
        </div>
      </div>
      <button class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  {{-- Main Card --}}
  <div class="card document-card mt-3">
    <div class="card-body p-4">

      {{-- Document Selection --}}
      <div class="mb-4">
        <h5 class="section-title">
          <i class="mdi mdi-file-search-outline me-2"></i>
          Select Document
        </h5>

        <form method="GET" action="{{ route('documents.distribution.index') }}" id="formPickDoc">
          <div class="row align-items-end">
            <div class="col-lg-10">
              <label class="form-label fw-semibold">Choose Document</label>
              <select class="form-select select2"
                      name="document_id"
                      id="selectDocument"
                      data-placeholder="Select a document..."
                      style="min-width: 280px">
                <option value=""></option>
                @foreach($documents as $d)
                  <option value="{{ $d->id }}" @selected($documentId === $d->id)>
                    {{ $d->document_number ?? '-' }} — {{ $d->name }}
                    @if(!is_null($d->revision))
                      (Rev {{ $d->revision }})
                    @endif
                  </option>
                @endforeach
              </select>
            </div>

            @if($documentId)
              <div class="col-lg-2 mt-3 mt-lg-0">
                <a href="{{ route('documents.distribution.index') }}"
                   class="btn btn-outline-secondary w-100"
                   title="Change document">
                  <i class="mdi mdi-swap-horizontal me-1"></i> Change
                </a>
              </div>
            @endif
          </div>
        </form>
      </div>

      @if(! $selectedDoc)
        {{-- Empty State --}}
        <div class="empty-state">
          <div class="empty-state-icon">
            <i class="mdi mdi-file-document-outline"></i>
          </div>
          <h5 class="text-muted">No Document Selected</h5>
          <p class="text-muted">Please select a document from the dropdown above to manage its distribution.</p>
        </div>
      @else
        {{-- Document Info Card --}}
        <div class="info-card p-4 mb-4">
          <div class="row align-items-center">
            <div class="col-lg-8">
              <h6 class="fw-bold mb-2">
                <i class="mdi mdi-file-document me-1"></i> Selected Document
              </h6>
              <div class="mb-1">
                <span class="badge bg-primary me-2">{{ $selectedDoc->document_number }}</span>
                <strong>{{ $selectedDoc->name }}</strong>
              </div>
              <div class="small text-muted">
                <i class="mdi mdi-calendar-outline me-1"></i>
                Published:
                {{ optional($selectedDoc->publish_date)->format('d M Y') ?? '-' }}
                <span class="mx-2">•</span>
                Status:
                {!! $selectedDoc->is_active
                     ? '<span class="badge bg-success">Active</span>'
                     : '<span class="badge bg-danger">Inactive</span>' !!}
                @if(!is_null($selectedDoc->revision))
                  <span class="mx-2">•</span> Revision {{ $selectedDoc->revision }}
                @endif
              </div>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
              <div class="d-flex flex-column gap-2">
                <div class="stat-card py-2">
                  <div class="text-muted small">Total Divisi</div>
                  <div class="h4 mb-0 fw-bold text-primary">{{ count($departments) }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- Distribution Form --}}
        <form method="POST" action="{{ route('documents.distribution.store') }}">
          @csrf
          <input type="hidden" name="document_id" value="{{ $selectedDoc->id }}">

          <div class="mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
              <h5 class="section-title mb-0">
                <i class="mdi mdi-office-building-outline me-2"></i>
                Select Recipient Divisions
              </h5>

              <div class="form-check form-switch">
                <input class="form-check-input"
                       type="checkbox"
                       id="checkAllDeps"
                       style="cursor: pointer;">
                <label class="form-check-label fw-semibold"
                       for="checkAllDeps"
                       style="cursor: pointer;">
                  Select All Divisions (except main)
                </label>
              </div>
            </div>

            {{-- Statistics Row --}}
            @php
              $baseSelected = count($selectedDepartments);
              $primaryId    = $selectedDoc->department_id ?? null;
              if ($primaryId && ! in_array($primaryId, $selectedDepartments)) {
                  $baseSelected++;
              }
            @endphp

            <div class="row mb-4">
              <div class="col-md-4">
                <div class="stat-card">
                  <div class="stat-icon bg-label-primary">
                    <i class="mdi mdi-office-building"></i>
                  </div>
                  <div class="text-muted small text-uppercase">Total Divisions</div>
                  <div class="h3 fw-bold text-primary mb-0" id="totalDepts">{{ count($departments) }}</div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="stat-card">
                  <div class="stat-icon bg-label-success">
                    <i class="mdi mdi-check-circle"></i>
                  </div>
                  <div class="text-muted small text-uppercase">Selected</div>
                  <div class="h3 fw-bold text-success mb-0" id="selectedCount">
                    {{ $baseSelected }}
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="stat-card">
                  <div class="stat-icon bg-label-warning">
                    <i class="mdi mdi-clock-outline"></i>
                  </div>
                  <div class="text-muted small text-uppercase">Not Selected</div>
                  <div class="h3 fw-bold text-warning mb-0" id="unselectedCount">
                    {{ count($departments) - $baseSelected }}
                  </div>
                </div>
              </div>
            </div>

            {{-- Division Grid --}}
            <div class="row g-3" id="depsGrid">
              @forelse($departments as $dep)
                @php
                  $isPrimary = $selectedDoc && $selectedDoc->department_id === $dep->id;
                  $isChecked = $isPrimary || in_array($dep->id, $selectedDepartments);
                @endphp

                <div class="col-md-6 col-lg-4 col-xl-3">
                  <div class="dept-card p-3 {{ $isChecked ? 'selected' : '' }}" data-dept-id="{{ $dep->id }}">
                    <div class="form-check mb-0">
                      <input class="form-check-input dep-checkbox"
                             type="checkbox"
                             name="department_id[]"
                             id="dep_{{ $dep->id }}"
                             value="{{ $dep->id }}"
                             @checked($isChecked)
                             @if($isPrimary) disabled @endif
                             data-primary="{{ $isPrimary ? '1' : '0' }}"
                             style="cursor: pointer;">

                      {{-- hidden agar divisi utama tetap terkirim walaupun checkbox disabled --}}
                      @if($isPrimary)
                        <input type="hidden" name="department_id[]" value="{{ $dep->id }}">
                      @endif

                      <label class="form-check-label w-100"
                             for="dep_{{ $dep->id }}"
                             style="cursor: pointer;">
                        <div class="d-flex align-items-center justify-content-between">
                          <div>
                            <div class="fw-semibold">{{ $dep->name }}</div>
                            <div class="small text-muted">
                              @if($isPrimary)
                                <i class="mdi mdi-star-outline text-warning"></i>
                                Main Division
                              @else
                                <i class="mdi mdi-account-multiple-outline"></i>
                                Additional Division
                              @endif
                            </div>
                          </div>
                          <div class="ms-2">
                            @if($isChecked)
                              <span class="badge bg-success rounded-pill">
                                <i class="mdi mdi-check"></i>
                              </span>
                            @else
                              <span class="badge bg-light text-muted rounded-pill">
                                <i class="mdi mdi-plus"></i>
                              </span>
                            @endif
                          </div>
                        </div>
                      </label>
                    </div>
                  </div>
                </div>
              @empty
                <div class="col-12">
                  <div class="empty-state py-5">
                    <div class="empty-state-icon">
                      <i class="mdi mdi-office-building-outline"></i>
                    </div>
                    <h5 class="text-muted">No Divisions Available</h5>
                    <p class="text-muted">There are no divisions in the system yet.</p>
                  </div>
                </div>
              @endforelse
            </div>
          </div>

          {{-- Action Buttons --}}
          <div class="pt-4 border-top">
            <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center">
              <div class="text-muted small">
                <i class="mdi mdi-information-outline me-1"></i>
                Main division of the document is always included and cannot be unselected.
              </div>
              <div class="d-flex gap-2">
                <a href="{{ route('documents.distribution.index', ['document_id' => $selectedDoc->id]) }}"
                   class="btn btn-outline-secondary action-btn"
                   title="Reset distribution settings">
                  <i class="mdi mdi-refresh me-1"></i> Reset
                </a>
                <button type="submit"
                        class="btn btn-primary action-btn"
                        title="Save distribution settings">
                  <i class="mdi mdi-content-save-outline me-1"></i> Save Distribution
                </button>
              </div>
            </div>
          </div>
        </form>
      @endif
    </div>
  </div>
</div>
@endsection

{{-- ========== Select2 Scripts ========== --}}
@section('vendor-script')
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endsection

@section('page-script')
<script>
  (function() {
    function initSelect2() {
      const $el = $('#selectDocument');
      if ($el.length) {
        if ($el.hasClass('select2-hidden-accessible')) {
          $el.select2('destroy');
        }
        $el.select2({
          theme: 'bootstrap-5',
          width: '100%',
          placeholder: $el.data('placeholder') || 'Select a document...',
          allowClear: true,
          dropdownParent: $el.closest('.card')
        });

        $el.on('change', function () {
          document.getElementById('formPickDoc').submit();
        });
      }
    }

    // Update counter dan visual feedback
    function updateCounters() {
      const depBoxes = Array.from(document.querySelectorAll('.dep-checkbox'));
      const checked  = depBoxes.filter(cb => cb.checked).length;
      const total    = depBoxes.length;
      const unchecked = total - checked;

      const selectedCountEl   = document.getElementById('selectedCount');
      const unselectedCountEl = document.getElementById('unselectedCount');

      if (selectedCountEl) selectedCountEl.textContent = checked;
      if (unselectedCountEl) unselectedCountEl.textContent = unchecked;

      // Update visual card state
      depBoxes.forEach(cb => {
        const card  = cb.closest('.dept-card');
        const badge = card.querySelector('.badge');

        if (cb.checked) {
          card.classList.add('selected');
          if (badge) {
            badge.className = 'badge bg-success rounded-pill';
            badge.innerHTML = '<i class="mdi mdi-check"></i>';
          }
        } else {
          card.classList.remove('selected');
          if (badge) {
            badge.className = 'badge bg-light text-muted rounded-pill';
            badge.innerHTML = '<i class="mdi mdi-plus"></i>';
          }
        }
      });
    }

    // Check All functionality
    function initCheckAll() {
      const master   = document.getElementById('checkAllDeps');
      const depBoxes = Array.from(document.querySelectorAll('.dep-checkbox'));
      if (!master || depBoxes.length === 0) return;

      const normalBoxes = depBoxes.filter(cb => cb.dataset.primary !== '1');

      function refreshMasterState() {
        const total   = normalBoxes.length;
        const checked = normalBoxes.filter(cb => cb.checked).length;

        if (total === 0) {
          master.checked       = true;
          master.indeterminate = false;
          master.disabled      = true;
        } else if (checked === 0) {
          master.checked       = false;
          master.indeterminate = false;
          master.disabled      = false;
        } else if (checked === total) {
          master.checked       = true;
          master.indeterminate = false;
          master.disabled      = false;
        } else {
          master.checked       = false;
          master.indeterminate = true;
          master.disabled      = false;
        }

        updateCounters();
      }

      master.addEventListener('change', function() {
        const targetChecked = master.checked;
        normalBoxes.forEach(cb => {
          cb.checked = targetChecked;
        });
        master.indeterminate = false;
        updateCounters();
      });

      depBoxes.forEach(cb => {
        cb.addEventListener('change', refreshMasterState);
      });

      refreshMasterState();
    }

    document.addEventListener('DOMContentLoaded', function() {
      initSelect2();
      initCheckAll();
      updateCounters();
    });
  })();
</script>
@endsection
