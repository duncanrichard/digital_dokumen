@extends('layouts/contentNavbarLayout')

@section('title', 'Master Data - Document Types')

@section('content')
@php
  $me       = auth()->user();
  $role     = optional($me)->role;
  $roleName = $role->name ?? null;

  $isSuperadmin = $roleName && strcasecmp($roleName, 'Superadmin') === 0;

  $canCreate = $isSuperadmin || ($role && $role->hasPermissionTo('master.jenis-dokumen.create'));
  $canUpdate = $isSuperadmin || ($role && $role->hasPermissionTo('master.jenis-dokumen.update'));
  $canDelete = $isSuperadmin || ($role && $role->hasPermissionTo('master.jenis-dokumen.delete'));
@endphp

<div class="row gy-4">
  <div class="col-12">

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if ($errors->any())
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Validation failed.</strong> Please check the form below.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <div class="card">
      <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <div>
          <h5 class="card-title mb-1">Document Types</h5>
          <small class="text-muted">Manage your document type master data</small>
        </div>

        <div class="d-flex gap-2">
          <form method="get" class="d-flex" role="search">
            <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Search code/name..." />
          </form>

          @if($canCreate)
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
              <i class="mdi mdi-plus"></i> Add
            </button>
          @endif
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Code</th>
              <th>Name</th>
              <th>Description</th>
              <th>Status</th>
              @if($canUpdate || $canDelete)
                <th class="text-end">Actions</th>
              @endif
            </tr>
          </thead>
          <tbody>
            @forelse($items as $i => $row)
              <tr>
                <td>{{ $items->firstItem() + $i }}</td>
                <td class="fw-medium">{{ $row->kode }}</td>
                <td>{{ $row->nama }}</td>
                <td class="text-muted">{{ \Illuminate\Support\Str::limit($row->deskripsi, 80) }}</td>
                <td>
                  @if($row->is_active)
                    <span class="badge bg-label-success rounded-pill">Active</span>
                  @else
                    <span class="badge bg-label-secondary rounded-pill">Inactive</span>
                  @endif
                </td>

                @if($canUpdate || $canDelete)
                  <td class="text-end">
                    @if($canUpdate || $canDelete)
                      <button class="btn btn-sm btn-icon btn-primary"
                              data-bs-toggle="modal"
                              data-bs-target="#editModal-{{ $row->id }}"
                              title="Edit">
                        <i class="mdi mdi-pencil-outline"></i>
                      </button>
                    @endif
                  </td>
                @endif
              </tr>

              {{-- ======= EDIT MODAL (per row) ======= --}}
              @if($canUpdate || $canDelete)
                <div class="modal fade" id="editModal-{{ $row->id }}" tabindex="-1" aria-labelledby="editModalLabel-{{ $row->id }}" aria-hidden="true">
                  <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content shadow-lg rounded-3">

                      {{-- UPDATE FORM --}}
                      <form method="post" action="{{ route('master.jenis-dokumen.update', $row->id) }}" id="updateForm-{{ $row->id }}">
                        @csrf
                        @method('PUT')

                        <div class="modal-header border-0">
                          <h5 class="modal-title fw-semibold" id="editModalLabel-{{ $row->id }}">Edit Document Type</h5>
                          <button type="button" class="btn btn-icon btn-text-secondary" data-bs-dismiss="modal" aria-label="Close">
                            <i class="mdi mdi-close"></i>
                          </button>
                        </div>

                        <div class="modal-body pt-0">
                          <input type="hidden" name="_from" value="edit">
                          <input type="hidden" name="_edit_id" value="{{ $row->id }}">

                          <div class="mb-3">
                            <label class="form-label required">Code</label>
                            <input type="text"
                                   name="kode"
                                   value="{{ old('_from') === 'edit' && old('_edit_id') == $row->id ? old('kode') : $row->kode }}"
                                   class="form-control"
                                   required>
                          </div>

                          <div class="mb-3">
                            <label class="form-label required">Name</label>
                            <input type="text"
                                   name="nama"
                                   value="{{ old('_from') === 'edit' && old('_edit_id') == $row->id ? old('nama') : $row->nama }}"
                                   class="form-control"
                                   required>
                          </div>

                          <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="deskripsi" rows="3" class="form-control">{{ old('_from') === 'edit' && old('_edit_id') == $row->id ? old('deskripsi') : $row->deskripsi }}</textarea>
                          </div>

                          <div class="mb-1">
                            <label class="form-label">Status</label>
                            @php
                              $currentStatus = old('_from') === 'edit' && old('_edit_id') == $row->id
                                  ? old('is_active', $row->is_active ? '1' : '0')
                                  : ($row->is_active ? '1' : '0');
                            @endphp
                            <select name="is_active" class="form-select">
                              <option value="1" {{ $currentStatus == '1' ? 'selected' : '' }}>Active</option>
                              <option value="0" {{ $currentStatus == '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                          </div>
                        </div>

                        <div class="modal-footer border-0 d-flex justify-content-between">
                          @if($canDelete)
                            {{-- DELETE button pakai form eksternal --}}
                            <button type="submit"
                                    class="btn btn-outline-danger d-inline-flex align-items-center gap-2"
                                    form="deleteForm-{{ $row->id }}"
                                    onclick="return confirm('Delete this record?')">
                              <i class="mdi mdi-delete-outline"></i> Delete
                            </button>
                          @endif

                          <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            @if($canUpdate)
                              <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                                <i class="mdi mdi-content-save-outline"></i> Save
                              </button>
                            @endif
                          </div>
                        </div>
                      </form>
                      {{-- /UPDATE FORM --}}

                    </div>
                  </div>
                </div>

                {{-- External DELETE form (so we don't nest forms) --}}
                @if($canDelete)
                  <form id="deleteForm-{{ $row->id }}" method="post" action="{{ route('master.jenis-dokumen.destroy', $row->id) }}" class="d-none">
                    @csrf
                    @method('DELETE')
                  </form>
                @endif
              @endif
              {{-- ======= /EDIT MODAL ======= --}}

            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-4">No data available</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($items->hasPages())
        <div class="card-footer">
          {{ $items->links() }}
        </div>
      @endif
    </div>

  </div>
</div>

{{-- ================== CREATE MODAL ================== --}}
@if($canCreate)
  <div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content shadow-lg rounded-3">
        <form method="post" action="{{ route('master.jenis-dokumen.store') }}">
          @csrf
          <div class="modal-header border-0">
            <h5 class="modal-title fw-semibold" id="createModalLabel">Add Document Type</h5>
            <button type="button" class="btn btn-icon btn-text-secondary" data-bs-dismiss="modal" aria-label="Close">
              <i class="mdi mdi-close"></i>
            </button>
          </div>
          <div class="modal-body pt-0">
            <input type="hidden" name="_from" value="create">

            <div class="mb-3">
              <label class="form-label required">Code</label>
              <input type="text"
                     name="kode"
                     value="{{ old('kode') }}"
                     class="form-control @error('kode') is-invalid @enderror"
                     required
                     placeholder="e.g., SK, IN, OUT">
              @error('kode')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
              <label class="form-label required">Name</label>
              <input type="text"
                     name="nama"
                     value="{{ old('nama') }}"
                     class="form-control @error('nama') is-invalid @enderror"
                     required
                     placeholder="e.g., Surat Keputusan">
              @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
              <label class="form-label">Description</label>
              <textarea name="deskripsi"
                        rows="3"
                        class="form-control @error('deskripsi') is-invalid @enderror"
                        placeholder="Short description...">{{ old('deskripsi') }}</textarea>
              @error('deskripsi')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-1">
              <label class="form-label">Status</label>
              <select name="is_active" class="form-select @error('is_active') is-invalid @enderror">
                <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
              </select>
              @error('is_active')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>
          <div class="modal-footer border-0 d-flex justify-content-end">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
              <i class="mdi mdi-content-save-outline"></i> Save
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endif

@endsection

{{-- Styles for required star & icon buttons --}}
@push('styles')
<style>
  .form-label.required::before {
    content: '*';
    color: #dc3545;
    margin-right: .35rem;
    font-weight: 600;
  }
  .btn-icon {
    width: 34px;
    height: 34px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:0;
  }
</style>
@endpush

{{-- Auto-open Create or specific Edit modal on validation fails --}}
@section('page-script')
<script>
  // Create modal
  @if($errors->any() && old('_from') === 'create')
    const createModalEl = document.getElementById('createModal');
    if (createModalEl) {
      const createModal = new bootstrap.Modal(createModalEl);
      createModal.show();
    }
  @endif

  // Edit modal (when validation fails during update)
  @if($errors->any() && old('_from') === 'edit' && old('_edit_id'))
    const editId = @json(old('_edit_id'));
    const el = document.getElementById('editModal-' + editId);
    if (el) {
      const editModal = new bootstrap.Modal(el);
      editModal.show();
    }
  @endif
</script>
@endsection
