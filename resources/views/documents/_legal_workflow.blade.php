@php
  $routeName = Route::currentRouteName();
  $steps = [
    ['documents.index', '1', 'Buat & Kelola', 'Unggah dokumen pusat, revisi, perubahan, atau turunan', 'mdi-file-document-edit-outline'],
    ['documents.distribution.index', '2', 'Pilih Penerima', 'Distribusikan ke divisi, cabang, atau orang', 'mdi-account-multiple-plus-outline'],
    ['documents.gallery.index', '3', 'Lihat Dokumen', 'Periksa dokumen yang sudah diterima pengguna', 'mdi-bookshelf'],
    ['documents.approvals.index', '4', 'Persetujuan', 'Proses permintaan akses khusus', 'mdi-shield-check-outline'],
  ];
@endphp

<div class="card border-0 shadow-sm mb-4" style="background:linear-gradient(135deg,#fff 0%,#f4f7ff 100%);">
  <div class="card-body p-3 p-lg-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
      <div>
        <div class="text-primary fw-bold small text-uppercase">Alur kerja Legal</div>
        <h5 class="mb-0">Kelola dokumen dari pusat sampai penerima</h5>
      </div>
      <span class="badge bg-label-primary rounded-pill">Dokumen terkendali</span>
    </div>
    <div class="row g-2">
      @foreach($steps as [$route, $number, $title, $description, $icon])
        @php $active = $routeName === $route || ($route === 'documents.index' && str_starts_with($routeName ?? '', 'documents.revisions')); @endphp
        <div class="col-12 col-md-6 col-xl-3">
          <a href="{{ route($route) }}" class="text-decoration-none">
            <div class="h-100 rounded-3 p-3 border {{ $active ? 'border-primary bg-primary text-white' : 'bg-white' }}">
              <div class="d-flex gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 {{ $active ? 'bg-white text-primary' : 'bg-label-primary' }}" style="width:38px;height:38px;">
                  <i class="mdi {{ $icon }}"></i>
                </div>
                <div>
                  <div class="fw-bold {{ $active ? 'text-white' : 'text-dark' }}">{{ $number }}. {{ $title }}</div>
                  <div class="small {{ $active ? 'text-white opacity-75' : 'text-muted' }}">{{ $description }}</div>
                </div>
              </div>
            </div>
          </a>
        </div>
      @endforeach
    </div>
  </div>
</div>
