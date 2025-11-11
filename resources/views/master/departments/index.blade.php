@extends('layouts/contentNavbarLayout')

@section('title', 'Master Data - Departments')

@section('content')
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
          <h5 class="card-title mb-1">Departments</h5>
          <small class="text-muted">Manage your departments</small>
        </div>

        <div class="d-flex gap-2">
          <form method="get" class="d-flex" role="search">
            <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Search code/name..." />
          </form>
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="mdi mdi-plus"></i> Add
          </button>
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
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($items as $i => $row)
              <tr>
                <td>{{ $items->firstItem() + $i }}</td>
                <td class="fw-medium">{{ $row->code }}</td>
                <td>{{ $row->name }}</td>
                <td class="text-muted">{{ \Illuminate\Support\Str::limit($row->description, 80) }}</td>
                <td>
                  @if($row->is_active)
                    <span class="badge bg-label-success rounded-pill">Active</span>
                  @else
                    <span class="badge bg-label-secondary rounded-pill">Inactive</span>
                  @endif
                </td>
                <td class="text-end">
                  <button class="btn btn-sm btn-icon btn-primary"
                          data-bs-toggle="modal"
                          data-bs-target="#editModal-{{ $row->id }}"
                          title="Edit">
                    <i class="mdi mdi-pencil-outline"></i>
                  </button>
                </td>
              </tr>

              {{-- ===== EDIT MODAL (per row) ===== --}}
              <div class="modal fade" id="editModal-{{ $row->id }}" tabindex="-1" aria-labelledby="editModalLabel-{{ $row->id }}" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                  <div class="modal-content shadow-lg rounded-3">
                    <form method="post" action="{{ route('master.departments.update', $row->id) }}" id="updateForm-{{ $row->id }}">
                      @csrf
                      @method('PUT')

                      <div class="modal-header border-0">
                        <h5 class="modal-title fw-semibold" id="editModalLabel-{{ $row->id }}">Edit Department</h5>
                        <button type="button" class="btn btn-icon btn-text-secondary" data-bs-dismiss="modal" aria-label="Close">
                          <i class="mdi mdi-close"></i>
                        </button>
                      </div>

                      <div class="modal-body pt-0">
                        <input type="hidden" name="_from" value="edit">
                        <input type="hidden" name="_edit_id" value="{{ $row->id }}">

                        <div class="mb-3">
                          <label class="form-label required">Code</label>
                          <input type="text" name="code" value="{{ old('code', $row->code) }}" class="form-control" required>
                        </div>

                        <div class="mb-3">
                          <label class="form-label required">Name</label>
                          <input type="text" name="name" value="{{ old('name', $row->name) }}" class="form-control" required>
                        </div>

                        <div class="mb-3">
                          <label class="form-label">Description</label>
                          <textarea name="description" rows="3" class="form-control">{{ old('description', $row->description) }}</textarea>
                        </div>

                        <div class="mb-1">
                          <label class="form-label">Status</label>
                          <select name="is_active" class="form-select">
                            <option value="1" {{ old('is_active', $row->is_active ? '1' : '0') == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('is_active', $row->is_active ? '1' : '0') == '0' ? 'selected' : '' }}>Inactive</option>
                          </select>
                        </div>
                      </div>

                      <div class="modal-footer border-0 d-flex justify-content-between">
                        {{-- External DELETE submit (no nested forms) --}}
                        <button type="submit"
                                class="btn btn-outline-danger d-inline-flex align-items-center gap-2"
                                form="deleteForm-{{ $row->id }}"
                                onclick="return confirm('Delete this department?')">
                          <i class="mdi mdi-delete-outline"></i> Delete
                        </button>

                        <div class="d-flex gap-2">
                          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                          <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2">
                            <i class="mdi mdi-content-save-outline"></i> Save
                          </button>
                        </div>
                      </div>
                    </form>
                  </div>
                </div>
              </div>

              {{-- External DELETE form --}}
              <form id="deleteForm-{{ $row->id }}" method="post" action="{{ route('master.departments.destroy', $row->id) }}" class="d-none">
                @csrf
                @method('DELETE')
              </form>
              {{-- ===== /EDIT MODAL ===== --}}
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

{{-- ===== CREATE MODAL ===== --}}
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow-lg rounded-3">
      <form method="post" action="{{ route('master.departments.store') }}">
        @csrf
        <div class="modal-header border-0">
          <h5 class="modal-title fw-semibold" id="createModalLabel">Add Department</h5>
          <button type="button" class="btn btn-icon btn-text-secondary" data-bs-dismiss="modal" aria-label="Close">
            <i class="mdi mdi-close"></i>
          </button>
        </div>
        <div class="modal-body pt-0">
          <input type="hidden" name="_from" value="create">

          <div class="mb-3">
            <label class="form-label required">Code</label>
            <input type="text" name="code" value="{{ old('code') }}" class="form-control @error('code') is-invalid @enderror" required placeholder="e.g., HR, FIN, OPS">
            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="mb-3">
            <label class="form-label required">Name</label>
            <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required placeholder="e.g., Human Resources">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror" placeholder="Short description...">{{ old('description') }}</textarea>
            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
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

@push('styles')
<style>
  .form-label.required::before { content: '*'; color: #dc3545; margin-right: .35rem; font-weight: 600; }
  .btn-icon { width: 34px; height: 34px; display:inline-flex; align-items:center; justify-content:center; padding:0; }
</style>
@endpush

@section('page-script')
<script>
  // Auto-open Create modal on validation fail
  @if($errors->any() && old('_from') === 'create')
    new bootstrap.Modal(document.getElementById('createModal')).show();
  @endif

  // Auto-open Edit modal on validation fail
  @if($errors->any() && old('_from') === 'edit' && old('_edit_id'))
    const el = document.getElementById('editModal-{{ old('_edit_id') }}');
    if (el) new bootstrap.Modal(el).show();
  @endif
</script>
@endsection

@endsection
