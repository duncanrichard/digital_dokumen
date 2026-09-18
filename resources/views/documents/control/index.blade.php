@extends('layouts/contentNavbarLayout')

@section('title', 'Pusat Document Control')

@section('content')
@include('documents._legal_workflow')

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
  <div>
    <h3 class="mb-1">Pusat Document Control</h3>
    <p class="text-muted mb-0">Satu halaman untuk membuat, mendistribusikan, dan memantau dokumen Legal.</p>
  </div>
  <a href="{{ route('documents.index') }}" class="btn btn-primary btn-lg">
    <i class="mdi mdi-file-plus-outline me-1"></i> Buat Dokumen
  </a>
</div>

<div class="row g-3 mb-4">
  @foreach([
    ['Dokumen aktif', $stats['active'], 'mdi-file-check-outline', 'primary'],
    ['Dapat saya lihat', $stats['visible'], 'mdi-eye-check-outline', 'success'],
    ['Sudah didistribusikan', $stats['distributed'], 'mdi-share-variant-outline', 'info'],
    ['Menunggu persetujuan', $stats['pending'], 'mdi-clock-alert-outline', 'warning'],
  ] as [$label, $value, $icon, $color])
    <div class="col-6 col-xl-3">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="avatar avatar-lg bg-label-{{ $color }} rounded"><i class="mdi {{ $icon }} mdi-28px"></i></div>
          <div><div class="text-muted small">{{ $label }}</div><h3 class="mb-0">{{ $value }}</h3></div>
        </div>
      </div>
    </div>
  @endforeach
</div>

<div class="row g-4">
  <div class="col-xl-8">
    <div class="card shadow-sm h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div><h5 class="mb-1">Dokumen terbaru</h5><small class="text-muted">Dokumen aktif yang tersedia untuk Anda</small></div>
        <a href="{{ route('documents.gallery.index') }}" class="btn btn-sm btn-outline-primary">Lihat semua</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead><tr><th>Nomor</th><th>Nama dokumen</th><th>Pemilik</th><th>Tanggal</th><th></th></tr></thead>
          <tbody>
          @forelse($recentDocuments as $document)
            <tr>
              <td><span class="badge bg-label-primary">{{ $document->document_number }} R{{ $document->revision ?? 0 }}</span></td>
              <td><strong>{{ $document->name }}</strong><div class="small text-muted">{{ $document->jenisDokumen->nama ?? '-' }}</div></td>
              <td>{{ $document->department->name ?? '-' }}</td>
              <td>{{ optional($document->publish_date)->format('d M Y') }}</td>
              <td><a class="btn btn-sm btn-icon btn-outline-primary" href="{{ route('documents.gallery.read', $document) }}"><i class="mdi mdi-eye-outline"></i></a></td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted py-5">Belum ada dokumen yang tersedia.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="card shadow-sm h-100">
      <div class="card-header"><h5 class="mb-1">Aksi cepat Legal</h5><small class="text-muted">Pilih pekerjaan yang ingin dilakukan</small></div>
      <div class="card-body d-grid gap-3">
        <a href="{{ route('documents.index') }}" class="btn btn-outline-primary text-start p-3"><i class="mdi mdi-upload-outline me-2"></i><strong>Unggah / kelola dokumen</strong></a>
        <a href="{{ route('documents.distribution.index') }}" class="btn btn-outline-info text-start p-3"><i class="mdi mdi-send-outline me-2"></i><strong>Atur distribusi penerima</strong></a>
        <a href="{{ route('documents.revisions.index') }}" class="btn btn-outline-warning text-start p-3"><i class="mdi mdi-file-replace-outline me-2"></i><strong>Buat revisi dokumen</strong></a>
        <a href="{{ route('documents.approvals.index') }}" class="btn btn-outline-success text-start p-3"><i class="mdi mdi-shield-check-outline me-2"></i><strong>Proses persetujuan akses</strong></a>
      </div>
    </div>
  </div>
</div>
@endsection
