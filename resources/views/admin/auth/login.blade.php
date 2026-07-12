<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Login - LIVO</title>

    <!-- CSS files -->
    <link href="{{ asset('assets/css/tabler.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/tabler-vendors.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/admin-style.css') }}" rel="stylesheet" />

    <style>
      @import url("https://rsms.me/inter/inter.css");
      :root {
        --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
      }
      body {
        font-feature-settings: "cv03", "cv04", "cv11";
      }
    </style>
  </head>
  <body class="d-flex flex-column">
    <div class="page page-center">
      <div class="container container-tight py-4">
        <div class="text-center mb-4">
          LIVO
        </div>
        <div class="card card-md">
          <div class="card-body">

            @if($errors->any())
              <div class="alert alert-danger">
                <ul class="mb-0">
                  @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            @if($step === 'email')
              {{-- ── Step 1: masukkan email ── --}}
              <h2 class="h2 text-center mb-1">Masuk ke akun Anda</h2>
              <p class="text-muted text-center mb-4">Masukkan email untuk melanjutkan.</p>

              <form action="{{ route('admin.login.check-email') }}" method="POST" autocomplete="off">
                @csrf
                <div class="mb-3">
                  <label class="form-label">Alamat Email</label>
                  <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="your@email.com" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="form-footer">
                  <button type="submit" class="btn btn-primary w-100">Lanjutkan</button>
                </div>
              </form>

            @elseif($step === 'password')
              {{-- ── Step 2a: sudah punya password ── --}}
              <h2 class="h2 text-center mb-1">Halo, {{ $name }}</h2>
              <p class="text-muted text-center mb-4">{{ $email }}</p>

              <form action="{{ route('admin.login.submit') }}" method="POST" autocomplete="off">
                @csrf
                <div class="mb-2">
                  <label class="form-label">Password</label>
                  <div class="input-group input-group-flat">
                    <input type="password" id="password-input" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Password Anda" autocomplete="off" required autofocus>
                    <span class="input-group-text">
                      <a href="#" class="link-secondary toggle-password" title="Lihat password">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="2" /><path d="M22 12c-2.667 4.667 -6 7 -10 7s-7.333 -2.333 -10 -7c2.667 -4.667 6 -7 10 -7s7.333 2.333 10 7" /></svg>
                      </a>
                    </span>
                  </div>
                </div>
                <div class="mb-2">
                  <label class="form-check">
                    <input type="checkbox" name="remember" class="form-check-input" />
                    <span class="form-check-label">Ingat saya di perangkat ini</span>
                  </label>
                </div>
                <div class="form-footer">
                  <button type="submit" class="btn btn-primary w-100">Masuk</button>
                </div>
              </form>

              <form action="{{ route('admin.login.reset-email') }}" method="POST" class="text-center mt-3">
                @csrf
                <button type="submit" class="btn btn-link link-secondary">Gunakan email lain</button>
              </form>

            @else
              {{-- ── Step 2b: belum punya password, buat baru ── --}}
              <h2 class="h2 text-center mb-1">Halo, {{ $name }}</h2>
              <p class="text-muted text-center mb-4">
                {{ $email }}<br>
                Ini login pertama Anda — silakan buat password.
              </p>

              <form action="{{ route('admin.login.create-password') }}" method="POST" autocomplete="off">
                @csrf
                <div class="mb-3">
                  <label class="form-label">Password Baru</label>
                  <div class="input-group input-group-flat">
                    <input type="password" id="password-input" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Minimal 8 karakter" autocomplete="new-password" required autofocus>
                    <span class="input-group-text">
                      <a href="#" class="link-secondary toggle-password" title="Lihat password">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="2" /><path d="M22 12c-2.667 4.667 -6 7 -10 7s-7.333 -2.333 -10 -7c2.667 -4.667 6 -7 10 -7s7.333 2.333 10 7" /></svg>
                      </a>
                    </span>
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label">Ulangi Password</label>
                  <input type="password" name="password_confirmation" class="form-control" placeholder="Ketik ulang password" autocomplete="new-password" required>
                </div>
                <div class="form-footer">
                  <button type="submit" class="btn btn-primary w-100">Buat Password & Masuk</button>
                </div>
              </form>

              <form action="{{ route('admin.login.reset-email') }}" method="POST" class="text-center mt-3">
                @csrf
                <button type="submit" class="btn btn-link link-secondary">Gunakan email lain</button>
              </form>
            @endif

          </div>
        </div>
      </div>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('assets/js/tabler.min.js') }}" defer></script>
    <script>
      document.addEventListener('click', function (ev) {
        var toggle = ev.target.closest('.toggle-password');
        if (!toggle) return;
        ev.preventDefault();
        var input = document.getElementById('password-input');
        if (input) input.type = input.type === 'password' ? 'text' : 'password';
      });
    </script>
  </body>
</html>
