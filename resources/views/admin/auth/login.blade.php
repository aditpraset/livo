<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>Login - LIVO Admin</title>
    
    <!-- CSS files -->
    <link href="{{ asset('admin/assets/css/tabler.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('admin/assets/css/tabler-vendors.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('admin/assets/css/admin-style.css') }}" rel="stylesheet" />
    
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
            <h2 class="h2 text-center mb-4">Login to your account</h2>
            
            @if($errors->any())
              <div class="alert alert-danger">
                <ul class="mb-0">
                  @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            <form action="{{ route('admin.login.submit') }}" method="POST" autocomplete="off">
              @csrf
              <div class="mb-3">
                <label class="form-label">Email address</label>
                <input type="email" name="email" class="form-control" placeholder="your@email.com" value="{{ old('email') }}" required autofocus>
              </div>
              <div class="mb-2">
                <label class="form-label">
                  Password
                  <span class="form-label-description">
                    <a href="#">I forgot password</a>
                  </span>
                </label>
                <div class="input-group input-group-flat">
                  <input type="password" name="password" class="form-control" placeholder="Your password" autocomplete="off" required>
                  <span class="input-group-text">
                    <a href="#" class="link-secondary" title="Show password" data-bs-toggle="tooltip">
                      <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="2" /><path d="M22 12c-2.667 4.667 -6 7 -10 7s-7.333 -2.333 -10 -7c2.667 -4.667 6 -7 10 -7s7.333 2.333 10 7" /></svg>
                    </a>
                  </span>
                </div>
              </div>
              <div class="mb-2">
                <label class="form-check">
                  <input type="checkbox" name="remember" class="form-check-input" />
                  <span class="form-check-label">Remember me on this device</span>
                </label>
              </div>
              <div class="form-footer">
                <button type="submit" class="btn btn-primary w-100">Sign in</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Scripts -->
    <script src="{{ asset('admin/assets/js/tabler.min.js') }}" defer></script>
  </body>
</html>
