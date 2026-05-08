<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>@yield('title', 'Dashboard - LIVO Admin')</title>
    
    <!-- CSS files -->
    <link href="{{ asset('admin/assets/css/tabler.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('admin/assets/css/tabler-flags.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('admin/assets/css/tabler-socials.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('admin/assets/css/tabler-payments.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('admin/assets/css/tabler-vendors.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('admin/assets/css/tabler-marketing.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('admin/assets/css/tabler-themes.min.css') }}" rel="stylesheet" />
    
    <!-- Custom Admin Style -->
    <link rel="stylesheet" href="{{ asset('admin/assets/css/admin-style.css') }}">
    
    <style>
      @import url("https://rsms.me/inter/inter.css");
      :root {
      	--tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
      }
      body {
      	font-feature-settings: "cv03", "cv04", "cv11";
      }
    </style>
    @stack('css')
  </head>
  <body class="layout-fluid">
    <script src="{{ asset('admin/assets/js/tabler-theme.min.js') }}"></script>
    <div class="page">
      <!-- Sidebar -->
      @include('admin.partials.sidebar')
      
      <div class="page-wrapper">
        @include('admin.partials.header')
        
        <!-- Page Header -->
        @yield('page-header')

        <!-- Page Body -->
        <main id="content" class="page-body">
          <div class="container-xl">
            @yield('content')
          </div>
        </main>

        <!-- Footer -->
        @include('admin.partials.footer')
      </div>
    </div>

    <!-- Modals -->
    @include('admin.partials.modals')

    <!-- Libs & Scripts -->
    @include('admin.partials.scripts')
    
    @stack('js')
  </body>
</html>
