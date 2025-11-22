@extends('layouts/contentNavbarLayout')

@section('title', 'Access Control - Users')

{{-- Select2 styles (Bootstrap 5 theme) --}}
@section('vendor-style')
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endsection

@push('styles')
<style>
<<<<<<< HEAD
  .table-responsive { overflow-x: auto; }
  .col-idx { width: 60px; text-align: center; }
  .col-aksi { width: 140px; text-align: center; }
  .form-label.required::before {
    content: '*';
    color: #dc3545;
    font-weight: 600;
    margin-right: .35rem;
  }
  .status-badge {
    display: inline-block;
    min-width: 85px;
    text-align: center;
    font-weight: 600;
  }
  .table thead th {
    background-color: #f8f9fa;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    border-bottom: 2px solid #dee2e6;
  }
  .badge-hris {
    font-size: 0.7rem;
    border-radius: 999px;
=======
  /* Style kustom untuk view ini */
  .table-responsive { overflow-x: auto; }
  .col-idx { width: 60px; text-align: center; }
  .col-aksi { width: 140px; text-align: center; }
  .form-label.required::before { content: '*'; color: #dc3545; font-weight: 600; margin-right: .35rem; }
  .status-badge { display: inline-block; min-width: 85px; text-align: center; font-weight: 600; }
  .table thead th {
    background-color: #f8f9fa; font-weight: 600; font-size: 0.85rem;
    text-transform: uppercase; letter-spacing: 0.3px; border-bottom: 2px solid #dee2e6;
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
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

<<<<<<< HEAD
    @if ($errors->any())
=======
    @if ($errors->any() && !old('_from'))
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
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
<<<<<<< HEAD
            <p class="text-muted mb-0 small">Manage application users, roles, and departments</p>
          </div>
        </div>

        {{-- Filters --}}
        <form method="get" class="mt-3">
          <div class="row g-3 align-items-end">
            <div class="col-12 col-md-3">
              <label class="form-label small text-muted mb-1">Department</label>
              <select name="department_id"
                      class="form-select select2"
                      data-placeholder="All departments">
=======
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
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
                <option value=""></option>
                @foreach($departments as $dep)
                  <option value="{{ $dep->id }}" {{ $filterDeptId === $dep->id ? 'selected' : '' }}>
                    {{ $dep->code }} - {{ $dep->name }}
                  </option>
                @endforeach
              </select>
            </div>
<<<<<<< HEAD

=======
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
            <div class="col-12 col-md-3">
              <label class="form-label small text-muted mb-1">Status</label>
              <select name="status" class="form-select">
                <option value="">All Status</option>
                <option value="1" {{ $filterStatus === '1' ? 'selected' : '' }}>Active</option>
                <option value="0" {{ $filterStatus === '0' ? 'selected' : '' }}>Inactive</option>
              </select>
            </div>
<<<<<<< HEAD

            <div class="col-12 col-md-4">
              <label class="form-label small text-muted mb-1">Search</label>
              <input type="text"
                     name="q"
                     value="{{ $q ?? '' }}"
                     class="form-control"
                     placeholder="Search by name, username or email...">
            </div>

=======
            <div class="col-12 col-md-4">
              <label class="form-label small text-muted mb-1">Search</label>
              <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control" placeholder="Search by name, username or email...">
            </div>
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
            <div class="col-12 col-md-2 d-flex gap-2">
              <button type="submit" class="btn btn-primary flex-fill">
                <i class="mdi mdi-magnify me-1"></i> Search
              </button>
              @if($filterDeptId || $filterStatus || $q)
<<<<<<< HEAD
                <a href="{{ route('access.users.index') }}"
                   class="btn btn-outline-secondary"
                   title="Clear filters">
=======
                <a href="{{ route('access.users.index') }}" class="btn btn-outline-secondary" title="Clear filters">
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
                  <i class="mdi mdi-close"></i>
                </a>
              @endif
            </div>
          </div>
        </form>
      </div>

<<<<<<< HEAD
      <div class="card-body">
        <div class="row g-4">

          {{-- ================= KOLOM KIRI: FORM CREATE / EDIT ================= --}}
          @php
            $isEdit     = isset($editUser);
            $formTitle  = $isEdit ? 'Edit User' : 'Add New User';
            $isHrisEdit = $isEdit && !empty($editUser->hris_employee_id ?? null);
            // password wajib hanya pada CREATE + non-HRIS
            $passwordRequired = !$isEdit && !$isHrisEdit;
          @endphp

          <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-header bg-transparent border-bottom">
                <h5 class="mb-0">{{ $formTitle }}</h5>
              </div>
              <div class="card-body">
                <form method="post"
                      action="{{ $isEdit
                                ? route('access.users.update', $editUser->id)
                                : route('access.users.store') }}">
                  @csrf
                  @if($isEdit)
                    @method('PUT')
                  @endif

                  {{-- HRIS EMPLOYEE (OPSIONAL) --}}
                  <div class="mb-3">
                    <label class="form-label">HRIS Employee (optional)</label>
                    <select name="hris_employee_id"
                            id="hris_employee_id"
                            class="form-select select2 @error('hris_employee_id') is-invalid @enderror"
                            data-placeholder="Select employee from HRIS"
                            @if($isHrisEdit) disabled @endif>
                      <option value=""></option>
                      @foreach($employees as $emp)
                        <option value="{{ $emp->id }}"
                                data-name="{{ $emp->name }}"
                                data-email="{{ $emp->email }}"
                                data-wa="{{ $emp->office_phone }}"
                          {{ (string) old('hris_employee_id', $isEdit ? $editUser->hris_employee_id : '') === (string) $emp->id ? 'selected' : '' }}>
                          {{ $emp->name }} @if($emp->email) ({{ $emp->email }}) @endif
                        </option>
                      @endforeach
                    </select>
                    {{-- kalau edit HRIS, kirim hidden agar value tetap terkirim walau select disabled --}}
                    @if($isHrisEdit)
                      <input type="hidden" name="hris_employee_id" value="{{ $editUser->hris_employee_id }}">
                    @endif

                    @error('hris_employee_id')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted d-block mt-1">
                      Jika dipilih: <strong>Name</strong>, <strong>Username</strong>, dan <strong>Email</strong> akan mengikuti data HRIS.
                    </small>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text"
                           class="form-control @error('name') is-invalid @enderror"
                           name="name"
                           id="field_name"
                           value="{{ old('name', $isEdit ? $editUser->name : '') }}"
                           placeholder="e.g. John Doe"
                           @if($isHrisEdit) readonly @endif>
                    @error('name')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label">Username</label>
                      <input type="text"
                             class="form-control @error('username') is-invalid @enderror"
                             name="username"
                             id="field_username"
                             value="{{ old('username', $isEdit ? $editUser->username : '') }}"
                             placeholder="e.g. johndoe"
                             @if($isHrisEdit) readonly @endif>
                      @error('username')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                      <label class="form-label">Email</label>
                      <input type="email"
                             class="form-control @error('email') is-invalid @enderror"
                             name="email"
                             id="field_email"
                             value="{{ old('email', $isEdit ? $editUser->email : '') }}"
                             placeholder="e.g. john@example.com"
                             @if($isHrisEdit) readonly @endif>
                      @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                      @enderror
                    </div>
                  </div>

                  {{-- NOMOR WA --}}
                  <div class="mb-3">
                    <label class="form-label">Nomor WhatsApp</label>
                    <input type="text"
                           class="form-control @error('nomor_wa') is-invalid @enderror"
                           name="nomor_wa"
                           id="field_nomor_wa"
                           value="{{ old('nomor_wa', $isEdit ? $editUser->nomor_wa : '') }}"
                           placeholder="e.g. 62812xxxxxxx">
                    @error('nomor_wa')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">Gunakan format angka saja, contoh: 62812xxxxxxx.</small>
                  </div>

                  <div class="mb-3">
                    <label class="form-label">Department</label>
                    <select class="form-select select2 @error('department_id') is-invalid @enderror"
                            name="department_id"
                            data-placeholder="Select Department">
                      <option value=""></option>
                      @foreach($departments as $dep)
                        <option value="{{ $dep->id }}"
                          {{ (string) old('department_id', $isEdit ? $editUser->department_id : '') === (string) $dep->id ? 'selected' : '' }}>
                          {{ $dep->code }} - {{ $dep->name }}
                        </option>
                      @endforeach
                    </select>
                    @error('department_id')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  {{-- ====== ROLE FIELD (boleh diubah walau HRIS) ====== --}}
                  <div class="mb-3">
                    <label class="form-label">Role</label>
                    <select class="form-select select2 @error('role_id') is-invalid @enderror"
                            name="role_id"
                            data-placeholder="Select Role">
                      <option value=""></option>
                      @foreach($roles as $role)
                        <option value="{{ $role->id }}"
                          {{ (string) old('role_id', $isEdit ? $editUser->role_id : '') === (string) $role->id ? 'selected' : '' }}>
                          {{ $role->name }}
                        </option>
                      @endforeach
                    </select>
                    @error('role_id')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>
                  {{-- ===================== --}}

                  {{-- PASSWORD: hanya untuk user NON-HRIS --}}
                  <div id="password_row_wrapper" class="{{ $isHrisEdit ? 'd-none' : '' }}">
                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label class="form-label {{ !$isEdit ? 'required' : '' }}">
                          {{ $isEdit ? 'New Password' : 'Password' }}
                        </label>
                        <input type="password"
                               class="form-control @error('password') is-invalid @enderror"
                               name="password"
                               id="password_field"
                               @if($passwordRequired) required @endif
                               placeholder="{{ $isEdit ? 'Leave empty to keep old password' : '' }}">
                        @error('password')
                          <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                      </div>

                      <div class="col-md-6 mb-3">
                        <label class="form-label {{ !$isEdit ? 'required' : '' }}">
                          {{ $isEdit ? 'Confirm New Password' : 'Confirm Password' }}
                        </label>
                        <input type="password"
                               class="form-control"
                               name="password_confirmation"
                               id="password_confirmation_field"
                               @if($passwordRequired) required @endif>
                      </div>
                    </div>
                  </div>

                  <div id="hris_password_info"
                       class="alert alert-info small mt-2 {{ $isHrisEdit ? '' : 'd-none' }}">
                    User ini terhubung ke HRIS. <br>
                    <strong>Password</strong> mengikuti password di sistem HRIS dan
                    <strong>tidak dapat diubah</strong> dari aplikasi ini.
                  </div>

                  <div class="mb-3 mt-2">
                    <label class="form-label d-block">Status</label>
                    <div class="form-check form-switch">
                      <input class="form-check-input"
                             type="checkbox"
                             id="is_active"
                             name="is_active"
                             value="1"
                             @checked(old(
                               'is_active',
                               $isEdit ? ($editUser->is_active ? 1 : 0) : 1
                             ))>
                      <label class="form-check-label" for="is_active">Active</label>
                    </div>
                  </div>

                  <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                      <i class="mdi mdi-content-save-outline me-1"></i>
                      {{ $isEdit ? 'Update' : 'Save' }}
                    </button>

                    @if($isEdit)
                      <a href="{{ route('access.users.index', request()->except('edit', 'page')) }}"
                         class="btn btn-outline-secondary">
                        Cancel
                      </a>
                    @else
                      <button type="reset" class="btn btn-outline-secondary">
                        Reset
                      </button>
                    @endif
                  </div>
                </form>
              </div>
            </div>
          </div>

          {{-- ================= KOLOM KANAN: TABEL USERS ================= --}}
          <div class="col-12 col-lg-8">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead>
                  <tr>
                    <th class="col-idx">#</th>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>WhatsApp</th>
                    <th>Department</th>
                    <th>Role</th>
                    <th class="text-center">Status</th>
                    <th class="col-aksi">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($items as $row)
                    <tr>
                      <td class="col-idx text-muted">{{ $items->firstItem() + $loop->index }}</td>
                      <td>
                        <div class="fw-semibold">
                          {{ $row->name }}
                          @if(!empty($row->hris_employee_id))
                            <span class="badge bg-info text-dark badge-hris ms-1">HRIS</span>
                          @endif
                        </div>
                      </td>
                      <td><div class="text-muted">{{ $row->username }}</div></td>
                      <td><div class="text-muted">{{ $row->email }}</div></td>
                      <td>
                        @if($row->nomor_wa)
                          <a href="https://wa.me/{{ $row->nomor_wa }}"
                             target="_blank"
                             class="text-decoration-none">
                            {{ $row->nomor_wa }}
                          </a>
                        @else
                          <span class="text-muted small">—</span>
                        @endif
                      </td>
                      <td>
                        @if($row->department)
                          <span class="badge bg-light text-dark border">{{ $row->department->code }}</span>
                        @else
                          <span class="text-muted small">—</span>
                        @endif
                      </td>
                      <td>
                        @if($row->role)
                          <span class="badge bg-primary text-white">{{ $row->role->name }}</span>
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
                          {{-- Edit: kirim ?edit= --}}
                          <a href="{{ route('access.users.index', array_merge(request()->except('page','edit'), ['edit' => $row->id])) }}"
                             class="btn btn-sm btn-outline-primary btn-icon"
                             title="Edit">
                            <i class="mdi mdi-pencil-outline"></i>
                          </a>

                          <form action="{{ route('access.users.destroy', $row->id) }}"
                                method="POST"
                                onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="btn btn-sm btn-outline-danger btn-icon"
                                    title="Delete">
                              <i class="mdi mdi-trash-can-outline"></i>
                            </button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="9">
                        <div class="empty-state py-5 text-center">
                          <i class="mdi mdi-account-search-outline fs-1 text-muted"></i>
                          <h5 class="text-muted mt-3">No Users Found</h5>
                          <p class="text-muted small">Try adjusting your search filters or add a new user.</p>
                        </div>
                      </td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>

            @if($items->hasPages())
              <div class="mt-3 d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                  Showing {{ $items->firstItem() }} to {{ $items->lastItem() }} of {{ $items->total() }} users
                </div>
                {{ $items->links() }}
              </div>
            @endif
          </div>

        </div> {{-- /.row --}}
      </div>
    </div>
  </div>
</div>
=======
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th class="col-idx">#</th>
                <th>Name</th>
                <th>Username</th>
                <th>Email</th>
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
                    <div class="text-muted">{{ $row->email }}</div>
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
                              data-email="{{ $row->email }}"
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
                  <td colspan="7">
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
              <label class="form-label required">Email</label>
              <input type="email" class="form-control @error('email') is-invalid @enderror"
                     name="email" id="create_email"
                     value="{{ old('_from')==='create' ? old('email') : '' }}"
                     placeholder="e.g. john@example.com" required>
              @error('email') @if(old('_from')==='create') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
          </div>

          <div class="row">
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
              <label class="form-label required">Email</label>
              <input type="email" class="form-control @error('email') is-invalid @enderror"
                     name="email" id="edit_email"
                     value="{{ old('_from')==='edit' ? old('email') : '' }}" required>
              @error('email') @if(old('_from')==='edit') <div class="invalid-feedback">{{ $message }}</div> @endif @enderror
            </div>
          </div>

          <div class="row">
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
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
@endsection

{{-- Select2 scripts --}}
@section('vendor-script')
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endsection

@section('page-script')
<script>
  (function() {
<<<<<<< HEAD
=======
    // Helper untuk inisialisasi Select2
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
    function initSelect2(scope) {
      const $scope = scope ? $(scope) : $(document);
      $scope.find('.select2').each(function () {
        const $el = $(this);
<<<<<<< HEAD
        if ($el.hasClass('select2-hidden-accessible')) {
=======
        if ($el.hasClass("select2-hidden-accessible")) {
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
          $el.select2('destroy');
        }
        $el.select2({
          theme: 'bootstrap-5',
<<<<<<< HEAD
          width: '100%',
          placeholder: $el.data('placeholder') || '',
          allowClear: true
=======
          width: $el.data('width') ? $el.data('width') : '100%',
          placeholder: $el.data('placeholder') || '',
          // Penting: Tentukan dropdown parent agar Select2 berfungsi di dalam modal
          dropdownParent: $el.closest('.modal').length ? $el.closest('.modal') : $el.closest('form')
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
        });
      });
    }

<<<<<<< HEAD
    function applyHrisLock(locked, isEdit) {
      const $name    = $('#field_name');
      const $user    = $('#field_username');
      const $email   = $('#field_email');
      const $pwdWrap = $('#password_row_wrapper');
      const $info    = $('#hris_password_info');
      const $pwd     = $('#password_field');
      const $pwdConf = $('#password_confirmation_field');

      if (locked) {
        $name.prop('readonly', true);
        $user.prop('readonly', true);
        $email.prop('readonly', true);

        // sembunyikan row password + hapus required
        $pwdWrap.addClass('d-none');
        $pwd.prop('required', false);
        $pwdConf.prop('required', false);

        $info.removeClass('d-none');
      } else {
        $name.prop('readonly', false);
        $user.prop('readonly', false);
        $email.prop('readonly', false);

        $pwdWrap.removeClass('d-none');

        // Kalau mode CREATE (bukan edit), password wajib
        if (!isEdit) {
          $pwd.prop('required', true);
          $pwdConf.prop('required', true);
        }

        $info.addClass('d-none');
      }
    }

    function bindHrisEmployeeAutoFill(isHrisEditBlade, isEditBlade) {
      const $select = $('#hris_employee_id');
      if (!$select.length) return;

      // kalau sedang edit user HRIS, langsung lock dari awal
      if (isHrisEditBlade) {
        applyHrisLock(true, isEditBlade);
      }

      // untuk create / edit non-HRIS, perubahan dropdown HRIS akan lock / unlock
      $select.on('change', function() {
        const selected = this.options[this.selectedIndex];
        if (!selected || !selected.value) {
          // tidak pilih HRIS -> unlock manual
          applyHrisLock(false, isEditBlade);
          return;
        }

        const name  = selected.getAttribute('data-name')  || '';
        const email = selected.getAttribute('data-email') || '';
        const wa    = selected.getAttribute('data-wa')    || '';

        if (name) {
          $('#field_name').val(name);
          $('#field_username').val(name); // username dari name HRIS
        }
        if (email) {
          $('#field_email').val(email);
        }
        if (wa) {
          $('#field_nomor_wa').val(wa);
        }

        // ketika pilih HRIS, lock field name/username/email & sembunyikan password input
        applyHrisLock(true, isEditBlade);
      });
    }

    $(document).ready(function () {
      const isEditBlade     = @json($isEdit ?? false);
      const isHrisEditBlade = @json($isHrisEdit ?? false);

      initSelect2();
      bindHrisEmployeeAutoFill(isHrisEditBlade, isEditBlade);
=======
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
        setVal('edit_email', btn.data('email'));

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
>>>>>>> 680225e2e19fe941c77cea205e063022e1bbb0c0
    });
  })();
</script>
@endsection
