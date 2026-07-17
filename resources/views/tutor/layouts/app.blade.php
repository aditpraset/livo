<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>@yield('title', 'Dashboard Tutor - LIVO')</title>

    <!-- CSS files -->
    <link href="{{ asset('assets/css/tabler.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/tabler-vendors.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/tabler-themes.min.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/css/admin-style.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
      @import url("https://rsms.me/inter/inter.css");
      :root {
        --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
      }
      body { font-feature-settings: "cv03", "cv04", "cv11"; }
      .badge { color: white; }
    </style>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 0; }
        .dataTables_wrapper .dataTables_filter input { border: 1px solid #ddd; border-radius: 4px; padding: 4px 8px; }
    </style>
    @stack('css')
  </head>
  <body class="layout-fluid">
    <script src="{{ asset('assets/js/tabler-theme.min.js') }}"></script>
    <div class="page">
      <!-- Sidebar Tutor -->
      <aside class="navbar navbar-vertical navbar-expand-lg bg-white">
        <div class="container-fluid">
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu"
            aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle sidebar navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="navbar-brand">
            <img src="{{ asset('frontend/images/logo.jpeg') }}" alt="Livo" class="navbar-brand-image" style="height: 32px" />
          </div>

          <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
              <li class="nav-item {{ request()->routeIs('tutor.dashboard') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('tutor.dashboard') }}">
                  <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="bi bi-house fs-2"></i></span>
                  <span class="nav-link-title"> Dashboard </span>
                </a>
              </li>
              <li class="nav-item {{ request()->routeIs('tutor.schedules*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('tutor.schedules.week') }}">
                  <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="bi bi-calendar-week fs-2"></i></span>
                  <span class="nav-link-title"> Jadwal Mingguan </span>
                </a>
              </li>
              <li class="nav-item {{ request()->routeIs('tutor.students*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('tutor.students.index') }}">
                  <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="bi bi-people fs-2"></i></span>
                  <span class="nav-link-title"> Data Siswa </span>
                </a>
              </li>
              <li class="nav-item {{ request()->routeIs('tutor.evaluations*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('tutor.evaluations.index') }}">
                  <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="bi bi-clipboard-check fs-2"></i></span>
                  <span class="nav-link-title"> Evaluasi Siswa </span>
                </a>
              </li>
              <li class="nav-item {{ request()->routeIs('tutor.rekap-pengajaran') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('tutor.rekap-pengajaran') }}">
                  <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="bi bi-journal-text fs-2"></i></span>
                  <span class="nav-link-title"> Rekap Pengajaran </span>
                </a>
              </li>
              <li class="nav-item {{ request()->routeIs('tutor.rekap-fee') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('tutor.rekap-fee') }}">
                  <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="bi bi-cash-coin fs-2"></i></span>
                  <span class="nav-link-title"> Rekap Fee </span>
                </a>
              </li>
              <li class="nav-item {{ request()->routeIs('tutor.reports*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('tutor.reports.index') }}">
                  <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="bi bi-file-earmark-arrow-down fs-2"></i></span>
                  <span class="nav-link-title"> Laporan </span>
                </a>
              </li>
              <li class="nav-item {{ request()->routeIs('tutor.profile*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('tutor.profile') }}">
                  <span class="nav-link-icon d-md-none d-lg-inline-block"><i class="bi bi-person-circle fs-2"></i></span>
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
            <div class="navbar-nav flex-row order-md-last ms-auto">
              <div class="nav-item dropdown">
                <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
                  @php($tutorPhoto = auth()->user()->tutor->photo ?? null)
                  @if($tutorPhoto)
                    <span class="avatar avatar-sm" style="background-image: url({{ asset('storage/' . $tutorPhoto) }})"></span>
                  @else
                    <span class="avatar avatar-sm"><i class="bi bi-person"></i></span>
                  @endif
                  <div class="d-none d-xl-block ps-2">
                    <div>{{ auth()->user()->name }}</div>
                    <div class="mt-1 small text-secondary">Tutor</div>
                  </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                  <a href="{{ route('tutor.profile') }}" class="dropdown-item">Profil Saya</a>
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
            &copy; {{ date('Y') }} LIVO — Area Tutor
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
