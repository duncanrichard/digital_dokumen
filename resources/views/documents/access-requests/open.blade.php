@extends('layouts/contentNavbarLayout')

@section('title', 'Document Access - Open')

@push('styles')
<style>
  .countdown-display {
    font-size: 1.25rem;
    font-weight: 600;
    letter-spacing: 0.08em;
  }
  .countdown-label {
    font-size: 0.8rem;
    text-transform: uppercase;
    color: #6c757d;
  }
</style>
@endpush

@section('content')
<div class="row gy-4">
  <div class="col-12">

    <div class="card shadow-sm">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
          <h4 class="card-title mb-1">
            <i class="mdi mdi-file-document-outline me-1"></i>
            {{ $document->document_number }} (R{{ $document->revision }})
          </h4>
          <p class="text-muted small mb-0">
            {{ $document->name }}
          </p>
        </div>
      </div>

      <div class="card-body">

        {{-- Info akses --}}
        <div class="alert alert-success d-flex align-items-start" role="alert">
          <div class="me-2">
            <i class="mdi mdi-check-circle-outline mdi-24px"></i>
          </div>
          <div>
            <h6 class="alert-heading mb-1">Akses dokumen sudah disetujui</h6>
            @if(!empty($validUntil))
              <p class="mb-0 small">
                Anda dapat mengakses dokumen ini hingga:
                <strong>{{ $validUntil->format('d M Y H:i') }}</strong>.
              </p>
            @else
              <p class="mb-0 small">
                Tidak ada batas waktu khusus yang diterapkan untuk akses dokumen ini.
              </p>
            @endif
          </div>
        </div>

        {{-- Timer (jika ada durasi) --}}
        @if(!empty($remainingSeconds) && $remainingSeconds > 0)
          <div class="mb-4">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <span class="countdown-label">Sisa waktu akses</span>
              <span id="countdownDisplay" class="countdown-display text-primary">
                {{-- akan diisi JS --}}
              </span>
            </div>
            <div class="progress" style="height: 8px;">
              <div id="countdownProgress" class="progress-bar" role="progressbar"
                   style="width: 100%;" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <p class="text-muted small mt-2 mb-0">
              Setelah waktu habis, Anda perlu mengajukan permintaan akses lagi untuk melihat dokumen ini.
            </p>
          </div>
        @endif

        {{-- Info tab baru --}}
        <div class="mb-3">
          <p class="mb-1">
            Jendela baru yang berisi PDF sudah dibuka (jika tidak muncul, periksa pop-up blocker browser Anda).
          </p>
          <a href="{{ route('documents.file.raw', $document->id) }}" target="_blank" class="btn btn-primary">
            <i class="mdi mdi-open-in-new me-1"></i> Buka PDF di Tab Baru
          </a>
        </div>

      </div>
    </div>

  </div>
</div>

{{-- Modal: Waktu akses habis --}}
<div class="modal fade" id="accessExpiredModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">
          <i class="mdi mdi-timer-off me-1"></i>
          Waktu Akses Habis
        </h5>
      </div>
      <div class="modal-body">
        <p class="mb-0">
          Waktu akses dokumen Anda sudah habis.
          <br>
          Silakan ajukan permintaan akses lagi jika diperlukan.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" id="btnExpiredOk" class="btn btn-danger w-100">
          OK
        </button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const rawUrl = @json(route('documents.file.raw', $document->id));

    // Buka PDF di tab baru (kalau browser mengizinkan) dan simpan handlenya
    let pdfWindow = window.open(rawUrl, '_blank');

    // Kalau tidak ada timer (akses tanpa batas waktu), tidak perlu JS lanjutan
    @if(empty($remainingSeconds) || $remainingSeconds <= 0)
      return;
    @endif

    let remaining = {{ (int) $remainingSeconds }};
    const total = remaining;

    const displayEl    = document.getElementById('countdownDisplay');
    const progressEl   = document.getElementById('countdownProgress');
    const modalEl      = document.getElementById('accessExpiredModal');
    const btnExpiredOk = document.getElementById('btnExpiredOk');

    function formatTime(sec) {
      const m = Math.floor(sec / 60);
      const s = sec % 60;
      return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    }

    function tick() {
      if (!displayEl || !progressEl) return;

      displayEl.textContent = formatTime(remaining);
      const percent = total > 0 ? (remaining / total) * 100 : 0;
      progressEl.style.width = percent + '%';

      if (remaining <= 0) {
        displayEl.textContent = '00:00';

        // Tutup tab PDF kalau masih terbuka dan browser mengizinkan
        try {
          if (pdfWindow && !pdfWindow.closed) {
            pdfWindow.close();
          }
        } catch (e) {
          // abaikan error (misal cross-origin / browser block)
        }

        // Tampilkan modal Bootstrap, lalu kembali ke Library Dokumen
        if (typeof bootstrap !== 'undefined' && modalEl) {
          const expiredModal = new bootstrap.Modal(modalEl);
          expiredModal.show();

          if (btnExpiredOk) {
            btnExpiredOk.onclick = function () {
              window.location.href = @json(route('documents.index'));
            };
          }
        } else {
          // Fallback kalau bootstrap JS tidak tersedia
          window.location.href = @json(route('documents.index'));
        }

        return;
      }

      remaining--;
      setTimeout(tick, 1000);
    }

    tick();
  });
</script>
@endsection
