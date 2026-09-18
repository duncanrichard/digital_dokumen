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

<style>
  :root {
    --app-navy: #243b53;
    --app-indigo: #4f46e5;
    --app-bg: #f4f7fb;
    --app-muted: #52606d;
  }
  body { background: var(--app-bg); color: var(--app-navy); }
  .layout-menu.bg-menu-theme { background: var(--app-navy) !important; color: #fff; }
  .layout-menu.bg-menu-theme .app-brand-text,
  .layout-menu.bg-menu-theme .menu-link,
  .layout-menu.bg-menu-theme .menu-header-text,
  .layout-menu.bg-menu-theme .menu-icon,
  .layout-menu.bg-menu-theme .menu-toggle::after { color: #fff !important; }
  .layout-menu.bg-menu-theme .menu-link:hover,
  .layout-menu.bg-menu-theme .menu-link:focus { color: #fff !important; background: rgba(255,255,255,.10); }
  .layout-menu.bg-menu-theme .menu-item.active > .menu-link:not(.menu-toggle) {
    background: var(--app-indigo) !important;
    color: #fff !important;
    border-radius: .5rem;
    margin-inline: .5rem;
  }
  .layout-menu.bg-menu-theme .menu-item.open > .menu-toggle { background: rgba(255,255,255,.08); border-radius: .5rem; }
  .card { border-color: #e3eaf3; box-shadow: 0 .25rem 1rem rgba(36,59,83,.06); }
  .btn-primary, .bg-primary { background-color: var(--app-indigo) !important; border-color: var(--app-indigo) !important; }
  .text-primary { color: var(--app-indigo) !important; }
  .form-control:focus, .form-select:focus { border-color: var(--app-indigo); box-shadow: 0 0 0 .15rem rgba(79,70,229,.15); }
  .badge.bg-success { background-color: #0f766e !important; }
  .badge.bg-warning { background-color: #d97706 !important; }
  .badge.bg-danger { background-color: #dc2626 !important; }
</style>

<!-- Vendor Styles (per halaman) -->
@yield('vendor-style')

<!-- Page Styles (per halaman) -->
@yield('page-style')
