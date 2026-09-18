@php
  $containerNav   = $containerNav ?? 'container-fluid';
  $navbarDetached = ($navbarDetached ?? '');
  $notifCount     = $notifCount ?? 0;
  $notifItems     = $notifItems ?? collect();

  $user = auth()->user();
  $displayName = $user ? ($user->username ?? $user->name ?? 'User') : 'Guest';
  $displayRole = $user ? (optional($user->role)->name ?? 'User') : '';
@endphp

<!-- Navbar -->
@if(isset($navbarDetached) && $navbarDetached == 'navbar-detached')
<nav class="layout-navbar {{$containerNav}} navbar navbar-expand-xl {{$navbarDetached}} align-items-center bg-navbar-theme" id="layout-navbar">
@endif
@if(isset($navbarDetached) && $navbarDetached == '')
<nav class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
  <div class="{{$containerNav}}">
@endif

  {{-- Brand (desktop) --}}
  @if(isset($navbarFull))
    <div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-4">
      <a href="{{ url('/') }}" class="app-brand-link gap-2">
        <span class="app-brand-logo demo">
          @include('_partials.macros', ['height'=>20])
        </span>
        <span class="app-brand-text demo menu-text fw-semibold ms-1">
          {{ config('variables.templateName') }}
        </span>
      </a>
      <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
        <i class="mdi menu-toggle-icon d-xl-block align-middle mdi-20px"></i>
      </a>
    </div>
  @endif

  {{-- Toggle (hidden on some layouts) --}}
  @if(!isset($navbarHideToggle))
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0{{ isset($menuHorizontal) ? ' d-xl-none ' : '' }} {{ isset($contentNavbar) ? ' d-xl-none ' : '' }}">
      <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
        <i class="mdi mdi-menu mdi-24px"></i>
      </a>
    </div>
  @endif

  <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
    {{-- Search --}}
   <!--  <div class="navbar-nav align-items-center">
      <div class="nav-item d-flex align-items-center">
        <i class="mdi mdi-magnify mdi-24px lh-0"></i>
        <input type="text" class="form-control border-0 shadow-none bg-body" placeholder="Search..." aria-label="Search...">
      </div>
    </div> -->
    {{-- /Search --}}

    <ul class="navbar-nav flex-row align-items-center ms-auto">

      {{-- Notifications --}}
      <li class="nav-item dropdown me-3">
        <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="mdi mdi-bell-outline mdi-24px"></i>

            <span id="notificationBadge" class="badge bg-danger rounded-pill badge-notifications"
                  style="position:absolute; top:0; right:-2px; {{ $notifCount > 0 ? '' : 'display:none;' }}">
              {{ $notifCount }}
            </span>
        </a>

        <ul class="dropdown-menu dropdown-menu-end p-0" style="min-width: 360px;">
          <li class="px-3 py-2 border-bottom">
            <div class="d-flex align-items-center justify-content-between">
              <h6 class="mb-0">Notifikasi Dokumen</h6>

              @if($notifCount > 0)
                <form id="notificationReadAllForm" action="{{ route('notifications.readAll') }}" method="POST">
                  @csrf
                  <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none">
                    Tandai semua dibaca
                  </button>
                </form>
              @endif
            </div>
          </li>

          <div id="notificationItems">
          @forelse($notifItems as $n)
            <li>
              <a class="dropdown-item py-3 d-flex gap-3"
                 href="{{ route('documents.open', $n->id) }}"
                 target="_blank" rel="noopener">
                <span class="avatar avatar-sm flex-shrink-0 bg-label-primary d-flex align-items-center justify-content-center rounded-circle">
                  <i class="mdi mdi-file-outline"></i>
                </span>
                <div class="flex-grow-1">
                  <div class="fw-semibold text-wrap">
                    {{ $n->name }}
                  </div>
                  <small class="text-muted">
                    {{ $n->document_number }} R{{ $n->revision }} •
                    {{ optional($n->created_at)->diffForHumans() }}
                  </small>
                </div>
              </a>
            </li>
          @empty
            <li>
              <div class="dropdown-item py-3 text-center text-muted">
                Tidak ada notifikasi baru
              </div>
            </li>
          @endforelse
          </div>

          <li><hr class="dropdown-divider my-0"></li>
          <li>
            <a class="dropdown-item text-center py-2" href="{{ route('documents.index') }}">
              Lihat semua dokumen
            </a>
          </li>
        </ul>
      </li>
      {{-- /Notifications --}}

      {{-- User --}}
      <li class="nav-item navbar-dropdown dropdown-user dropdown">
        <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
          <div class="avatar avatar-online">
            <img src="{{ asset('assets/img/avatars/1.png') }}" alt="avatar" class="w-px-40 h-auto rounded-circle">
          </div>
        </a>

        <ul class="dropdown-menu dropdown-menu-end mt-3 py-2">
          <li>
            <a class="dropdown-item pb-2 mb-1" href="javascript:void(0);">
              <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-2 pe-1">
                  <div class="avatar avatar-online">
                    <img src="{{ asset('assets/img/avatars/1.png') }}" alt="avatar" class="w-px-40 h-auto rounded-circle">
                  </div>
                </div>

                <div class="flex-grow-1">
                  {{-- ✅ Nama sesuai user login --}}
                  <h6 class="mb-0">{{ $displayName }}</h6>

                  {{-- ✅ Role sesuai user login --}}
                  @if($displayRole !== '')
                    <small class="text-muted">{{ $displayRole }}</small>
                  @endif
                </div>
              </div>
            </a>
          </li>

          <li><div class="dropdown-divider my-1"></div></li>

          {{-- Profile --}}
         <!--  <li>
            <a class="dropdown-item" href="javascript:void(0);">
              <i class="mdi mdi-account-outline me-1 mdi-20px"></i>
              <span class="align-middle">My Profile</span>
            </a>
          </li> -->

          <li><div class="dropdown-divider my-1"></div></li>

          {{-- Logout --}}
          <li>
            <a class="dropdown-item" href="{{ route('logout') }}"
              onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
              <i class="mdi mdi-power me-1 mdi-20px"></i>
              <span class="align-middle">Log Out</span>
            </a>

            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
              @csrf
            </form>
          </li>
        </ul>
      </li>
      {{-- /User --}}

    </ul>
  </div>

  @if(!isset($navbarDetached))
</div>
@endif
</nav>
<!-- / Navbar -->

@auth
<script>
(() => {
  window.documentRealtimeConfig = {
    userId: @json((string) auth()->id()),
    key: @json(config('broadcasting.connections.reverb.key')),
    host: @json(config('broadcasting.connections.reverb.options.host', '127.0.0.1')),
    port: @json(config('broadcasting.connections.reverb.options.port', 8080)),
    scheme: @json(config('broadcasting.connections.reverb.options.scheme', 'http')),
    authEndpoint: @json(url('/broadcasting/auth'))
  };
  window.documentRealtimeConnected = false;
  const feedUrl = @json(route('notifications.feed'));
  const badge = document.getElementById('notificationBadge');
  const list = document.getElementById('notificationItems');
  const readAllForm = document.getElementById('notificationReadAllForm');

  function renderNotifications(data) {
    if (!badge || !list) return;
    const count = Number(data.count || 0);
    badge.textContent = count > 99 ? '99+' : count;
    badge.style.display = count > 0 ? '' : 'none';
    list.replaceChildren();

    if (!data.items || data.items.length === 0) {
      const li = document.createElement('li');
      li.innerHTML = '<div class="dropdown-item py-3 text-center text-muted"><i class="mdi mdi-check-circle-outline me-1"></i>Semua sudah dibaca</div>';
      list.appendChild(li);
      if (readAllForm) readAllForm.style.display = 'none';
      return;
    }

    if (readAllForm) readAllForm.style.display = '';
    data.items.forEach(item => {
      const li = document.createElement('li');
      const link = document.createElement('a');
      link.className = 'dropdown-item py-3 d-flex gap-3';
      link.href = item.url;

      const icon = document.createElement('span');
      icon.className = 'avatar avatar-sm flex-shrink-0 bg-label-primary d-flex align-items-center justify-content-center rounded-circle';
      icon.innerHTML = '<i class="mdi mdi-file-outline"></i>';

      const body = document.createElement('div');
      body.className = 'flex-grow-1';
      const title = document.createElement('div');
      title.className = 'fw-semibold text-wrap';
      title.textContent = item.name;
      const meta = document.createElement('small');
      meta.className = 'text-muted';
      meta.textContent = item.number + ' • ' + item.time;
      body.append(title, meta);
      link.append(icon, body);
      li.appendChild(link);
      list.appendChild(li);
    });
  }

  async function refreshNotifications() {
    if (document.hidden) return;
    try {
      const response = await fetch(feedUrl, {headers:{'Accept':'application/json'}, credentials:'same-origin'});
      if (response.ok) renderNotifications(await response.json());
    } catch (_) {}
  }

  window.refreshDocumentNotifications = refreshNotifications;

  window.addEventListener('load', refreshNotifications);
  document.addEventListener('visibilitychange', refreshNotifications);
  // Fallback ringan hanya saat WebSocket sedang terputus.
  window.setInterval(() => {
    if (!window.documentRealtimeConnected) refreshNotifications();
  }, 30000);
})();
</script>
@endauth
