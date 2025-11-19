@extends('layouts/contentNavbarLayout')

@section('title', 'Document Control - Revisions')

{{-- Select2 styles (Bootstrap 5 theme) --}}
@section('vendor-style')
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endsection

@push('styles')
<style>
  .table-responsive { overflow-x: auto; }
  .badge-round { border-radius:999px; padding:.35em .75em; font-weight:500; }
  .revision-badge { min-width:45px; text-align:center; }
</style>
@endpush

@section('content')
<div class="row gy-4">
  <div class="col-12">

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="mdi mdi-check-circle-outline me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <div class="card shadow-sm">
      <div class="card-header bg-white border-bottom">
        <div class="d-flex flex-wrap align-items-center justify-content-between w-100 gap-3 py-2">
          <div>
            <h4 class="card-title mb-1">📝 Revisions</h4>
            <p class="text-muted mb-0 small">Kelola dan buat revisi dari dokumen yang sudah ada</p>
          </div>
        </div>

        {{-- Filters --}}
        <form method="get" class="mt-3">
          <div class="row g-3 align-items-end">
            <div class="col-12 col-md-3">
              <label class="form-label small text-muted mb-1">Document Type</label>
              <select name="document_type_id" class="form-select select2" data-placeholder="All types">
                <option value=""></option>
                @foreach($documentTypes as $dt)
                  <option value="{{ $dt->id }}" {{ ($filterJenisId ?? '')===$dt->id ? 'selected' : '' }}>
                    {{ $dt->kode }} - {{ $dt->nama }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label small text-muted mb-1">Department</label>
              <select name="department_id" class="form-select select2" data-placeholder="All departments">
                <option value=""></option>
                @foreach($departments as $dep)
                  <option value="{{ $dep->id }}" {{ ($filterDeptId ?? '')===$dep->id ? 'selected' : '' }}>
                    {{ $dep->code }} - {{ $dep->name }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label small text-muted mb-1">Search</label>
              <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control" placeholder="Cari nama atau nomor dokumen...">
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
              <button type="submit" class="btn btn-primary flex-fill">
                <i class="mdi mdi-magnify me-1"></i> Search
              </button>
              @if(($filterJenisId ?? null) || ($filterDeptId ?? null) || ($q ?? ''))
                <a href="{{ route('documents.revisions.index') }}" class="btn btn-outline-secondary" title="Clear">
                  <i class="mdi mdi-close"></i>
                </a>
              @endif
            </div>
          </div>
        </form>
      </div>

      <div class="card-body p-0">
        @if(($grouped ?? collect())->isEmpty())
          <div class="p-4 text-center text-muted">
            <i class="mdi mdi-file-document-outline" style="font-size:3rem"></i>
            <div class="mt-2">Tidak ada data dokumen.</div>
          </div>
        @else
          <div class="accordion accordion-flush" id="revTree">
            @foreach($grouped as $docNumber => $rows)
              @php
                $sorted = $rows->sortBy('revision');
                $latest = $rows->sortByDesc('revision')->first();
                $accId  = 'revTreeItem_'.$loop->index;
              @endphp
              <div class="accordion-item">
                <h2 class="accordion-header" id="heading-{{ $accId }}">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                          data-bs-target="#collapse-{{ $accId }}" aria-expanded="false"
                          aria-controls="collapse-{{ $accId }}">
                    <div class="d-flex w-100 align-items-center gap-2 flex-wrap">
                      <div class="fw-semibold">
                        <i class="mdi mdi-file-document me-1"></i>{{ $docNumber }}
                      </div>
                      <span class="badge bg-primary badge-round">Latest: R{{ $latest->revision ?? 0 }}</span>
                      <span class="ms-auto text-muted small d-none d-md-inline">
                        {{ $latest->jenisDokumen->nama ?? '—' }} • {{ $latest->department->name ?? '—' }}
                      </span>
                    </div>
                  </button>
                </h2>
                <div id="collapse-{{ $accId }}" class="accordion-collapse collapse" aria-labelledby="heading-{{ $accId }}" data-bs-parent="#revTree">
                  <div class="accordion-body">
                    <div class="d-flex justify-content-end mb-3">
                      <button
                        class="btn btn-sm btn-primary btn-open-revise"
                        data-base-id="{{ $latest->id }}"
                        data-doc-number="{{ $docNumber }}"
                        data-latest-rev="{{ $latest->revision ?? 0 }}"
                        data-bs-toggle="modal"
                        data-bs-target="#reviseModal"
                      >
                        <i class="mdi mdi-file-restore-outline me-1"></i> Create Revision
                      </button>
                    </div>

                    <div class="table-responsive">
                      <table class="table table-hover align-middle mb-0">
                        <thead>
                          <tr>
                            <th class="text-center">Revision</th>
                            <th>Name</th>
                            <th class="text-center">Publish Date</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">File</th>
                          </tr>
                        </thead>
                        <tbody>
                          @foreach($sorted as $row)
                            <tr>
                              <td class="text-center">
                                <span class="badge revision-badge {{ $row->revision == ($latest->revision ?? 0) ? 'bg-primary' : 'bg-secondary' }}">
                                  R{{ $row->revision ?? 0 }}
                                </span>
                              </td>
                              <td class="fw-semibold">{{ $row->name }}</td>
                              <td class="text-center text-nowrap small">{{ \Carbon\Carbon::parse($row->publish_date)->format('d M Y') }}</td>
                              <td class="text-center">
                                @if($row->is_active)
                                  <span class="badge bg-success rounded-pill">Active</span>
                                @else
                                  <span class="badge bg-danger rounded-pill">Inactive</span>
                                @endif
                              </td>
                              <td class="text-center">
                                @php $fileUrl = $row->file_path ? asset('storage/'.$row->file_path) : null; @endphp
                                @if($fileUrl)
                                  <div class="btn-group btn-group-sm">
                                    <a href="{{ $fileUrl }}" target="_blank" rel="noopener" class="btn btn-outline-primary" title="View">
                                      <i class="mdi mdi-eye"></i>
                                    </a>
                                    <a href="{{ $fileUrl }}" download class="btn btn-outline-secondary" title="Download">
                                      <i class="mdi mdi-download"></i>
                                    </a>
                                  </div>
                                @else
                                  <span class="text-muted">—</span>
                                @endif
                              </td>
                            </tr>
                          @endforeach
                        </tbody>
                      </table>
                    </div>

                  </div>
                </div>
              </div>
            @endforeach
          </div>
        @endif
      </div>

      @if($items->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
          <div class="text-muted small">
            Showing {{ $items->firstItem() }} to {{ $items->lastItem() }} of {{ $items->total() }} document rows (grouped by number)
          </div>
          {{ $items->links() }}
        </div>
      @endif
    </div>
  </div>
</div>

{{-- ======= REVISE MODAL ======= --}}
<div class="modal fade" id="reviseModal" tabindex="-1" aria-labelledby="reviseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow-lg rounded-3">
      <form method="post" action="{{ route('documents.revisions.store') }}" enctype="multipart/form-data" id="formRevise">
        @csrf
        <input type="hidden" name="base_id" id="rev_base_id" value="">
        <div class="modal-header border-0">
          <h5 class="modal-title fw-semibold" id="reviseModalLabel">Create Revision</h5>
          <button type="button" class="btn btn-icon btn-text-secondary" data-bs-dismiss="modal" aria-label="Close">
            <i class="mdi mdi-close"></i>
          </button>
        </div>
        <div class="modal-body pt-0">
          <div class="alert alert-secondary small mb-3 d-none" id="rev_info"></div>

          <div class="mb-3">
            <label class="form-label required">Document Name</label>
            <input type="text" class="form-control" name="document_name" required placeholder="Nama dokumen (versi revisi)">
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label required">Publish Date</label>
              <input type="date" class="form-control" name="publish_date" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label d-block">Status</label>
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="rev_is_active" name="is_active" value="1" checked>
                <label class="form-check-label" for="rev_is_active">Active</label>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label required">Upload Document (PDF)</label>
            <input type="file" class="form-control" name="file" accept="application/pdf,.pdf" required>
            <small class="text-muted">PDF only. Max 10MB.</small>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="mdi mdi-file-restore-outline"></i> Create Revision
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@section('vendor-script')
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endsection

@section('page-script')
<script>
  (function () {
    function initSelect2(scope) {
      const $scope = scope ? $(scope) : $(document);
      $scope.find('.select2').each(function () {
        const $el = $(this);
        if ($el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
        $el.select2({
          theme: 'bootstrap-5',
          width: '100%',
          placeholder: $el.data('placeholder') || '',
          allowClear: true,
          dropdownParent: $el.closest('.card')
        });
      });
    }

    // Open Revise modal: set base_id + info
    $(document).on('click', '.btn-open-revise', function () {
      const baseId  = this.getAttribute('data-base-id');
      const number  = this.getAttribute('data-doc-number') || '';
      const latest  = this.getAttribute('data-latest-rev') || '0';

      $('#rev_base_id').val(baseId || '');
      const info = document.getElementById('rev_info');
      if (number) {
        info.classList.remove('d-none');
        info.innerHTML = '<strong>Base:</strong> ' + number + ' &nbsp; <span class="badge bg-primary">Latest: R' + latest + '</span>';
      } else {
        info.classList.add('d-none');
        info.innerHTML = '';
      }
    });

    document.addEventListener('DOMContentLoaded', function () {
      initSelect2();
    });
  })();
</script>
@endsection
