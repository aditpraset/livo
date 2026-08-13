<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>@yield('title', 'Dashboard Siswa - LIVO')</title>

    <!-- CSS files -->
    <link href="{{ asset('assets/css/tabler.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/tabler-vendors.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/tabler-themes.min.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/css/admin-style.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

    <style>
      @import url("https://rsms.me/inter/inter.css");
      :root {
        --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
        --livo-siswa-bg: #0D1B2A;
        --livo-siswa-accent: #00C2FF;
      }
      body { font-feature-settings: "cv03", "cv04", "cv11"; }
      .badge { color: white; }

      /* Sidebar siswa: gelap dengan aksen cyan — sengaja dibedakan dari
         sidebar admin & tutor yang berlatar putih. */
      .sidebar-siswa { background: var(--livo-siswa-bg); border: 0; }
      .sidebar-siswa .navbar-brand { color: #fff; }
      .sidebar-siswa .nav-link { color: rgba(255,255,255,.72); border-radius: 8px; margin: 2px 8px; }
      .sidebar-siswa .nav-link:hover { color: #fff; background: rgba(255,255,255,.08); }
      .sidebar-siswa .nav-item.active > .nav-link { color: #fff; background: rgba(0,194,255,.18); box-shadow: inset 3px 0 0 var(--livo-siswa-accent); }
      .sidebar-siswa .navbar-toggler-icon { filter: invert(1); }
      .sidebar-siswa .sidebar-label { color: rgba(255,255,255,.38); font-size: 11px; letter-spacing: .08em; text-transform: uppercase; padding: 12px 16px 4px; }

      .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 0; }
      .dataTables_wrapper .dataTables_filter input { border: 1px solid #ddd; border-radius: 4px; padding: 4px 8px; }
    </style>
    @stack('css')
  </head>
  <body class="layout-fluid">
    {{-- $siswaMode & $siswaModeLabel dibagikan oleh middleware EnsureSiswaModeSelected --}}
    <script src="{{ asset('assets/js/tabler-theme.min.js') }}"></script>
    <div class="page">
      <!-- Sidebar Siswa -->
      <aside class="navbar navbar-vertical navbar-expand-lg sidebar-siswa">
        <div class="container-fluid">
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu"
            aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle sidebar navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="navbar-brand">
            <img src="{{ asset('frontend/images/logo.jpeg') }}" alt="Livo" class="navbar-brand-image" style="height: 32px" />
          </div>

          <div class="collapse navbar-collapse" id="sidebar-menu">
            <div class="sidebar-label d-none d-lg-block">Menu {{ $siswaModeLabel }}</div>
            <ul class="navbar-nav pt-lg-1">
              <li class="nav-item {{ request()->routeIs('siswa.dashboard') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('siswa.dashboard') }}">
                  <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="bi bi-house-door fs-2"></i></span>
                  <span class="nav-link-title"> Dashboard </span>
                </a>
              </li>
              <li class="nav-item {{ request()->routeIs('siswa.schedules*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('siswa.schedules.week') }}">
                  <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="bi bi-calendar3 fs-2"></i></span>
                  <span class="nav-link-title"> Jadwal Belajar </span>
                </a>
              </li>
              <li class="nav-item {{ request()->routeIs('siswa.evaluations*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('siswa.evaluations.index') }}">
                  <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="bi bi-bar-chart-line fs-2"></i></span>
                  <span class="nav-link-title"> Nilai &amp; Evaluasi </span>
                </a>
              </li>
              <li class="nav-item {{ request()->routeIs('siswa.payments*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('siswa.payments.index') }}">
                  <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="bi bi-receipt fs-2"></i></span>
                  <span class="nav-link-title"> Pembayaran </span>
                </a>
              </li>
              <li class="nav-item {{ request()->routeIs('siswa.profile*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('siswa.profile') }}">
                  <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="bi bi-person-badge fs-2"></i></span>
                  <span class="nav-link-title"> Profil Saya </span>
                </a>
              </li>
            </ul>
          </div>
        </div>
      </aside>

      <div class="page-wrapper">
        <!-- Header -->
        <header class="navbar navbar-expand-md d-none d-lg-flex d-print-none">
          <div class="container-xl">
            {{-- Penanda mode yang sedang aktif --}}
            <span class="badge {{ $siswaMode === 'orang_tua' ? 'bg-success' : 'bg-info' }}">
              <i class="bi {{ $siswaMode === 'orang_tua' ? 'bi-people' : 'bi-backpack' }} me-1"></i>
              Mode {{ $siswaModeLabel }}
            </span>

            <div class="navbar-nav flex-row order-md-last ms-auto">
              <div class="nav-item dropdown">
                <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
                  @php($studentPhoto = auth()->user()->studentProfile->photo ?? null)
                  @if($studentPhoto)
                    <span class="avatar avatar-sm" style="background-image: url({{ asset('storage/' . $studentPhoto) }})"></span>
                  @else
                    <span class="avatar avatar-sm"><i class="bi bi-person"></i></span>
                  @endif
                  <div class="d-none d-xl-block ps-2">
                    <div>{{ auth()->user()->name }}</div>
                    <div class="mt-1 small text-secondary">{{ $siswaModeLabel }}</div>
                  </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                  <a href="{{ route('siswa.profile') }}" class="dropdown-item">Profil Saya</a>
                  <a href="#" class="dropdown-item" onclick="event.preventDefault(); document.getElementById('switch-mode-form').submit();">
                    <i class="bi bi-arrow-left-right me-1"></i> Ganti Mode
                  </a>
                  <form id="switch-mode-form" action="{{ route('siswa.mode.switch') }}" method="POST" class="d-none">@csrf</form>
                  <div class="dropdown-divider"></div>
                  <a href="#" class="dropdown-item" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
                  <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" class="d-none">@csrf</form>
                </div>
              </div>
            </div>
          </div>
        </header>

        @yield('page-header')

        <main id="content" class="page-body">
          <div class="container-xl">
            @yield('content')
          </div>
        </main>

        <footer class="footer footer-transparent d-print-none">
          <div class="container-xl text-center text-muted small py-2">
            &copy; {{ date('Y') }} LIVO — Area Siswa
          </div>
        </footer>
      </div>
    </div>

    <script src="{{ asset('assets/js/tabler.min.js') }}" defer></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Flash messages --}}
    @if(session('success'))
    <script>
        Swal.fire({ title: 'Berhasil!', text: '{{ session('success') }}', icon: 'success', timer: 3000, showConfirmButton: false });
    </script>
    @endif
    @if(session('error'))
    <script>
        Swal.fire({ title: 'Gagal!', text: '{{ session('error') }}', icon: 'error' });
    </script>
    @endif
    @if($errors->any())
    <script>
        Swal.fire({ title: 'Validasi Gagal!', html: '{!! implode("<br>", $errors->all()) !!}', icon: 'warning' });
    </script>
    @endif

    @stack('js')
  </body>
</html>
