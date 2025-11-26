@extends('layouts/contentNavbarLayout')

@section('title', 'User Access - Roles')

@push('styles')
<style>
  .col-aksi { width: 140px; text-align: center; }
  .col-idx { width: 60px; text-align: center; }
  .table thead th {
    background-color: #f8f9fa;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    border-bottom: 2px solid #dee2e6;
  }
</style>
@endpush

@section('content')
@php
  $me       = auth()->user();
  $role     = optional($me)->role;
  $roleName = $role->name ?? null;

  // Superadmin bebas
  $isSuperadmin = $roleName && strcasecmp($roleName, 'Superadmin') === 0;

  $canCreate = $isSuperadmin || ($role && $role->hasPermissionTo('access.roles.create'));
  $canUpdate = $isSuperadmin || ($role && $role->hasPermissionTo('access.roles.update'));
  $canDelete = $isSuperadmin || ($role && $role->hasPermissionTo('access.roles.delete'));

  $isEdit    = isset($editRole);
  $formTitle = $isEdit ? 'Edit Role' : 'Tambah Role';
@endphp

<div class="container-xxl flex-grow-1 container-p-y">

  <h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">User Access /</span> Roles
  </h4>

  {{-- Alert success --}}
  @if (session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  {{-- Alert error --}}
  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="row">

    {{-- ========== KOLOM KIRI: FORM ========== --}}
    <div class="col-lg-4 mb-4">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">{{ $formTitle }}</h5>
        </div>

        <div class="card-body">
          @if(($isEdit && $canUpdate) || (!$isEdit && $canCreate))
            <form action="{{ $isEdit
                              ? route('access.roles.update', $editRole->id)
                              : route('access.roles.store') }}"
                  method="POST">

              @csrf
              @if ($isEdit)
                @method('PUT')
              @endif

              <div class="mb-3">
                <label for="name" class="form-label">Nama Role</label>
                <input
                  type="text"
                  class="form-control"
                  id="name"
                  name="name"
                  value="{{ old('name', $isEdit ? $editRole->name : '') }}"
                  required
                >
              </div>

              <div class="mb-3">
                <label for="guard_name" class="form-label">Guard Name</label>
                <input
                  type="text"
                  class="form-control"
                  id="guard_name"
                  name="guard_name"
                  value="{{ old('guard_name', $isEdit ? $editRole->guard_name : 'web') }}"
                  readonly
                >
              </div>

              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                  {{ $isEdit ? 'Update' : 'Simpan' }}
                </button>

                @if ($isEdit)
                  <a href="{{ route('access.roles.index') }}" class="btn btn-outline-secondary">
                    Batal
                  </a>
                @endif
              </div>
            </form>
          @else
            <div class="alert alert-warning mb-0">
              Anda tidak memiliki izin untuk {{ $isEdit ? 'mengubah' : 'membuat' }} role.
            </div>
          @endif
        </div>
      </div>
    </div>

    {{-- ========== KOLOM KANAN: TABEL ========== --}}
    <div class="col-lg-8 mb-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Daftar Roles</h5>
        </div>

        <div class="table-responsive text-nowrap">
          <table class="table table-hover align-middle mb-0">
            <thead>
              <tr>
                <th class="col-idx">#</th>
                <th>Nama Role</th>
                <th>Guard Name</th>
                <th>Dibuat</th>
                <th>Diperbarui</th>
                <th class="col-aksi">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($roles as $index => $roleItem)
                <tr>
                  <td class="text-muted col-idx">{{ $roles->firstItem() + $index }}</td>
                  <td>{{ $roleItem->name }}</td>
                  <td class="text-muted">{{ $roleItem->guard_name }}</td>
                  <td class="text-muted small">{{ $roleItem->created_at?->format('d-m-Y H:i') }}</td>
                  <td class="text-muted small">{{ $roleItem->updated_at?->format('d-m-Y H:i') }}</td>
                  <td class="col-aksi">
                    <div class="d-flex justify-content-center gap-2">
                      @if($canUpdate)
                        {{-- Edit: kirim query ?edit= --}}
                        <a href="{{ route('access.roles.index', array_merge(request()->except('page','edit'), ['edit' => $roleItem->id])) }}"
                           class="btn btn-sm btn-outline-primary btn-icon"
                           title="Edit">
                          <i class="mdi mdi-pencil-outline"></i>
                        </a>
                      @endif

                      @if($canDelete && strcasecmp($roleItem->name, 'Superadmin') !== 0)
                        {{-- Delete (jangan izinkan hapus Superadmin) --}}
                        <form action="{{ route('access.roles.destroy', $roleItem->id) }}"
                              method="POST"
                              class="d-inline"
                              onsubmit="return confirm('Hapus role ini?');">
                          @csrf
                          @method('DELETE')
                          <button type="submit"
                                  class="btn btn-sm btn-outline-danger btn-icon"
                                  title="Hapus">
                            <i class="mdi mdi-trash-can-outline"></i>
                          </button>
                        </form>
                      @endif
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center">
                    Belum ada data role.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if ($roles->hasPages())
          <div class="card-footer">
            {{ $roles->links() }}
          </div>
        @endif
      </div>
    </div>

  </div> {{-- /.row --}}

</div>
@endsection
