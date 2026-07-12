<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <title>Dashboard Siswa - LIVO</title>
    <link href="{{ asset('assets/css/tabler.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/admin-style.css') }}" rel="stylesheet" />
  </head>
  <body class="d-flex flex-column">
    <div class="page page-center">
      <div class="container container-tight py-4 text-center">
        <div class="mb-3">LIVO</div>
        <div class="card card-md">
          <div class="card-body">
            <h2 class="h2 mb-1">Halo, {{ auth()->user()->name }}</h2>
            <p class="text-muted">Anda login sebagai <span class="badge bg-primary-lt">Siswa</span></p>
            <p class="text-muted">Dashboard siswa sedang dalam pengembangan.</p>
            <form action="{{ route('admin.logout') }}" method="POST">
              @csrf
              <button type="submit" class="btn btn-outline-danger">Logout</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
