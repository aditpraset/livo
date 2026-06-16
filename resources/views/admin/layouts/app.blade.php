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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
      @import url("https://rsms.me/inter/inter.css");
      :root {
      	--tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
      }
      body {
      	font-feature-settings: "cv03", "cv04", "cv11";
      }
      .badge {
        color:white;
      }
    </style>
    @stack('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">

    <!-- Select2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
    <style>
        .dataTables_wrapper .dataTables_paginate .paginate_button { padding: 0; }
        .dataTables_wrapper .dataTables_filter input { border: 1px solid #ddd; border-radius: 4px; padding: 4px 8px; }
        /* Select2 — selaraskan dengan tinggi kontrol Bootstrap */
        .select2-container--bootstrap-5 .select2-selection { min-height: calc(1.5em + 0.75rem + 2px); }
        .select2-container .select2-dropdown { z-index: 1100; }
        /* Versi kecil untuk filter form-select-sm */
        .form-select-sm + .select2-container--bootstrap-5 .select2-selection { min-height: calc(1.5em + 0.5rem + 2px); font-size: .875rem; }
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    {{-- Global Select2: ubah seluruh dropdown <select> menjadi Select2 --}}
    <script>
        // Inisialisasi (atau re-init) semua <select> dalam sebuah scope menjadi Select2.
        // Dikecualikan: kontrol panjang DataTables & select bertanda .no-select2.
        window.applySelect2 = function (scope) {
            var $scope = scope ? $(scope) : $(document);
            $scope.find('select').not('[name$="_length"]').not('.no-select2').each(function () {
                var $s = $(this);
                if ($s.data('select2')) { $s.select2('destroy'); }
                var $modal = $s.closest('.modal');
                var placeholder = ($s.find('option[value=""]').first().text() || '').trim();
                // Pertahankan lebar tetap bila di-set via style (mis. filter bar), selain itu penuh.
                var width = this.style.width || '100%';
                $s.select2({
                    theme: 'bootstrap-5',
                    width: width,
                    dropdownParent: $modal.length ? $modal : $(document.body),
                    placeholder: placeholder || null,
                    allowClear: false
                });
            });
        };

        $(function () {
            // Semua dropdown di halaman (page-header + konten + modal tersembunyi)
            window.applySelect2($(document.body));

            // Dropdown di dalam modal — re-init saat modal tampil agar opsi
            // yang diisi dinamis (mis. silabus, sesi) ikut ter-render dengan benar.
            $(document).on('shown.bs.modal', '.modal', function () {
                window.applySelect2($(this));
            });
        });
    </script>

    {{-- Global SweetAlert2 Flash Messages --}}
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
