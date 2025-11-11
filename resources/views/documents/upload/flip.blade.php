@extends('layouts/contentNavbarLayout')

@section('title', 'Flip View - '.$document->name)

@push('styles')
  {{-- 3D FlipBook CSS via CDN --}}
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/3dflipbook@1.7.7/dist/css/flipbook.min.css">
  <style>
    .flip-wrapper { max-width: 1200px; margin: 24px auto; padding: 0 12px; }
    .flip-toolbar { display:flex; justify-content:space-between; align-items:center; gap:.75rem; margin-bottom:.75rem; }
    #flipbook {
      width: 100%; height: 80vh; min-height: 560px;
      background: #f5f7fb; border-radius: 12px; box-shadow: 0 6px 22px rgba(0,0,0,.08);
      position: relative;
    }
    .flip-error {
      position:absolute; inset:0; display:flex; align-items:center; justify-content:center;
      padding: 1rem; color:#6b7280; font-size:.95rem; text-align:center;
    }
  </style>
@endpush

@section('content')
<div class="flip-wrapper container-fluid">
  <div class="flip-toolbar">
    <div>
      <h4 class="mb-0">{{ $document->document_number ?? 'Document' }}</h4>
      <small class="text-muted">{{ $document->name }}</small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ $fileUrl }}" class="btn btn-outline-secondary" download>
        <i class="mdi mdi-download"></i> Download PDF
      </a>
      <a href="{{ route('documents.index') }}" class="btn btn-light">
        <i class="mdi mdi-arrow-left"></i> Back
      </a>
    </div>
  </div>

  <div id="flipbook" aria-label="Flipbook Viewer">
    <div class="flip-error" id="flipStatus" style="display:none;"></div>
  </div>
</div>
@endsection

@push('scripts')
  {{-- jQuery diperlukan oleh plugin UMD 3D FlipBook --}}
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
  <script>window.$ = window.jQuery = window.jQuery || window.$ || jQuery;</script>

  {{-- PDF.js (CDN) --}}
  <script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js"></script>
  <script>
    // pastikan worker tersedia; beberapa CSP butuh workerSrc eksplisit
    if (window.pdfjsLib) {
      pdfjsLib.GlobalWorkerOptions.workerSrc =
        'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js';
    }
  </script>

  {{-- 3D FlipBook (UMD) --}}
  <script src="https://cdn.jsdelivr.net/npm/3dflipbook@1.7.7/dist/js/flipbook.min.js"></script>

  <script>
    (function () {
      const container = document.getElementById('flipbook');
      const statusBox = document.getElementById('flipStatus');
      const fileUrl = @json($fileUrl);

      function showStatus(msg, fallbackIframe) {
        if (statusBox) {
          statusBox.style.display = 'flex';
          statusBox.innerHTML = msg;
        }
        if (fallbackIframe) {
          container.innerHTML =
            `<iframe src="${fileUrl}" style="width:100%;height:100%;border:0;" title="PDF Preview" allow="fullscreen"></iframe>`;
        }
      }

      // Guard: mixed content (https vs http)
      try {
        const a = document.createElement('a'); a.href = fileUrl;
        if (location.protocol === 'https:' && a.protocol === 'http:') {
          showStatus('Blocked mixed content: file PDF menggunakan http sementara situs https. Pastikan APP_URL & storage URL menggunakan https.', true);
          return;
        }
      } catch (_) {}

      // Guard: plugin tersedia?
      if (!window.jQuery || !jQuery.fn || !jQuery.fn.FlipBook) {
        showStatus('Flip plugin tidak terdeteksi. Pastikan 3D FlipBook dari CDN termuat dan tidak diblokir CSP.', true);
        return;
      }

      // Timeout guard: jika plugin hang (mis. worker diblokir CSP)
      let inited = false;
      const initTimeout = setTimeout(() => {
        if (!inited) {
          showStatus('Gagal memuat flipbook (kemungkinan PDF worker diblokir oleh CSP). Menggunakan fallback iframe.', true);
        }
      }, 4000);

      try {
        const options = {
          pdf: fileUrl,
          propertiesCallback: function (props) {
            props.page.depth = 2;
            props.cover.padding = 0.002;
            props.cssLayersLoader = 'all';
            return props;
          },
          controlsProps: {
            auto1: true,
            downloadURL: fileUrl,
            hideShare: true
          }
        };

        const book = jQuery(container).FlipBook(options);
        inited = true; clearTimeout(initTimeout);
        // sembunyikan status kalau ada
        if (statusBox) statusBox.style.display = 'none';

        // responsif
        window.addEventListener('resize', () => {
          try { book.update(); } catch(e) {}
        });
      } catch (err) {
        inited = true; clearTimeout(initTimeout);
        console.error('Flip viewer error:', err);
        showStatus('Terjadi error saat membuat flipbook. Menampilkan fallback iframe.', true);
      }
    })();
  </script>
@endpush
