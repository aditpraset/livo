<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Pilih Mode Masuk - LIVO</title>

    <link href="{{ asset('assets/css/tabler.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/tabler-vendors.min.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/css/admin-style.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
      @import url("https://rsms.me/inter/inter.css");
      :root {
        --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
      }
      body { font-feature-settings: "cv03", "cv04", "cv11"; }

      .mode-card {
        border: 2px solid #e6e9f0;
        border-radius: 16px;
        transition: all .18s ease;
        height: 100%;
        background: #fff;
      }
      .mode-card:hover { border-color: #00C2FF; transform: translateY(-3px); box-shadow: 0 12px 24px rgba(13,27,42,.10); }
      .mode-btn { border: 0; background: transparent; padding: 0; width: 100%; text-align: left; }
      .mode-icon {
        width: 64px; height: 64px; border-radius: 16px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 2rem;
      }
      .mode-icon-siswa { background: rgba(0,194,255,.12); color: #0aa2c7; }
      .mode-icon-ortu  { background: rgba(31,122,77,.12); color: #1F7A4D; }
    </style>
  </head>
  <body class="d-flex flex-column">
    <div class="page page-center">
      <div class="container container-normal py-4">
        <div class="text-center mb-4">
          <img src="{{ asset('frontend/images/logo.jpeg') }}" alt="Livo" style="height: 40px" class="mb-3">
          <h1 class="h2 mb-1">Halo, {{ $student->nickname ?: $student->full_name }}</h1>
          <p class="text-muted mb-0">Silakan pilih ingin masuk sebagai apa.</p>
        </div>

        @if($errors->any())
          <div class="alert alert-danger mx-auto" style="max-width: 720px;">
            <ul class="mb-0 ps-3">
              @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
          </div>
        @endif

        <div class="row g-3 justify-content-center" style="max-width: 720px; margin: 0 auto;">
          <div class="col-md-6">
            <form action="{{ route('siswa.mode.store') }}" method="POST" class="h-100">
              @csrf
              <input type="hidden" name="mode" value="siswa">
              <button type="submit" class="mode-btn h-100">
                <div class="mode-card p-4 text-center">
                  <span class="mode-icon mode-icon-siswa mb-3"><i class="bi bi-backpack"></i></span>
                  <h3 class="mb-1">Siswa</h3>
                  <p class="text-muted small mb-3">
                    Lihat jadwal belajar, nilai, dan perkembangan belajarmu sendiri.
                  </p>
                  <span class="btn btn-primary w-100">
                    Masuk sebagai Siswa <i class="bi bi-arrow-right ms-1"></i>
                  </span>
                </div>
              </button>
            </form>
          </div>

          <div class="col-md-6">
            <form action="{{ route('siswa.mode.store') }}" method="POST" class="h-100">
              @csrf
              <input type="hidden" name="mode" value="orang_tua">
              <button type="submit" class="mode-btn h-100">
                <div class="mode-card p-4 text-center">
                  <span class="mode-icon mode-icon-ortu mb-3"><i class="bi bi-people"></i></span>
                  <h3 class="mb-1">Orang Tua</h3>
                  <p class="text-muted small mb-3">
                    Pantau kehadiran, hasil evaluasi, dan pembayaran ananda {{ $student->nickname ?: $student->full_name }}.
                  </p>
                  <span class="btn btn-success w-100">
                    Masuk sebagai Orang Tua <i class="bi bi-arrow-right ms-1"></i>
                  </span>
                </div>
              </button>
            </form>
          </div>
        </div>

        <div class="text-center mt-4">
          <p class="text-muted small mb-2">Mode bisa diganti kapan saja dari menu profil di dalam aplikasi.</p>
          <form action="{{ route('admin.logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-link link-secondary">
              <i class="bi bi-box-arrow-left me-1"></i> Keluar
            </button>
          </form>
        </div>
      </div>
    </div>

    <script src="{{ asset('assets/js/tabler.min.js') }}" defer></script>
  </body>
</html>
