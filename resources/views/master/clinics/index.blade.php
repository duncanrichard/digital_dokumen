@extends('layouts/contentNavbarLayout')

@section('title', 'Master Data - Klinik')

@section('content')
@php
  $me       = auth()->user();
  $role     = optional($me)->role;
  $roleName = $role->name ?? null;

  $isSuperadmin = $roleName && strcasecmp($roleName, 'Superadmin') === 0;

  $canCreate = $isSuperadmin || ($role && $role->hasPermissionTo('master.clinics.create'));
  $canUpdate = $isSuperadmin || ($role && $role->hasPermissionTo('master.clinics.update'));
  $canDelete = $isSuperadmin || ($role && $role->hasPermissionTo('master.clinics.delete'));
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
          <h5 class="card-title mb-1">Klinik</h5>
          <small class="text-muted">Kelola data klinik</small>
        </div>

        <div class="d-flex gap-2">
          <form method="get" class="d-flex" role="search">
            <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Search code/name/phone..." />
          </form>

          @if($canCreate)
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
              <i class="mdi mdi-plus"></i> Add Klinik
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
              <th>Address</th>
              <th>Phone</th>
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
                <td class="fw-medium">{{ $row->code }}</td>
                <td>{{ $row->name }}</td>
                <td class="text-muted">{{ \Illuminate\Support\Str::limit($row->address, 70) }}</td>
                <td>
                  @if($row->phone)
                    <span class="fw-medium">{{ $row->phone }}</span>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
                <td>
                  @if($row->is_active)
                    <span class="badge bg-label-success rounded-pill">Active</span>
                  @else
                    <span class="badge bg-label-secondary rounded-pill">Inactive</span>
                  @endif
                </td>

                @if($canUpdate || $canDelete)
                  <td class="text-end">
                    <button class="btn btn-sm btn-icon btn-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#editModal-{{ $row->id }}"
                            title="Edit">
                      <i class="mdi mdi-pencil-outline"></i>
                    </button>
                  </td>
                @endif
              </tr>

              {{-- ===== EDIT MODAL (per row) ===== --}}
              @if($canUpdate || $canDelete)
                <div class="modal fade" id="editModal-{{ $row->id }}" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content shadow-lg rounded-3">
                      <form method="post" action="{{ route('master.clinics.update', $row->id) }}" id="updateForm-{{ $row->id }}">
                        @csrf
                        @method('PUT')

                        <div class="modal-header border-0">
                          <h5 class="modal-title fw-semibold">Edit Klinik</h5>
                          <button type="button" class="btn btn-icon btn-text-secondary" data-bs-dismiss="modal" aria-label="Close">
                            <i class="mdi mdi-close"></i>
                          </button>
                        </div>

                        <div class="modal-body pt-0">
                          <input type="hidden" name="_from" value="edit">
                          <input type="hidden" name="_edit_id" value="{{ $row->id }}">

                          <div class="mb-3">
                            <label class="form-label required">Code</label>
                            <input type="text" name="code"
                                   value="{{ old('_from') === 'edit' && old('_edit_id') == $row->id ? old('code') : $row->code }}"
                                   class="form-control" required>
                          </div>

                          <div class="mb-3">
                            <label class="form-label required">Name</label>
                            <input type="text" name="name"
                                   value="{{ old('_from') === 'edit' && old('_edit_id') == $row->id ? old('name') : $row->name }}"
                                   class="form-control" required>
                          </div>

                          <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" rows="3" class="form-control">{{ old('_from') === 'edit' && old('_edit_id') == $row->id ? old('address') : $row->address }}</textarea>
                          </div>

                          <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone"
                                   value="{{ old('_from') === 'edit' && old('_edit_id') == $row->id ? old('phone') : $row->phone }}"
                                   class="form-control" placeholder="e.g., +6281234567890">
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
                            <button type="submit"
                                    class="btn btn-outline-danger d-inline-flex align-items-center gap-2"
                                    form="deleteForm-{{ $row->id }}"
                                    onclick="return confirm('Delete this clinic?')">
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
                    </div>
                  </div>
                </div>

                @if($canDelete)
                  <form id="deleteForm-{{ $row->id }}" method="post" action="{{ route('master.clinics.destroy', $row->id) }}" class="d-none">
                    @csrf
                    @method('DELETE')
                  </form>
                @endif
              @endif
              {{-- ===== /EDIT MODAL ===== --}}
            @empty
              <tr>
                <td colspan="{{ ($canUpdate || $canDelete) ? 7 : 6 }}" class="text-center text-muted py-4">
                  No data available
                </td>
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
@if($canCreate)
  <div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content shadow-lg rounded-3">
        <form method="post" action="{{ route('master.clinics.store') }}">
          @csrf
          <div class="modal-header border-0">
            <h5 class="modal-title fw-semibold">Add Klinik</h5>
            <button type="button" class="btn btn-icon btn-text-secondary" data-bs-dismiss="modal" aria-label="Close">
              <i class="mdi mdi-close"></i>
            </button>
          </div>

          <div class="modal-body pt-0">
            <input type="hidden" name="_from" value="create">

            <div class="mb-3">
              <label class="form-label required">Code</label>
              <input type="text" name="code" value="{{ old('code') }}"
                     class="form-control @error('code') is-invalid @enderror"
                     required placeholder="e.g., KLINIK01">
              @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
              <label class="form-label required">Name</label>
              <input type="text" name="name" value="{{ old('name') }}"
                     class="form-control @error('name') is-invalid @enderror"
                     required placeholder="Nama Klinik">
              @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
              <label class="form-label">Address</label>
              <textarea name="address" rows="3"
                        class="form-control @error('address') is-invalid @enderror"
                        placeholder="Alamat klinik...">{{ old('address') }}</textarea>
              @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
              <label class="form-label">Phone</label>
              <input type="text" name="phone" value="{{ old('phone') }}"
                     class="form-control @error('phone') is-invalid @enderror"
                     placeholder="e.g., +6281234567890">
              @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
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

@section('page-script')
<script>
  @if($errors->any() && old('_from') === 'create')
    const createModalEl = document.getElementById('createModal');
    if (createModalEl) new bootstrap.Modal(createModalEl).show();
  @endif

  @if($errors->any() && old('_from') === 'edit' && old('_edit_id'))
    const editId = @json(old('_edit_id'));
    const el = document.getElementById('editModal-' + editId);
    if (el) new bootstrap.Modal(el).show();
  @endif
</script>
@endsection
