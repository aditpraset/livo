@extends('website.layouts.app')

@section('title', 'LIVO — Learning Innovation | Bimbel TK, SD, SMP Terpercaya di Jakarta Selatan')
@section('meta_description', 'LIVO adalah bimbingan belajar terpadu di Srengseng Sawah, Jakarta Selatan. Fokus pada Matematika dan Bahasa Inggris untuk jenjang TK, SD, dan SMP dengan metode berpikir kritis dan kreatif.')
@section('meta_keywords', 'bimbel jakarta selatan, bimbingan belajar matematika, les bahasa inggris anak, bimbel srengseng sawah, livo learning innovation, bimbel tk sd smp, bimbingan belajar terpercaya')

@section('content')
<!-- HERO -->
<section class="hero-section" id="beranda">
  <div class="hero-bg-shape s1"></div>
  <div class="hero-bg-shape s2"></div>
  <div class="hero-grid-dots"></div>
  <div class="container position-relative">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <div class="hero-badge fade-up">
          <i class="bi bi-lightning-charge-fill"></i> Bimbingan Belajar Terpadu
        </div>
        <h1 class="hero-title fade-up fade-up-d1">
          Belajar Lebih <span>Cerdas,</span> Berpikir Lebih Jauh
        </h1>
        <p class="hero-desc fade-up fade-up-d2">
          LIVO mengintegrasikan metode pembelajaran terpadu untuk mengembangkan kemampuan berpikir kritis dan kreatif siswa TK–SMP. Fokus Matematika & Bahasa Inggris.
        </p>
        <div class="d-flex flex-wrap gap-3 fade-up fade-up-d3">
          <a href="{{ route('registration') }}" class="btn-hero-primary">Daftar Sekarang &rarr;</a>
          <a href="#program" class="btn-hero-secondary">Lihat Program</a>
        </div>
      </div>
      <div class="col-lg-5 offset-lg-1 fade-up fade-up-d2">
        <div class="hero-card-float mb-3">
          <div class="d-flex align-items-center gap-3 mb-3">
            <div style="width:42px;height:42px;background:var(--livo-yellow);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;"><i class="bi bi-book"></i></div>
            <div>
              <div style="font-size:13px;font-weight:700;color:#fff;">Program Unggulan</div>
              <div style="font-size:11px;color:rgba(255,255,255,0.5);">Matematika & Bahasa Inggris</div>
            </div>
          </div>
          <div style="font-size:13px;color:rgba(255,255,255,0.6);line-height:1.6;">Metode pembelajaran sistematis dengan bimbingan personal dari tutor berpengalaman untuk setiap siswa.</div>
        </div>
        <div class="hero-stat-grid">
          <div class="hero-stat">
            <div class="hero-stat-num">TK–SMP</div>
            <div class="hero-stat-label">Jenjang Siswa</div>
          </div>
          <div class="hero-stat">
            <div class="hero-stat-num">2</div>
            <div class="hero-stat-label">Program Utama</div>
          </div>
          <div class="hero-stat">
            <div class="hero-stat-num">24/7</div>
            <div class="hero-stat-label">Konsultasi Tugas</div>
          </div>
          <div class="hero-stat">
            <div class="hero-stat-num">100%</div>
            <div class="hero-stat-label">Bimbingan Personal</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- KEUNGGULAN -->
<section class="keunggulan-section">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-tag"><i class="bi bi-star-fill"></i> Keunggulan Kami</div>
      <h2 class="section-title">Mengapa Memilih <span>LIVO?</span></h2>
      <p class="section-desc mt-3 mx-auto" style="max-width:520px;">Kami hadir dengan pendekatan yang berbeda — pembelajaran yang menyentuh cara berpikir, bukan sekadar menghafal.</p>
    </div>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="keunggulan-card">
          <div class="keunggulan-icon"><i class="bi bi-lightbulb"></i></div>
          <div class="keunggulan-title">Berpikir Kritis & Kreatif</div>
          <p class="keunggulan-desc">Sistem pembelajaran terpadu yang dirancang untuk mengasah kemampuan analisis dan kreativitas anak dalam memahami setiap materi pelajaran.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="keunggulan-card">
          <div class="keunggulan-icon"><i class="bi bi-person-heart"></i></div>
          <div class="keunggulan-title">Bimbingan Personal</div>
          <p class="keunggulan-desc">Tutor berpengalaman menyesuaikan kecepatan belajar setiap anak secara personal, memastikan tidak ada siswa yang tertinggal.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="keunggulan-card">
          <div class="keunggulan-icon"><i class="bi bi-chat-dots"></i></div>
          <div class="keunggulan-title">Konsultasi Tugas Kapanpun</div>
          <p class="keunggulan-desc">Siswa dapat berkonsultasi tugas sekolah kapan saja — dukungan belajar tidak terbatas hanya pada jam kelas.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- PROGRAM -->
<section class="program-section" id="program">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-tag"><i class="bi bi-book"></i> Program Kami</div>
      <h2 class="section-title">Program <span>Unggulan</span> LIVO</h2>
      <p class="section-desc mt-3 mx-auto" style="max-width:480px;">Dua program inti yang dirancang untuk membangun pondasi akademik siswa TK hingga SMP.</p>
    </div>
    <div class="row g-4 mb-4">
      <div class="col-md-6">
        <div class="program-card">
          <div class="program-card-header math">
            <div class="program-card-header-shape"></div>
            <div class="d-flex align-items-center justify-content-between position-relative" style="z-index: 1;">
              <div>
                <div class="program-card-title">Matematika</div>
                <div class="program-card-jenjang"><i class="bi bi-mortarboard me-1"></i> Untuk siswa TK – SMP</div>
              </div>
              <div class="program-icon-big m-0">
                <img src="{{ asset('frontend/images/math_vector.png') }}" alt="Bimbel Matematika LIVO" class="img-fluid" style="height: 100px;">
              </div>
            </div>
          </div>
          <div class="program-card-body">
            <ul class="program-feature-list">
              <li><i class="bi bi-check-circle-fill"></i> Pemahaman konsep dasar hingga lanjutan</li>
              <li><i class="bi bi-check-circle-fill"></i> Pendekatan berpikir logis dan sistematis</li>
              <li><i class="bi bi-check-circle-fill"></i> Persiapan ujian sekolah dan kompetisi</li>
              <li><i class="bi bi-check-circle-fill"></i> Latihan soal dengan pembahasan mendalam</li>
              <li><i class="bi bi-check-circle-fill"></i> Tutor menyesuaikan kecepatan belajar siswa</li>
            </ul>
            <a href="{{ route('registration') }}" class="btn-program">Daftar Program Matematika</a>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="program-card">
          <div class="program-card-header english">
            <div class="program-card-header-shape"></div>
            <div class="d-flex align-items-center justify-content-between position-relative" style="z-index: 1;">
              <div>
                <div class="program-card-title">Bahasa Inggris</div>
                <div class="program-card-jenjang"><i class="bi bi-mortarboard me-1"></i> Untuk siswa TK – SMP</div>
              </div>
              <div class="program-icon-big m-0">
                <img src="{{ asset('frontend/images/english_vector.png') }}" alt="Bimbel Bahasa Inggris LIVO" class="img-fluid" style="height: 100px;">
              </div>
            </div>
          </div>
          <div class="program-card-body">
            <ul class="program-feature-list">
              <li><i class="bi bi-check-circle-fill"></i> Penguasaan kosa kata dan tata bahasa</li>
              <li><i class="bi bi-check-circle-fill"></i> Metode komunikatif dan kreatif</li>
              <li><i class="bi bi-check-circle-fill"></i> Latihan reading, writing, dan speaking</li>
              <li><i class="bi bi-check-circle-fill"></i> Persiapan ujian bahasa sekolah</li>
              <li><i class="bi bi-check-circle-fill"></i> Pembelajaran kontekstual dan menyenangkan</li>
            </ul>
            <a href="{{ route('registration') }}" class="btn-program">Daftar Program Bahasa Inggris</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- TENTANG KAMI -->
<section class="tentang-section" id="tentang">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-5">
        {{-- <div class="tentang-img-box">
          <div class="tentang-img-icon">🎓</div>
          <div class="tentang-img-label">Learning Innovation</div>
          <div class="tentang-img-sub">Srengseng Sawah, Jakarta Selatan</div>
          <div class="tentang-badge-float">
            <div class="tentang-badge-num">100%</div>
            <div class="tentang-badge-label">Bimbingan Personal</div>
          </div>
        </div> --}}
        <img src="{{ asset('frontend/images/hero.png') }}" alt="Belajar di LIVO Learning Innovation" class="img-fluid rounded-3">
      </div>
      <div class="col-lg-7">
        <div class="section-tag"><i class="bi bi-building-check"></i> Tentang Kami</div>
        <h2 class="section-title mb-4">Kami Percaya Setiap Anak Bisa <span>Berkembang</span></h2>
        <p class="section-desc mb-4">LIVO adalah lembaga pendidikan yang dibangun dengan mengintegrasikan beberapa konsep pembelajaran menjadi suatu sistem yang terpadu dan sistematis — untuk mengembangkan kemampuan berpikir anak menjadi lebih kritis dan kreatif.</p>
        <div class="nilai-item">
          <div class="nilai-icon"><i class="bi bi-puzzle"></i></div>
          <div>
            <div class="nilai-title">Pembelajaran Terpadu & Sistematis</div>
            <p class="nilai-desc">Mengintegrasikan berbagai konsep belajar dalam satu sistem yang terstruktur dan mudah diikuti oleh setiap siswa.</p>
          </div>
        </div>
        <div class="nilai-item">
          <div class="nilai-icon"><i class="bi bi-graph-up-arrow"></i></div>
          <div>
            <div class="nilai-title">Fokus pada Perkembangan Anak</div>
            <p class="nilai-desc">Setiap langkah pembelajaran dirancang untuk mendorong potensi maksimal anak sesuai dengan kecepatan belajarnya masing-masing.</p>
          </div>
        </div>
        <div class="nilai-item">
          <div class="nilai-icon"><i class="bi bi-people"></i></div>
          <div>
            <div class="nilai-title">Tutor Berpengalaman</div>
            <p class="nilai-desc">Tim pengajar yang berdedikasi dengan pengalaman mendampingi siswa dari berbagai jenjang — TK hingga SMP.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>


<!-- KONSULTASI BANNER -->
<section class="konsultasi-section">
  <div class="container">
    <div class="konsultasi-box">
      <div>
        <div class="konsultasi-icon-wrap">💬</div>
        <h3 class="konsultasi-title">Konsultasi Tugas<br>Kapan Saja!</h3>
        <p class="konsultasi-desc">Bingung dengan tugas sekolah di luar jam bimbel? Siswa LIVO bisa berkonsultasi kapanpun — kami selalu siap membantu proses belajar tanpa batas waktu.</p>
      </div>
      <a href="https://wa.me/628118179511" target="_blank" class="btn-konsultasi">Hubungi Kami Sekarang &rarr;</a>
    </div>
  </div>
</section>

<!-- TESTIMONI -->
{{-- <section class="testimoni-section" id="testimoni">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-tag"><i class="bi bi-heart-fill"></i> Testimoni</div>
      <h2 class="section-title">Kata Mereka tentang <span>LIVO</span></h2>
    </div>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="testi-card">
          <div class="testi-quote">"</div>
          <div class="testi-stars">★★★★★</div>
          <p class="testi-text">Anak saya yang tadinya takut dengan pelajaran Matematika, kini justru semangat belajar. Metode di LIVO benar-benar berbeda — mengajarkan cara berpikir, bukan hanya rumus.</p>
          <div class="d-flex align-items-center gap-3">
            <div class="testi-avatar">RW</div>
            <div>
              <div class="testi-name">Ibu Ratna W.</div>
              <div class="testi-role">Orang tua siswa kelas 5 SD</div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="testi-card">
          <div class="testi-quote">"</div>
          <div class="testi-stars">★★★★★</div>
          <p class="testi-text">Nilai Bahasa Inggris anak kami naik drastis setelah ikut LIVO. Yang lebih membanggakan, dia sekarang sudah berani berbicara aktif di kelas. Terima kasih LIVO!</p>
          <div class="d-flex align-items-center gap-3">
            <div class="testi-avatar" style="background:#1340b0;">AH</div>
            <div>
              <div class="testi-name">Bapak Ahmad H.</div>
              <div class="testi-role">Orang tua siswa kelas 7 SMP</div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="testi-card">
          <div class="testi-quote">"</div>
          <div class="testi-stars">★★★★★</div>
          <p class="testi-text">Fitur konsultasi tugas kapanpun sangat membantu. Anak saya tidak perlu menunggu jadwal kelas untuk bertanya — tutornya responsif dan sabar dalam menjelaskan.</p>
          <div class="d-flex align-items-center gap-3">
            <div class="testi-avatar" style="background:#0e3a7a;">DS</div>
            <div>
              <div class="testi-name">Ibu Dewi S.</div>
              <div class="testi-role">Orang tua siswa kelas 3 SD</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section> --}}

<!-- LOKASI -->
<section class="lokasi-section" id="kontak">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-tag"><i class="bi bi-geo-alt-fill"></i> Lokasi</div>
      <h2 class="section-title">Temukan <span>Kami</span></h2>
    </div>
    <div class="row g-4 align-items-start">
      <div class="col-md-5">
        <div class="lokasi-info-card">
          <div class="lokasi-item">
            <div class="lokasi-item-icon"><i class="bi bi-geo-alt-fill"></i></div>
            <div>
              <div class="lokasi-item-label">Alamat</div>
              <div class="lokasi-item-value">Srengseng Sawah, Jakarta Selatan</div>
            </div>
          </div>
          <div class="lokasi-item">
            <div class="lokasi-item-icon"><i class="bi bi-clock-fill"></i></div>
            <div>
              <div class="lokasi-item-label">Jam Operasional</div>
              <div class="lokasi-item-value">Senin – Sabtu: 08.00 – 20.00 WIB</div>
            </div>
          </div>
          <div class="lokasi-item">
            <div class="lokasi-item-icon"><i class="bi bi-whatsapp"></i></div>
            <div>
              <div class="lokasi-item-label">WhatsApp</div>
              <div class="lokasi-item-value">+62 811-8179-511</div>
            </div>
          </div>
          <div class="lokasi-item">
            <div class="lokasi-item-icon"><i class="bi bi-envelope-fill"></i></div>
            <div>
              <div class="lokasi-item-label">Email</div>
              <div class="lokasi-item-value">info.center@livo.co.id</div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-7">
        <div style="border-radius: 20px; overflow: hidden; height: 100%; min-height: 360px;">
          <iframe 
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3965.5!2d106.83!3d-6.35!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e69edb523e657a7%3A0x46ca98ba9d6b1554!2sBimbel%20LIVO%20Cabang%20Shibi!5e0!3m2!1sid!2sid!4v1" 
            width="100%" 
            height="100%" 
            style="border:0; min-height: 360px;" 
            allowfullscreen="" 
            loading="lazy" 
            referrerpolicy="no-referrer-when-downgrade">
          </iframe>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection