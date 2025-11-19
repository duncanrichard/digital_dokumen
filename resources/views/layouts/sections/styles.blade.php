@php
  /**
   * Gunakan mix() jika public/mix-manifest.json ada dan entry-nya cocok,
   * kalau tidak ada/entry hilang -> fallback ke asset() agar tidak error.
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

<!-- BEGIN: Theme CSS-->
<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<!-- Vendor Fonts / Plugins -->
<link rel="stylesheet" href="{{ mix_or_asset('assets/vendor/fonts/materialdesignicons.css') }}" />
<link rel="stylesheet" href="{{ mix_or_asset('assets/vendor/libs/node-waves/node-waves.css') }}" />

<!-- Core CSS -->
<link rel="stylesheet" href="{{ mix_or_asset('assets/vendor/css/core.css') }}" />
<link rel="stylesheet" href="{{ mix_or_asset('assets/vendor/css/theme-default.css') }}" />
<link rel="stylesheet" href="{{ mix_or_asset('assets/css/demo.css') }}" />

<!-- Vendors CSS -->
<link rel="stylesheet" href="{{ mix_or_asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />

<!-- Vendor Styles (per halaman) -->
@yield('vendor-style')

<!-- Page Styles (per halaman) -->
@yield('page-style')
