@extends('layouts/contentNavbarLayout')

@section('title', 'Document Control - Documents')

{{-- Select2 styles (Bootstrap 5 theme) --}}
@section('vendor-style')
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endsection

@push('styles')
<style>
  .table-responsive { overflow-x: auto; }
  .col-idx { width: 60px; }
  .col-aksi { width: 140px; text-align: center; }
  .form-label.required::before { content: '*'; color: #dc3545; font-weight: 600; margin-right: .35rem; }
  .card-header .select2-container { min-width: 100%; }
  @media (min-width: 768px) { .card-header .select2-container { min-width: 220px; } }

  .accordion-button { padding: 1rem 1.25rem; font-size: 0.95rem; }
  .accordion-button:not(.collapsed) { background-color: #f8f9fa; color: #495057; box-shadow: none; }
  .accordion-button .doc-header { display: flex; width: 100%; align-items: center; gap: 1rem; flex-wrap: wrap; }
  .doc-number { font-size: 1rem; font-weight: 600; color: #2c3e50; min-width: 200px; }
  .doc-badges { display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
  .doc-meta { margin-left: auto; display: flex; gap: 1rem; font-size: 0.85rem; color: #6c757d; }
  .badge-round { border-radius: 999px; padding: 0.35em 0.75em; font-size: 0.8rem; font-weight: 500; }

  .table > :not(caption) > * > * { padding: 0.75rem 0.5rem; }
  .table thead th {
    background-color: #f8f9fa; font-weight: 600; font-size: 0.85rem;
    text-transform: uppercase; letter-spacing: 0.3px; border-bottom: 2px solid #dee2e6;
  }
  .table tbody tr:hover { background-color: #f8f9fa; }

  .revision-badge { display: inline-block; min-width: 45px; text-align: center; }
  .status-badge { display: inline-block; min-width: 85px; text-align: center; font-weight: 600; }

  .btn-action { padding: 0.35rem 0.75rem; font-size: 0.85rem; border-radius: 0.25rem; }

  .empty-state { padding: 3rem 1rem; text-align: center; }
  .empty-state i { font-size: 4rem; color: #dee2e6; margin-bottom: 1rem; }

  @media (max-width: 768px) {
    .doc-number { min-width: 100%; margin-bottom: 0.5rem; }
    .doc-meta { margin-left: 0; width: 100%; justify-content: flex-start; }
    .accordion-button { padding: 0.75rem 1rem; }
  }
</style>
@endpush

@section('content')
@php
  $me       = auth()->user();
  $role     = optional($me)->role; // relasi role() di model User
  $roleName = $role->name ?? null;

  // Superadmin (bypass semua)
  $isSuperadmin = $roleName && strcasecmp($roleName, 'Superadmin') === 0;

  // Permission berdasarkan ROLE (bukan $user->can())
  $canCreate = $isSuperadmin || ($role && $role->hasPermissionTo('documents.upload.create'));
  $canUpdate = $isSuperadmin || ($role && $role->hasPermissionTo('documents.upload.update'));
  $canDelete = $isSuperadmin || ($role && $role->hasPermissionTo('documents.upload.delete'));
@endphp

<div class="row gy-4">
  <div class="col-12">

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="mdi mdi-check-circle-outline me-2"></i>
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if ($errors->any() && !old('_from'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="mdi mdi-alert-circle-outline me-2"></i>
        <strong>Validation failed.</strong> Please check the form.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <div class="card shadow-sm">
      <div class="card-header bg-white border-bottom">
        <div class="d-flex flex-wrap align-items-center justify-content-between w-100 gap-3 py-2">
          <div>
            <h4 class="card-title mb-1">📚 Document Library</h4>
            <p class="text-muted mb-0 small">Managed controlled documents & company assets</p>
          </div>

          @if($canCreate)
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal" id="btnOpenCreate">
              <i class="mdi mdi-plus me-1"></i> Add Document
            </button>
          @endif

        </div>

        {{-- Filters --}}
        <form method="get" class="mt-4">
          <div class="row g-3 align-items-end">
            <div class="col-12 col-md-3">
              <label class="form-label small text-muted mb-1">Document Type</label>
              <select name="document_type_id" class="form-select select2" data-placeholder="All types">
                <option value=""></option>
                @foreach($documentTypes as $dt)
                  <option value="{{ $dt->id }}" {{ ($filterJenisId ?? '') === $dt->id ? 'selected' : '' }}>
                    {{ $dt->kode }} - {{ $dt->nama }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label small text-muted mb-1">Divisi</label>
              <select name="department_id" class="form-select select2" data-placeholder="All divisions">
                <option value=""></option>
                @foreach($departments as $dep)
                  <option value="{{ $dep->id }}" {{ ($filterDeptId ?? '') === $dep->id ? 'selected' : '' }}>
                    {{ $dep->code }} - {{ $dep->name }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label small text-muted mb-1">Search</label>
              <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control" placeholder="Search by name or number...">
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
              <button type="submit" class="btn btn-primary flex-fill">
                <i class="mdi mdi-magnify me-1"></i> Search
              </button>
              @if(($filterJenisId ?? null) || ($filterDeptId ?? null) || ($q ?? ''))
                <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary" title="Clear filters">
                  <i class="mdi mdi-close"></i>
                </a>
              @endif
            </div>
          </div>
        </form>
      </div>

      <div class="card-body p-0">
        {{-- ===== TREE / GROUPED BY DOCUMENT NUMBER ===== --}}
        @php
          $grouped = collect($items->items() ?? [])->groupBy('document_number');
        @endphp

        @if($grouped->isEmpty())
          <div class="empty-state">
            <i class="mdi mdi-file-document-outline"></i>
            <h5 class="text-muted">No Documents Found</h5>
            <p class="text-muted small">Start by adding your first document using the "Add Document" button above.</p>
          </div>
        @else
          <div class="accordion accordion-flush" id="docTree">
            @foreach($grouped as $docNumber => $rows)
              @php
                $sorted = $rows->sortBy('revision');
                $latest = $rows->sortByDesc('revision')->first();
                $accId  = 'docTreeItem_'.$loop->index;
              @endphp

              <div class="accordion-item">
                <h2 class="accordion-header" id="heading-{{ $accId }}">
                  <button class="accordion-button collapsed" type="button"
                          data-bs-toggle="collapse"
                          data-bs-target="#collapse-{{ $accId }}"
                          aria-expanded="false"
                          aria-controls="collapse-{{ $accId }}">
                    <div class="doc-header">
                      <div class="doc-number">
                        <i class="mdi mdi-file-document me-2"></i>{{ $docNumber }}
                      </div>
                      <div class="doc-badges">
                        <span class="badge bg-light text-dark border">
                          <i class="mdi mdi-file-multiple"></i> {{ $rows->count() }} version{{ $rows->count() > 1 ? 's' : '' }}
                        </span>
                        <span class="badge bg-primary badge-round">Latest: R{{ $latest->revision ?? 0 }}</span>
                        @if($latest->is_active)
                          <span class="badge bg-success badge-round">Active</span>
                        @endif
                      </div>
                      <div class="doc-meta d-none d-lg-flex">
                        <span><i class="mdi mdi-folder-outline me-1"></i>{{ $latest->jenisDokumen->nama ?? '—' }}</span>
                        <span><i class="mdi mdi-office-building me-1"></i>{{ $latest->department->name ?? '—' }}</span>
                        <span><i class="mdi mdi-calendar me-1"></i>{{ optional(\Carbon\Carbon::parse($latest->publish_date))->format('d M Y') }}</span>
                      </div>
                    </div>
                  </button>
                </h2>

                <div id="collapse-{{ $accId }}" class="accordion-collapse collapse"
                     aria-labelledby="heading-{{ $accId }}" data-bs-parent="#docTree">
                  <div class="accordion-body p-0">
                    <div class="table-responsive">
                      <table class="table table-hover align-middle mb-0">
                        <thead>
                          <tr>
                            <th class="col-idx text-center">#</th>
                            <th class="text-center">Revision</th>
                            <th>Document Name</th>
                            <th class="text-center">Type</th>
                            <th class="text-center">Divisi</th>
                            <th class="text-center">Publish Date</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">File</th>
                            @if($canUpdate || $canDelete)
                              <th class="col-aksi text-center">Actions</th>
                            @endif
                          </tr>
                        </thead>
                        <tbody>
                          @foreach($sorted as $row)
                            <tr>
                              <td class="text-center text-muted small">
                                {{ $items->firstItem() + $loop->parent->index + $loop->index }}
                              </td>

                              <td class="text-center">
                                <span class="badge revision-badge {{ $row->revision == ($latest->revision ?? 0) ? 'bg-primary' : 'bg-secondary' }}">
                                  R{{ $row->revision ?? 0 }}
                                </span>
                              </td>

                              <td><div class="fw-semibold">{{ $row->name }}</div></td>

                              <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $row->jenisDokumen->kode ?? '' }}</span>
                              </td>

                              <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $row->department->code ?? '' }}</span>
                              </td>

                              <td class="text-center text-nowrap small">
                                {{ \Carbon\Carbon::parse($row->publish_date)->format('d M Y') }}
                              </td>

                              <td class="text-center">
                                @if($row->is_active)
                                  <span class="badge status-badge bg-success text-white rounded-pill">
                                    <i class="mdi mdi-check-circle me-1"></i>Active
                                  </span>
                                @else
                                  <span class="badge status-badge bg-danger text-white rounded-pill">
                                    <i class="mdi mdi-close-circle me-1"></i>Inactive
                                  </span>
                                @endif
                              </td>

                              <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                  {{-- VIEW -> ke gate stream() --}}
                                  <a href="{{ route('documents.file', $row->id) }}"
                                     class="btn btn-outline-primary btn-action"
                                     title="View">
                                    <i class="mdi mdi-eye"></i>
                                  </a>

                                  {{-- DOWNLOAD -> langsung ke rawFile, gunakan dl=1 --}}
                                  <a href="{{ route('documents.file.raw', [$row->id, 'dl' => 1]) }}"
                                     class="btn btn-outline-secondary btn-action"
                                     title="Download">
                                    <i class="mdi mdi-download"></i>
                                  </a>
                                </div>
                              </td>

                              @if($canUpdate || $canDelete)
                                <td class="text-center">
                                  <div class="dropdown">
                                    <button type="button" class="btn btn-sm btn-text-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                      <i class="mdi mdi-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">

                                      @if($canUpdate)
                                        <li>
                                          <a href="javascript:void(0);" class="dropdown-item btn-edit-document"
                                             data-update-url="{{ route('documents.update', $row->id) }}"
                                             data-id="{{ $row->id }}"
                                             data-document_type_id="{{ $row->jenis_dokumen_id }}"
                                             data-department_id="{{ $row->department_id }}"
                                             data-name="{{ $row->name }}"
                                             data-publish_date="{{ \Carbon\Carbon::parse($row->publish_date)->format('Y-m-d') }}"
                                             data-is_active="{{ $row->is_active ? 1 : 0 }}"
                                             data-bs-toggle="modal" data-bs-target="#editModal">
                                            <i class="mdi mdi-pencil-outline me-2"></i> Edit
                                          </a>
                                        </li>
                                      @endif

                                      @if($canUpdate && $canDelete)
                                        <li><hr class="dropdown-divider"></li>
                                      @endif

                                      @if($canDelete)
                                        <li>
                                          <a href="#" class="dropdown-item text-danger btn-delete-document"
                                             data-delete-url="{{ route('documents.destroy', $row->id) }}">
                                            <i class="mdi mdi-trash-can-outline me-2"></i> Delete
                                          </a>
                                        </li>
                                      @endif

                                    </ul>
                                  </div>
                                </td>
                              @endif
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
        {{-- ===== /TREE ===== --}}

      </div>

      @if($items->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
          <div class="text-muted small">
            Showing {{ $items->firstItem() }} to {{ $items->lastItem() }} of {{ $items->total() }} documents
          </div>
          {{ $items->links() }}
        </div>
      @endif
    </div>

  </div>
</div>

{{-- ================== CREATE MODAL (Hanya untuk dokumen baru) ================== --}}
@if($canCreate)
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow-lg rounded-3">
      <form method="post" action="{{ route('documents.store') }}" enctype="multipart/form-data" id="formCreate">
        @csrf
        <input type="hidden" name="_from" value="create">

        <div class="modal-header border-0">
          <h5 class="modal-title fw-semibold" id="createModalLabel">Add Document</h5>
          <button type="button" class="btn btn-icon btn-text-secondary" data-bs-dismiss="modal" aria-label="Close">
            <i class="mdi mdi-close"></i>
          </button>
        </div>

        <div class="modal-body pt-0">
          @if ($errors->any() && old('_from')==='create')
            <div class="alert alert-danger">
              <i class="mdi mdi-alert-circle-outline me-1"></i> Gagal menyimpan. Periksa kembali:
              <ul class="mb-0 mt-2 ps-3">
                @foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach
              </ul>
            </div>
          @endif

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label required">Document Type</label>
              <select class="form-select select2 @error('document_type_id') is-invalid @enderror"
                      name="document_type_id" id="create_document_type_id"
                      data-placeholder="Select Document" required>
                <option value=""></option>
                @foreach($documentTypes as $dt)
                  <option value="{{ $dt->id }}" {{ old('_from')==='create' && old('document_type_id')===$dt->id ? 'selected' : '' }}>
                    {{ $dt->kode }} - {{ $dt->nama }}
                  </option>
                @endforeach
              </select>
              @error('document_type_id') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label required">Divisi</label>
              <select class="form-select select2 @error('department_id') is-invalid @enderror"
                      name="department_id" id="create_department_id"
                      data-placeholder="Select Division" required>
                <option value=""></option>
                @foreach($departments as $dep)
                  <option value="{{ $dep->id }}" {{ old('_from')==='create' && old('department_id')===$dep->id ? 'selected' : '' }}>
                    {{ $dep->code }} - {{ $dep->name }}
                  </option>
                @endforeach
              </select>
              @error('department_id') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label required">Document Name</label>
            <input type="text" class="form-control @error('document_name') is-invalid @enderror"
                   name="document_name" id="create_name"
                   value="{{ old('_from')==='create' ? old('document_name') : '' }}"
                   placeholder="Document Name" required>
            @error('document_name') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
          </div>

          <div class="mb-3">
            <label class="form-label required">Publish Date</label>
            <input type="date" class="form-control @error('publish_date') is-invalid @enderror"
                   name="publish_date" id="create_publish_date"
                   value="{{ old('_from')==='create' ? old('publish_date') : '' }}"
                   required>
            @error('publish_date') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
          </div>

          <div class="mb-3">
            <label class="form-label required">Upload Document (PDF)</label>
            <input type="file" class="form-control @error('file') is-invalid @enderror"
                   name="file" accept="application/pdf,.pdf" required>
            @error('file') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            <small class="text-muted">PDF only. Max 10MB.</small>
          </div>

          <div class="mb-3">
            <label class="form-label d-block">Status</label>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                     {{ old('_from')==='create' ? (old('is_active') ? 'checked' : '') : 'checked' }}>
              <label class="form-check-label" for="is_active">Active</label>
            </div>
          </div>
        </div>

        <div class="modal-footer border-0 d-flex justify-content-end">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="mdi mdi-content-save-outline"></i> Save
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif

{{-- ================== EDIT MODAL ================== --}}
@if($canUpdate)
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow-lg rounded-3">
      <form method="post" action="#" enctype="multipart/form-data" id="formEdit">
        @csrf @method('PUT')
        <input type="hidden" name="_from" value="edit">
        <input type="hidden" name="id" id="edit_id" value="{{ old('_from')==='edit' ? old('id') : '' }}">
        <div class="modal-header border-0">
          <h5 class="modal-title fw-semibold" id="editModalLabel">Edit Document</h5>
          <button type="button" class="btn btn-icon btn-text-secondary" data-bs-dismiss="modal" aria-label="Close">
            <i class="mdi mdi-close"></i>
          </button>
        </div>

        <div class="modal-body pt-0">
          @if ($errors->any() && old('_from')==='edit')
            <div class="alert alert-danger">
              <i class="mdi mdi-alert-circle-outline me-1"></i> Gagal update. Periksa kembali:
              <ul class="mb-0 mt-2 ps-3">
                @foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach
              </ul>
            </div>
          @endif

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label required">Document Type</label>
              <select class="form-select select2" name="document_type_id" id="edit_document_type_id" data-placeholder="Select Document" required>
                <option value=""></option>
                @foreach($documentTypes as $dt)
                  <option value="{{ $dt->id }}" @selected(old('_from')==='edit' && old('document_type_id')===$dt->id)>{{ $dt->kode }} - {{ $dt->nama }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label required">Divisi</label>
              <select class="form-select select2" name="department_id" id="edit_department_id" data-placeholder="Select Division" required>
                <option value=""></option>
                @foreach($departments as $dep)
                  <option value="{{ $dep->id }}" @selected(old('_from')==='edit' && old('department_id')===$dep->id)>{{ $dep->code }} - {{ $dep->name }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label required">Document Name</label>
            <input type="text" class="form-control" name="document_name" id="edit_name"
                   value="{{ old('_from')==='edit' ? old('document_name') : '' }}" required>
          </div>

          <div class="mb-3">
            <label class="form-label required">Publish Date</label>
            <input type="date" class="form-control" name="publish_date" id="edit_publish_date"
                   value="{{ old('_from')==='edit' ? old('publish_date') : '' }}" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Replace Document (PDF)</label>
            <input type="file" class="form-control @error('file') is-invalid @enderror"
                   name="file" accept="application/pdf,.pdf">
            @error('file') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            <small class="text-muted d-block">Leave empty to keep current file.</small>
          </div>

          <div class="mb-3">
            <label class="form-label d-block">Status</label>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" value="1"
                     {{ old('_from')==='edit' ? (old('is_active') ? 'checked' : '') : '' }}>
              <label class="form-check-label" for="edit_is_active">Active</label>
            </div>
          </div>
        </div>

        <div class="modal-footer border-0 d-flex justify-content-end">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">
            <i class="mdi mdi-content-save-outline"></i> Update
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif

{{-- FORM DELETE tersembunyi --}}
@if($canDelete)
<form id="formDeleteDocument" method="POST" class="d-none">
  @csrf @method('DELETE')
</form>
@endif
@endsection

{{-- Select2 scripts --}}
@section('vendor-script')
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endsection

@section('page-script')
<script>
  (function() {
    function initSelect2(scope) {
      const $scope = scope ? $(scope) : $(document);
      $scope.find('.select2').each(function () {
        const $el = $(this);
        if ($el.hasClass("select2-hidden-accessible")) {
          $el.select2('destroy');
        }
        $el.select2({
          theme: 'bootstrap-5',
          width: $el.data('width') ? $el.data('width') : '100%',
          placeholder: $el.data('placeholder') || '',
          allowClear: true,
          dropdownParent: $el.closest('.modal').length ? $el.closest('.modal') : $el.closest('.card')
        });
      });
    }

    $(document).ready(function () {
      // Init awal (filter di card + field di modal yg sudah ada di DOM)
      initSelect2();

      // Init ulang Select2 setiap modal dibuka (agar width & dropdownParent rapi)
      $('#createModal').on('shown.bs.modal', function () {
        initSelect2(this);
      });

      $('#editModal').on('shown.bs.modal', function () {
        initSelect2(this);
      });

      // Auto-open CREATE modal if validation fails (mode create only)
      @if ($errors->any() && old('_from')==='create')
        const cm = document.getElementById('createModal');
        if (cm) {
          const bsModal = new bootstrap.Modal(cm);
          bsModal.show();
          setTimeout(() => { initSelect2(cm); }, 150);
        }
      @endif

      // EDIT - handler
      $(document).on('click', '.btn-edit-document', function(e) {
        e.preventDefault();
        const btn = $(this);
        const form = document.getElementById('formEdit');
        if (!form) return;
        form.action = btn.data('update-url') || '#';
        document.getElementById('edit_id').value = btn.data('id') || '';

        const setVal = (id, val) => {
          const el = document.getElementById(id);
          if (el) el.value = val ?? '';
        };
        setVal('edit_name', btn.data('name'));
        setVal('edit_publish_date', btn.data('publish_date'));

        $('#edit_document_type_id').val(btn.data('document_type_id') || '').trigger('change');
        $('#edit_department_id').val(btn.data('department_id') || '').trigger('change');

        const isActive = btn.data('is_active');
        const chk = document.getElementById('edit_is_active');
        if (chk) chk.checked = (isActive === 1 || isActive === '1');

        const modal = document.getElementById('editModal');
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        setTimeout(() => { initSelect2(modal); }, 100);
      });

      // Add new (reset form)
      $('#btnOpenCreate').on('click', function() {
        const form = document.getElementById('formCreate');
        if (!form) return;
        form.reset();
        $('#create_document_type_id').val('').trigger('change');
        $('#create_department_id').val('').trigger('change');
      });

      // Auto-open EDIT modal if validation failed on update
      @if ($errors->any() && old('_from')==='edit')
        const em = document.getElementById('editModal');
        if (em) {
          const bsModal = new bootstrap.Modal(em);
          bsModal.show();
          setTimeout(() => { initSelect2(em); }, 150);
        }
      @endif

      // Delete
      $(document).on('click', '.btn-delete-document', function(e) {
        e.preventDefault();
        if (!confirm('Delete this document? This action cannot be undone.')) return;
        const form = document.getElementById('formDeleteDocument');
        if (!form) return;
        form.action = $(this).data('delete-url') || '#';
        form.submit();
      });
    });
  })();
</script>
@endsection
