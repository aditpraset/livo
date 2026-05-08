<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0"/>
  <meta name="description" content="@yield('meta_description', 'LIVO — Learning Innovation. Bimbingan Belajar Terpadu TK–SMP di Jakarta Selatan yang fokus pada pengembangan kemampuan berpikir kritis dan kreatif siswa.')" />
  <meta name="keywords" content="@yield('meta_keywords', 'bimbel, bimbingan belajar, livo, matematika, bahasa inggris, jakarta selatan, srengseng sawah, pendidikan anak, kursus matematika, kursus bahasa inggris')" />
  <meta name="author" content="LIVO — Learning Innovation" />
  <meta name="robots" content="index, follow" />
  <link rel="canonical" href="{{ url()->current() }}" />

  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="website" />
  <meta property="og:url" content="{{ url()->current() }}" />
  <meta property="og:title" content="@yield('title', 'LIVO — Learning Innovation')" />
  <meta property="og:description" content="@yield('meta_description', 'LIVO — Learning Innovation. Bimbingan Belajar Terpadu TK–SMP di Jakarta Selatan.')" />
  <meta property="og:image" content="{{ asset('frontend/images/og-image.jpg') }}" />
  <meta property="og:site_name" content="LIVO Learning Innovation" />
  <meta property="og:locale" content="id_ID" />

  <!-- Twitter -->
  <meta property="twitter:card" content="summary_large_image" />
  <meta property="twitter:url" content="{{ url()->current() }}" />
  <meta property="twitter:title" content="@yield('title', 'LIVO — Learning Innovation')" />
  <meta property="twitter:description" content="@yield('meta_description', 'LIVO — Learning Innovation. Bimbingan Belajar Terpadu TK–SMP di Jakarta Selatan.')" />
  <meta property="twitter:image" content="{{ asset('frontend/images/og-image.jpg') }}" />

  <!-- Favicons -->
  <link rel="icon" href="{{ asset('frontend/images/logo.jpeg') }}" sizes="any" />
  <link rel="icon" href="{{ asset('frontend/images/logo.jpeg') }}" type="image/svg+xml" />
  <link rel="apple-touch-icon" href="{{ asset('frontend/images/logo.jpeg') }}" />

  <title>@yield('title', 'LIVO — Learning Innovation')</title>

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Fraunces:ital,wght@0,700;0,900;1,700&display=swap" rel="stylesheet"/>
  
  <!-- CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet"/>
  <link href="{{ asset('frontend/css/style.css') }}" rel="stylesheet"/>

  @stack('css')
</head>
<body>

  <!-- NAVBAR -->
  <nav class="livo-navbar" aria-label="Main Navigation">
    <div class="container">
      <div class="d-flex align-items-center justify-content-between">
        <a href="{{ url('/') }}" class="livo-logo">
          <div class=""><img src="{{ asset('frontend/images/logo.jpeg') }}" alt="Livo" style="height: 32px" /></div>
          {{-- <div>
            <div class="livo-logo-text">LIVO</div>
            <div class="livo-logo-sub">Learning Innovation</div>
          </div> --}}
        </a>
        <div class="d-none d-lg-flex align-items-center gap-1">
          <a href="{{ url('/#beranda') }}" class="nav-link">Beranda</a>
          <a href="{{ url('/#program') }}" class="nav-link">Program</a>
          <a href="{{ url('/#tentang') }}" class="nav-link">Tentang Kami</a>
          <a href="{{ url('/#kontak') }}" class="nav-link">Kontak</a>
          <a href="{{ route('registration') }}" class="btn-nav-cta ms-2">Daftar Sekarang</a>
        </div>
        <button class="btn d-lg-none border-0 p-1" type="button" data-bs-toggle="collapse" data-bs-target="#mobileMenu">
          <i class="bi bi-list" style="font-size:24px;color:var(--livo-dark)"></i>
        </button>
      </div>
      <div class="collapse d-lg-none" id="mobileMenu">
        <div class="py-3 d-flex flex-column gap-1">
          <a href="{{ url('/#beranda') }}" class="nav-link">Beranda</a>
          <a href="{{ url('/#program') }}" class="nav-link">Program</a>
          <a href="{{ url('/#tentang') }}" class="nav-link">Tentang Kami</a>
          <a href="{{ url('/#kontak') }}" class="nav-link">Kontak</a>
          <a href="{{ route('registration') }}" class="btn-nav-cta mt-1 text-center">Daftar Sekarang</a>
        </div>
      </div>
    </div>
  </nav>

  @yield('content')

  <!-- FOOTER -->
  <footer class="footer-section" aria-label="Main Footer">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-4">
          <div class="d-flex align-items-center gap-2 mb-3">
            <div class="livo-logo-mark" aria-hidden="true">LV</div>
            <h2 class="footer-logo-text mb-0">LIVO</h2>
          </div>
          <p class="footer-desc">Lembaga pendidikan yang mengembangkan kemampuan berpikir kritis dan kreatif siswa TK–SMP melalui program Matematika dan Bahasa Inggris.</p>
          <div class="mt-4">
            <a href="#" class="social-icon" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
            <a href="#" class="social-icon" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
            <a href="#" class="social-icon" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
            <a href="#" class="social-icon" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
          </div>
        </div>
        <div class="col-6 col-lg-2 offset-lg-1">
          <h3 class="footer-heading">Navigasi</h3>
          <nav class="d-flex flex-column" aria-label="Footer Navigation">
            <a href="{{ url('/#beranda') }}" class="footer-link">Beranda</a>
            <a href="{{ url('/#program') }}" class="footer-link">Program</a>
            <a href="{{ url('/#tentang') }}" class="footer-link">Tentang Kami</a>
            <a href="{{ url('/#testimoni') }}" class="footer-link">Testimoni</a>
            <a href="{{ route('registration') }}" class="footer-link">Daftar</a>
          </nav>
        </div>
        <div class="col-6 col-lg-2">
          <h3 class="footer-heading">Program</h3>
          <nav class="d-flex flex-column" aria-label="Program Links">
            <a href="{{ url('/#program') }}" class="footer-link">Matematika</a>
            <a href="{{ url('/#program') }}" class="footer-link">Bahasa Inggris</a>
            <a href="{{ url('/#program') }}" class="footer-link">Konsultasi Tugas</a>
          </nav>
        </div>
        <div class="col-lg-3">
          <h3 class="footer-heading">Kontak</h3>
          <address class="mb-0">
            <p class="footer-link mb-2" style="cursor:default;"><i class="bi bi-geo-alt me-2" aria-hidden="true"></i>Srengseng Sawah, Jakarta Selatan</p>
            <a href="https://wa.me/62xxxxxxxxxx" class="footer-link mb-2 d-block"><i class="bi bi-whatsapp me-2" aria-hidden="true"></i>+62 xxx-xxxx-xxxx</a>
            <a href="mailto:info@livo-learning.id" class="footer-link d-block"><i class="bi bi-envelope me-2" aria-hidden="true"></i>info@livo-learning.id</a>
          </address>
        </div>
      </div>
      <hr class="footer-divider"/>
      <div class="footer-bottom d-flex flex-wrap justify-content-between gap-2">
        <p class="mb-0">&copy; {{ date('Y') }} LIVO — Learning Innovation. All rights reserved.</p>
        <p class="mb-0">Srengseng Sawah, Jakarta Selatan</p>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Smooth active nav on scroll
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-link');

    window.addEventListener('scroll', () => {
      let current = '';
      sections.forEach(s => {
        if (window.scrollY >= s.offsetTop - 100) current = s.getAttribute('id');
      });
      navLinks.forEach(l => {
        l.style.background = '';
        l.style.color = '';
        if (l.getAttribute('href') === '#' + current) {
          l.style.background = 'var(--livo-blue-light)';
          l.style.color = 'var(--livo-blue)';
        }
      });
    });

    // Form submit handler
    const submitBtn = document.querySelector('.btn-submit');
    if (submitBtn) {
      submitBtn.addEventListener('click', (e) => {
        e.preventDefault();
        alert('Terima kasih! Tim LIVO akan segera menghubungi Anda. 🎓');
      });
    }
  </script>
  @stack('js')
</body>
</html>
