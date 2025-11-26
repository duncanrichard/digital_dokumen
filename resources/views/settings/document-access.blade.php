@extends('layouts/contentNavbarLayout')

@section('title', 'Settings - Document Access')

@section('content')
@php
  $me       = auth()->user();
  $role     = optional($me)->role;
  $roleName = $role->name ?? null;

  $isSuperadmin = $roleName && strcasecmp($roleName, 'Superadmin') === 0;

  // permission sama pola dengan halaman lain
  $canUpdate = $isSuperadmin || ($role && $role->hasPermissionTo('settings.document-access.update'));
@endphp

<div class="container-xxl flex-grow-1 container-p-y">

  <h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">Settings /</span> Document Access
  </h4>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger alert-dismissible" role="alert">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @unless($canUpdate)
    <div class="alert alert-warning mb-3">
      Anda tidak memiliki izin untuk mengubah pengaturan ini. Form dalam mode read-only.
    </div>
  @endunless

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div>
        <h5 class="mb-0">Document Access Expiry</h5>
        <small class="text-muted">
          Atur batas waktu akses dokumen untuk user non-Superadmin setelah request disetujui.
        </small>
      </div>
      <span class="badge {{ $canUpdate ? 'bg-label-success' : 'bg-label-secondary' }}">
        {{ $canUpdate ? 'Editable' : 'Read-only' }}
      </span>
    </div>

    <div class="card-body">
      <form action="{{ route('settings.document-access.update') }}" method="POST">
        @csrf

        {{-- Semua kontrol di-wrap dalam fieldset supaya gampang di-disable --}}
        <fieldset {{ $canUpdate ? '' : 'disabled' }}>
          <div class="mb-3 form-check form-switch">
            <input type="checkbox"
                   class="form-check-input"
                   id="enabled"
                   name="enabled"
                   value="1"
                   {{ old('enabled', $setting->enabled ?? false) ? 'checked' : '' }}>
            <label class="form-check-label" for="enabled">
              Aktifkan batas waktu akses dokumen
            </label>
          </div>

          <div class="mb-3 col-md-4">
            <label class="form-label">Default Duration (minutes)</label>
            <input type="number"
                   min="1"
                   class="form-control @error('default_duration_minutes') is-invalid @enderror"
                   name="default_duration_minutes"
                   value="{{ old('default_duration_minutes', $setting->default_duration_minutes ?? 1440) }}">
            @error('default_duration_minutes')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <small class="text-muted">
              Contoh: 60 = 1 jam, 1440 = 1 hari. Akan dipakai jika Superadmin tidak mengisi tanggal kadaluarsa saat approve.
            </small>
          </div>
        </fieldset>

        @if($canUpdate)
          <button type="submit" class="btn btn-primary">
            <i class="mdi mdi-content-save-outline me-1"></i> Save
          </button>
        @endif
      </form>
    </div>
  </div>
</div>
@endsection
