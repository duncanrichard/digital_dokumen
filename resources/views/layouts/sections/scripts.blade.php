@php
  /**
   * Helper: pakai mix() kalau mix-manifest ada & valid,
   * fallback ke asset() kalau tidak ada atau entry hilang.
   */
  if (!function_exists('mix_or_asset')) {
    function mix_or_asset(string $path) {
      $path = ltrim($path, '/');
      $manifest = public_path('mix-manifest.json');
      if (function_exists('mix') && file_exists($manifest)) {
        try {
          return mix('/' . $path);
        } catch (\Throwable $e) {
          return asset($path);
        }
      }
      return asset($path);
    }
  }
@endphp

<!-- BEGIN: Vendor JS (urutan penting) -->
<script src="{{ mix_or_asset('assets/vendor/libs/jquery/jquery.js') }}"
        onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js'"></script>

<script src="{{ mix_or_asset('assets/vendor/libs/popper/popper.js') }}"
        onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js'"></script>

<script src="{{ mix_or_asset('assets/vendor/js/bootstrap.js') }}"
        onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js'"></script>

<script src="{{ mix_or_asset('assets/vendor/libs/node-waves/node-waves.js') }}"
        onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/node-waves@0.7.6/dist/waves.min.js'"></script>

<script src="{{ mix_or_asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"
        onerror="this.onerror=null;this.src='https://cdn.jsdelivr.net/npm/perfect-scrollbar@1.5.5/dist/perfect-scrollbar.min.js'"></script>

<script src="{{ mix_or_asset('assets/vendor/js/menu.js') }}"></script>
<!-- END: Vendor JS -->

<!-- Vendor scripts khusus halaman -->
@yield('vendor-script')

<!-- BEGIN: Theme JS -->
<script src="{{ mix_or_asset('assets/js/main.js') }}"></script>
<!-- END: Theme JS -->

<!-- Pricing Modal (opsional dari template) -->
@stack('pricing-script')

<!-- BEGIN: Page JS (khusus halaman) -->
@yield('page-script')
<!-- END: Page JS -->
