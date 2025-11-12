@extends('layouts/contentNavbarLayout')

@section('title', 'Access Control - Users')

{{-- Select2 styles (Bootstrap 5 theme) --}}
@section('vendor-style')
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endsection

@push('styles')
<style>
  /* Style kustom untuk view ini */
  .table-responsive { overflow-x: auto; }
  .col-idx { width: 60px; text-align: center; }
  .col-aksi { width: 140px; text-align: center; }
  .form-label.required::before { content: '*'; color: #dc3545; font-weight: 600; margin-right: .35rem; }
  .status-badge { display: inline-block; min-width: 85px; text-align: center; font-weight: 600; }
  .table thead th {
    background-color: #f8f9fa; font-weight: 600; font-size: 0.85rem;
    text-transform: uppercase; letter-spacing: 0.3px; border-bottom: 2px solid #dee2e6;
  }
</style>
@endpush

@section('content')
<div class="row gy-4">
  <div class="col-12">

    {{-- Session Alerts --}}
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
    {{-- /Session Alerts --}}

    <div class="card shadow-sm">
      <div class="card-header bg-white border-bottom">
        <div class="d-flex flex-wrap align-items-center justify-content-between w-100 gap-3 py-2">
          <div>
            <h4 class="card-title mb-1">👤 User Management</h4>
            <p class="text-muted mb-0 small">Manage application users and departments</p>
          </div>

          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal" id="btnOpenCreate">
            <i class="mdi mdi-plus me-1"></i> Add User
          </button>
        </div>

        {{-- Filters --}}
        <form method="get" class="mt-4">
          <div class="row g-3 align-items-end">
            <div class="col-12 col-md-3">
              <label class="form-label small text-muted mb-1">Department</label>
              <select name="department_id" class="form-select select2" data-placeholder="All departments">
                <option value=""></option>
                @foreach($departments as $dep)
                  <option value="{{ $dep->id }}" {{ $filterDeptId === $dep->id ? 'selected' : '' }}>
                    {{ $dep->code }} - {{ $dep->name }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label small text-muted mb-1">Status</label>
              <select name="status" class="form-select">
                <option value="">All Status</option>
                <option value="1" {{ $filterStatus === '1' ? 'selected' : '' }}>Active</option>
                <option value="0" {{ $filterStatus === '0' ? 'selected' : '' }}>Inactive</option>
              </select>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label small text-muted mb-1">Search</label>
              <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control" placeholder="Search by name or username...">
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
              <button type="submit" class="btn btn-primary flex-fill">
                <i class="mdi mdi-magnify me-1"></i> Search
              </button>
              @if($filterDeptId || $filterStatus || $q)
                <a href="{{ route('access.users.index') }}" class="btn btn-outline-secondary" title="Clear filters">
                  <i class="mdi mdi-close"></i>
                </a>
              @endif
            </div>
          </div>
        </form>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th class="col-idx">#</th>
                <th>Name</th>
                <th>Username</th>
                <th>Department</th>
                <th class="text-center">Status</th>
                <th class="col-aksi">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($items as $row)
                <tr>
                  <td class="col-idx text-muted">{{ $items->firstItem() + $loop->index }}</td>
                  <td>
                    <div class="fw-semibold">{{ $row->name }}</div>
                  </td>
                  <td>
                    <div class="text-muted">{{ $row->username }}</div>
                  </td>
                  <td>
                    @if($row->department)
                      <span class="badge bg-light text-dark border">{{ $row->department->code }}</span>
                    @else
                      <span class="text-muted small">—</span>
                    @endif
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
                  <td class="col-aksi">
                    <div class="d-flex justify-content-center gap-2">
                      <button class="btn btn-sm btn-outline-primary btn-icon btn-edit-user"
                              title="Edit"
                              data-bs-toggle="modal"
                              data-bs-target="#editModal"
                              data-update-url="{{ route('access.users.update', $row->id) }}"
                              data-id="{{ $row->id }}"
                              data-name="{{ $row->name }}"
                              data-username="{{ $row->username }}"
                              data-department_id="{{ $row->department_id }}"
                              data-is_active="{{ $row->is_active ? 1 : 0 }}">
                        <i class="mdi mdi-pencil-outline"></i>
                      </button>
                      <button class="btn btn-sm btn-outline-danger btn-icon btn-delete-user"
                              title="Delete"
                              data-delete-url="{{ route('access.users.destroy', $row->id) }}">
                        <i class="mdi mdi-trash-can-outline"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6">
                    <div class="empty-state py-5 text-center">
                      <i class="mdi mdi-account-search-outline fs-1 text-muted"></i>
                      <h5 class="text-muted mt-3">No Users Found</h5>
                      <p class="text-muted small">Try adjusting your search filters.</p>
                    </div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      @if($items->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
          <div class="text-muted small">
            Showing {{ $items->firstItem() }} to {{ $items->lastItem() }} of {{ $items->total() }} users
          </div>
          {{ $items->links() }}
        </div>
      @endif
    </div>
  </div>
</div>

{{-- ================== CREATE MODAL ================== --}}
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow-lg rounded-3">
      <form method="post" action="{{ route('access.users.store') }}" id="formCreate">
        @csrf
        <input type="hidden" name="_from" value="create">

        <div class="modal-header border-0">
          <h5 class="modal-title fw-semibold" id="createModalLabel">Add New User</h5>
          <button type="button" class="btn btn-icon btn-text-secondary" data-bs-dismiss="modal" aria-label="Close">
            <i class="mdi mdi-close"></i>
          </button>
        </div>

        <div class="modal-body pt-0">
          @if ($errors->any() && old('_from')==='create')
            <div class="alert alert-danger">
              <i class="mdi mdi-alert-circle-outline me-1"></i> Failed to save. Please check:
              <ul class="mb-0 mt-2 ps-3">
                @foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach
              </ul>
            </div>
          @endif

          <div class="mb-3">
            <label class="form-label required">Full Name</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror"
                   name="name" id="create_name"
                   value="{{ old('_from')==='create' ? old('name') : '' }}"
                   placeholder="e.g. John Doe" required>
            @error('name') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label required">Username</label>
              <input type="text" class="form-control @error('username') is-invalid @enderror"
                     name="username" id="create_username"
                     value="{{ old('_from')==='create' ? old('username') : '' }}"
                     placeholder="e.g. johndoe" required>
              @error('username') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Department</label>
              <select class="form-select select2 @error('department_id') is-invalid @enderror"
                      name="department_id" id="create_department_id"
                      data-placeholder="Select Department">
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

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label required">Password</label>
              <input type="password" class="form-control @error('password') is-invalid @enderror"
                     name="password" required>
              @error('password') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label required">Confirm Password</label>
              <input type="password" class="form-control" name="password_confirmation" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label d-block">Status</label>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="create_is_active" name="is_active" value="1"
                     {{ old('_from')==='create' ? (old('is_active') ? 'checked' : '') : 'checked' }}>
              <label class="form-check-label" for="create_is_active">Active</label>
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

{{-- ================== EDIT MODAL ================== --}}
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content shadow-lg rounded-3">
      <form method="post" action="#" enctype="multipart/form-data" id="formEdit">
        @csrf @method('PUT')
        <input type="hidden" name="_from" value="edit">
        <input type="hidden" name="id" id="edit_id" value="{{ old('_from')==='edit' ? old('id') : '' }}">

        <div class="modal-header border-0">
          <h5 class="modal-title fw-semibold" id="editModalLabel">Edit User</h5>
          <button type="button" class="btn btn-icon btn-text-secondary" data-bs-dismiss="modal" aria-label="Close">
            <i class="mdi mdi-close"></i>
          </button>
        </div>

        <div class="modal-body pt-0">
          @if ($errors->any() && old('_from')==='edit')
            <div class="alert alert-danger">
              <i class="mdi mdi-alert-circle-outline me-1"></i> Failed to update. Please check:
              <ul class="mb-0 mt-2 ps-3">
                @foreach ($errors->all() as $err) <li>{{ $err }}</li> @endforeach
              </ul>
            </div>
          @endif

          <div class="mb-3">
            <label class="form-label required">Full Name</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror"
                   name="name" id="edit_name"
                   value="{{ old('_from')==='edit' ? old('name') : '' }}" required>
            @error('name') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label required">Username</label>
              <input type="text" class="form-control @error('username') is-invalid @enderror"
                     name="username" id="edit_username"
                     value="{{ old('_from')==='edit' ? old('username') : '' }}" required>
              @error('username') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Department</label>
              <select class="form-select select2 @error('department_id') is-invalid @enderror"
                      name="department_id" id="edit_department_id"
                      data-placeholder="Select Department">
                <option value=""></option>
                @foreach($departments as $dep)
                  <option value="{{ $dep->id }}" @selected(old('_from')==='edit' && old('department_id')===$dep->id)>
                    {{ $dep->code }} - {{ $dep->name }}
                  </option>
                @endforeach
              </select>
              @error('department_id') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">New Password</label>
              <input type="password" class="form-control @error('password') is-invalid @enderror"
                     name="password" placeholder="Leave empty to keep old password">
              @error('password') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Confirm New Password</label>
              <input type="password" class="form-control" name="password_confirmation">
            </div>
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
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="mdi mdi-content-save-outline"></i> Update
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Hidden Delete Form --}}
<form id="formDeleteUser" method="POST" class="d-none">
  @csrf @method('DELETE')
</form>
@endsection

{{-- Select2 scripts --}}
@section('vendor-script')
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endsection

@section('page-script')
<script>
  (function() {
    // Helper untuk inisialisasi Select2
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
          // Penting: Tentukan dropdown parent agar Select2 berfungsi di dalam modal
          dropdownParent: $el.closest('.modal').length ? $el.closest('.modal') : $el.closest('form')
        });
      });
    }

    $(document).ready(function () {
      initSelect2(); // Inisialisasi Select2 di filter utama

      // Auto-open CREATE modal jika ada error validasi
      @if ($errors->any() && old('_from')==='create')
        const cm = document.getElementById('createModal');
        if (cm) {
          const bsModal = new bootstrap.Modal(cm);
          bsModal.show();
          setTimeout(() => { initSelect2(cm); }, 150);
        }
      @endif

      // Auto-open EDIT modal jika ada error validasi
      @if ($errors->any() && old('_from')==='edit')
        const em = document.getElementById('editModal');
        if (em) {
          const bsModal = new bootstrap.Modal(em);
          bsModal.show();
          setTimeout(() => { initSelect2(em); }, 150);
        }
      @endif

      // Saat tombol Add User di-klik, reset form & select2
      $('#btnOpenCreate').on('click', function() {
        document.getElementById('formCreate').reset();
        $('#create_department_id').val('').trigger('change');
      });

      // Saat tombol EDIT di-klik, isi modal
      $(document).on('click', '.btn-edit-user', function(e) {
        e.preventDefault();
        const btn = $(this);
        const form = document.getElementById('formEdit');
        form.action = btn.data('update-url') || '#';

        // Helper untuk set value
        const setVal = (id, val) => { const el = document.getElementById(id); if (el) el.value = val ?? ''; };

        setVal('edit_id', btn.data('id'));
        setVal('edit_name', btn.data('name'));
        setVal('edit_username', btn.data('username'));

        // Set Select2
        $('#edit_department_id').val(btn.data('department_id') || '').trigger('change');

        // Set Checkbox
        document.getElementById('edit_is_active').checked = (btn.data('is_active') === 1 || btn.data('is_active') === '1');

        // Reset password fields
        setVal('password', '');
        setVal('password_confirmation', '');

        // Re-init select2 di dalam modal
        const modal = document.getElementById('editModal');
        setTimeout(() => { initSelect2(modal); }, 100);
      });

      // Saat tombol DELETE di-klik
      $(document).on('click', '.btn-delete-user', function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) return;
        
        const form = document.getElementById('formDeleteUser');
        form.action = $(this).data('delete-url') || '#';
        form.submit();
      });
    });
  })();
</script>
@endsection