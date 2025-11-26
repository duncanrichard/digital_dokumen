@extends('layouts/contentNavbarLayout')

@section('title', 'Akses Ditolak')

@section('content')
<style>
  .error-wrapper {
    min-height: calc(100vh - 160px);
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .error-card-icon {
    width: 80px;
    height: 80px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
  }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
  <div class="error-wrapper">
    <div class="card shadow-sm border-0" style="max-width: 600px; width: 100%;">
      <div class="card-body text-center p-4 p-md-5">

        <div class="error-card-icon bg-label-danger mb-3">
          <i class="mdi mdi-lock-outline"></i>
        </div>

        <h2 class="fw-bold mb-2">403 · Akses Ditolak</h2>

        @php
          $msg = $exception->getMessage() ?: 'Anda tidak memiliki izin untuk mengakses halaman ini.';
        @endphp

        <p class="text-muted mb-4">
          {{ $msg }}
        </p>

        <div class="d-flex flex-wrap justify-content-center gap-2">
          {{-- Kembali ke halaman sebelumnya --}}
         <!--  <a href="{{ url()->previous() }}"
             class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
            <i class="mdi mdi-arrow-left"></i>
            <span>Kembali</span>
          </a>
 -->
          {{-- Arahkan ke dashboard / home --}}
          <a href="{{ route('dashboard-analytics') }}"
             class="btn btn-primary d-inline-flex align-items-center gap-1">
            <i class="mdi mdi-view-dashboard-outline"></i>
            <span>Ke Dashboard</span>
          </a>
        </div>

        <hr class="my-4">

        <p class="text-muted small mb-0">
          Jika Anda merasa seharusnya memiliki akses ke halaman ini,
          silakan hubungi <strong>Administrator / Superadmin</strong> untuk mengatur permission role Anda.
        </p>
      </div>
    </div>
  </div>
</div>
@endsection
