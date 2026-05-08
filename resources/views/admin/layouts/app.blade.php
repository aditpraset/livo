<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    <title>@yield('title', 'Dashboard - LIVO Admin')</title>
    
    <!-- CSS files -->
    <link href="{{ asset('assets/css/tabler.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/tabler-flags.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/tabler-socials.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/tabler-payments.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/tabler-vendors.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/tabler-marketing.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/css/tabler-themes.min.css') }}" rel="stylesheet" />
    
    <!-- Custom Admin Style -->
    <link rel="stylesheet" href="{{ asset('assets/css/admin-style.css') }}">
    
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 0; }
        .dataTables_wrapper .dataTables_filter input { border: 1px solid #ddd; border-radius: 4px; padding: 4px 8px; }
    </style>
  </head>
  <body class="layout-fluid">
    <script src="{{ asset('assets/js/tabler-theme.min.js') }}"></script>
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
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    @stack('js')
  </body>
</html>
