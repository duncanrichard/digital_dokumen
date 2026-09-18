@extends('layouts/contentNavbarLayout')

@section('title', 'Document Access - Open')

@section('page-style')
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
  .pdf-reader { background:#eef2f7; border:1px solid #dbe3ee; border-radius:12px; overflow:hidden; }
  .pdf-toolbar { background:#243b53; padding:.75rem 1rem; display:flex; gap:.55rem; align-items:center; justify-content:center; color:#fff; position:sticky; top:0; z-index:2; }
  .pdf-toolbar button { min-width:38px; }
  #pdfPageLabel { min-width:115px; text-align:center; font-weight:600; font-size:.9rem; }
  .pdf-page-wrap { padding:1.5rem; overflow:auto; }
  #pdfCanvas { display:block; max-width:100%; height:auto; margin:auto; background:#fff; box-shadow:0 4px 18px rgba(36,59,83,.2); }
</style>
@endsection

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
            <h6 class="alert-heading mb-1">
              @if(($accessSource ?? '') === 'DISTRIBUTION')
                Akses dokumen tersedia karena dokumen sudah didistribusikan ke departemen Anda
              @elseif(($accessSource ?? '') === 'OWNER')
                Akses dokumen tersedia karena dokumen milik departemen Anda
              @else
                Akses dokumen sudah disetujui
              @endif
            </h6>

            {{-- ✅ Hanya approval/request yang menampilkan validUntil --}}
            @if(!empty($hasTimer) && !empty($validUntil))
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

        {{-- ✅ Timer hanya jika hasTimer true --}}
        @if(!empty($hasTimer) && !empty($remainingSeconds) && $remainingSeconds > 0)
          <div class="mb-4">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <span class="countdown-label">Sisa waktu akses</span>
              <span id="countdownDisplay" class="countdown-display text-primary"></span>
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
        <div class="pdf-reader">
          <div class="pdf-toolbar">
            <button id="pdfPrev" class="btn btn-sm btn-light">‹</button>
            <span id="pdfPageLabel">Memuat dokumen…</span>
            <button id="pdfNext" class="btn btn-sm btn-light">›</button>
            <button id="pdfZoomOut" class="btn btn-sm btn-outline-light">−</button>
            <button id="pdfZoomIn" class="btn btn-sm btn-outline-light">+</button>
          </div>
          <div class="pdf-page-wrap"><canvas id="pdfCanvas"></canvas></div>
        </div>

      </div>
    </div>

  </div>
</div>

{{-- Modal: Waktu akses habis (hanya berguna kalau hasTimer true) --}}
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
    const rawUrl = @json(route('documents.gallery.file.raw', $document->id));

    let pdfWindow = null;
    const script = document.createElement('script'); script.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';
    script.onload = () => { pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js'; initPdf(); }; document.head.appendChild(script);
    function initPdf() { let pdf, page = 1, scale = 1.65; const canvas = document.getElementById('pdfCanvas'), ctx = canvas.getContext('2d'), label = document.getElementById('pdfPageLabel');
      const render = () => pdf.getPage(page).then(p => { const v = p.getViewport({scale}); canvas.width=v.width; canvas.height=v.height; p.render({canvasContext:ctx,viewport:v}); label.textContent=`Halaman ${page} / ${pdf.numPages}`; });
      pdfjsLib.getDocument(rawUrl).promise.then(x => { pdf=x; render(); });
      document.getElementById('pdfPrev').onclick=()=>{if(page>1){page--;render();}}; document.getElementById('pdfNext').onclick=()=>{if(page<pdf.numPages){page++;render();}};
      document.getElementById('pdfZoomIn').onclick=()=>{scale=Math.min(2.5,scale+.15);render();}; document.getElementById('pdfZoomOut').onclick=()=>{scale=Math.max(.7,scale-.15);render();};
    }

    // ✅ kalau tidak pakai timer (distribution/owner) => stop di sini
    const hasTimer = @json((bool)($hasTimer ?? false));
    if (!hasTimer) return;

    let remaining = {{ (int)($remainingSeconds ?? 0) }};
    if (!remaining || remaining <= 0) return;

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

        try {
          if (pdfWindow && !pdfWindow.closed) {
            pdfWindow.close();
          }
        } catch (e) {}

        if (typeof bootstrap !== 'undefined' && modalEl) {
          const expiredModal = new bootstrap.Modal(modalEl, {
            backdrop: 'static',
            keyboard: false
          });
          expiredModal.show();

          if (btnExpiredOk) {
            btnExpiredOk.onclick = function () {
              expiredModal.hide();
            };
          }
        } else {
          alert('Waktu akses dokumen Anda sudah habis. Silakan ajukan permintaan akses lagi jika diperlukan.');
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
