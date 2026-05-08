@extends('website.layouts.app')

@section('content')
<div class="top_container">
  <section class="hero_section ">
    <div class="hero-container container">
      <div class="hero_detail-box pt-4">
        <h3>
          Wujudkan Potensi <br>
          Terbaik Anak Bersama
        </h3>
        <h1>
          Livo
        </h1>
        <p>
          Bimbingan Belajar Terpadu TK-SMP dengan Fokus Matematika & Bahasa Inggris. Mengembangkan kemampuan berpikir kritis dan kreatif.
        </p>
        <div class="hero_btn-continer">
          <a href="#contact" class="call_to-btn btn_white-border">
            <span>
              Daftar Sekarang
            </span>
            <img src="{{ asset('frontend/images/right-arrow.png') }}" alt="">
          </a>
        </div>
      </div>
      <div class="hero_img-container mt-4 pt-4">
        <div>
          <img src="{{ asset('frontend/images/hero.png') }}" alt="" class="img-fluid">
        </div>
      </div>
    </div>
  </section>
</div>
<section class="about_section layout_padding" id="about">
  <div class="container">
    <h2 class="main-heading ">
      Tentang LIVO
    </h2>
    <p class="text-center">
      LIVO merupakan lembaga pendidikan yang dibangun dengan mengintegrasikan beberapa konsep pembelajaran menjadi suatu sistem pembelajaran terpadu dan sistemastis untuk mengembangkan kemampuan berpikir anak menjadi lebih kritis dan kreatif dalam memahami berbagai materi pelajaran.
    </p>
    <div class="about_img-box ">
      <img src="{{ asset('frontend/images/kids.jpg') }}" alt="" class="img-fluid w-100">
    </div>
    <div class="d-flex justify-content-center mt-5 text-center">
      <p>
        Kami menyediakan layanan bimbingan belajar terpadu untuk SD-SMP, fokus pada matematika dan bahasa Inggris dengan metode berpikir kritis dan kreatif. Berlokasi di Jakarta Selatan (Srengseng Sawah), kami menawarkan konsultasi tugas sekolah dan pengajaran oleh tutor berpengalaman.
      </p>
    </div>
  </div>
</section>


<!-- about section -->

<!-- program section -->
<section class="teacher_section layout_padding-bottom" id="program">
  <div class="container">
    <h2 class="main-heading ">
      Program Unggulan Kami
    </h2>
    <p class="text-center">
      Fokus pada pengembangan akademis dan kemampuan berpikir siswa.
    </p>
    <div class="teacher_container layout_padding2">
      <div class="card-deck">
        <div class="card">
          <img class="card-img-top p-4" src="{{ asset('frontend/images/math_vector.png') }}" alt="Matematika">
          <div class="card-body">
            <h5 class="card-title">Matematika (TK-SMP)</h5>
            <p class="text-center">Pemahaman konsep mendalam untuk mengasah logika.</p>
          </div>
        </div>
        <div class="card">
          <img class="card-img-top p-4" src="{{ asset('frontend/images/english_vector.png') }}" alt="Bahasa Inggris">
          <div class="card-body">
            <h5 class="card-title">Bahasa Inggris (TK-SMP)</h5>
            <p class="text-center">Kemampuan komunikasi dan literasi internasional.</p>
          </div>
        </div>
        <div class="card">
          <img class="card-img-top p-4" src="{{ asset('frontend/images/consultation_vector.png') }}" alt="Konsultasi Tugas">
          <div class="card-body">
            <h5 class="card-title">Konsultasi Tugas</h5>
            <p class="text-center">Bantuan tugas sekolah kapanpun siswa membutuhkan.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- teacher section -->

<!-- keunggulan section -->
<section class="vehicle_section layout_padding" id="unggulan">
  <div class="container">
    <h2 class="main-heading ">
      Keunggulan LIVO
    </h2>
    <p class="text-center">
      Mengapa memilih Learning Innovation?
    </p>
    <div class="layout_padding2">
      <div class="row">
        <div class="col-md-4 text-center">
          <div class="p-4 border rounded shadow-sm h-100 bg-white">
            <h5 class="font-weight-bold">Fokus Akademik</h5>
            <p>Membantu pemahaman materi sekolah secara mendalam dan persiapan ujian yang matang.</p>
          </div>
        </div>
        <div class="col-md-4 text-center">
          <div class="p-4 border rounded shadow-sm h-100 bg-white">
            <h5 class="font-weight-bold">Pengembangan Berpikir</h5>
            <p>Mengintegrasikan konsep belajar untuk meningkatkan kemampuan berpikir kritis dan kreatif.</p>
          </div>
        </div>
        <div class="col-md-4 text-center">
          <div class="p-4 border rounded shadow-sm h-100 bg-white">
            <h5 class="font-weight-bold">Bimbingan Personal</h5>
            <p>Tutor membantu menyesuaikan kecepatan belajar anak secara individual.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>


<!-- vehicle section -->
<!-- client section -->
<section class="client_section layout_padding">
  <div class="container">
    <h2 class="main-heading ">
      Our Students Feedback
    </h2>
    <p class="text-center">
      There are many variations of passages of Lorem Ipsum available, but the majority hThere are many variations of
      passages of Lorem Ipsum available, but the majority h
    </p>
    <div class="layout_padding2">
      <div class="client_container d-flex flex-column">
        <div class="client_detail d-flex align-items-center">
          <div class="client_img-box ">
            <img src="{{ asset('frontend/images/student.png') }}" alt="">
          </div>
          <div class="client_detail-box">
            <h4>
              Veniam Quis
            </h4>
            <span>
              (exercitation)
            </span>
          </div>
        </div>
        <div class="client_text mt-4">
          <p>
            "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et
            dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex
            ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu
            fugiat
            nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit
            anim id est laborum."


          </p>
        </div>
      </div>
    </div>
  </div>
</section>




<!-- client section -->

<!-- contact section -->

  <section class="contact_section layout_padding-bottom" id="contact">
    <div class="container">

      <h2 class="main-heading">
        Hubungi Kami

      </h2>
      <p class="text-center">
        Berlokasi di Srengseng Sawah, Jakarta Selatan. Siap membantu potensi terbaik anak Anda.

      </p>
      <div class="">
        <div class="contact_section-container">
          <div class="row">
            <div class="col-md-6 mx-auto">
              <div class="contact-form">
                <form action="">
                  <div>
                    <input type="text" placeholder="Nama Orang Tua / Siswa">
                  </div>
                  <div>
                    <input type="text" placeholder="Nomor WhatsApp">
                  </div>
                  <div>
                    <input type="email" placeholder="Email">
                  </div>
                  <div>
                    <input type="text" placeholder="Pesan (Contoh: Ingin daftar Program SMP)" class="input_message">
                  </div>
                  <div class="d-flex justify-content-center">
                    <button type="submit" class="btn_on-hover">
                      Kirim Pesan
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>


<!-- end contact section -->

<!-- admission section -->
<section class="admission_section ">
  <div class="container-fluid position-relative">
    <div class="row h-100">
      <div id="map" class="h-100 w-100 ">
      </div>
      <div class="container">
        <div class="admission_container position-absolute">
          <div class="admission_img-box">
            <img src="{{ asset('frontend/images/kidss.jpg') }}" alt="">
          </div>
          <div class="admission_detail">
              <h3>
                Daftar Sekarang
              </h3>
              <p class="mt-3 mb-4">
                Bergabunglah dengan keluarga besar Learning Innovation (LIVO) dan rasakan transformasi cara belajar yang lebih cerdas.
              </p>
              <div class="">
                <a href="#contact" class="admission_btn btn_on-hover">
                  Daftar Lewat WhatsApp
                </a>
              </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</section>






<!-- admission section -->


<!-- landing section -->
<section class="landing_section layout_padding">
  <div class="container">
    <h2 class="main-heading">
      Free Multipurpose Responsive

    </h2>
    <h2 class="main-heading number_heading">
      Landing Page 2019

    </h2>
    <p class="landing_detail text-center">
      There are many variations of passages of Lorem Ipsum available, but the majority There are many variations of
      passages of Lorem Ipsum available, but the majority h

    </p>
  </div>
</section>

<!-- end landing section -->
@endsection

@push('js')
<script>
  // This example adds a marker to indicate the position of Bondi Beach in Sydney,
  // Australia.
  function initMap() {
    var map = new google.maps.Map(document.getElementById('map'), {
      zoom: 11,
      center: {
        lat: 40.645037,
        lng: -73.880224
      },
    });

    var image = '{{ asset('frontend/images/maps-and-flags.png') }}';
    var beachMarker = new google.maps.Marker({
      position: {
        lat: 40.645037,
        lng: -73.880224
      },
      map: map,
      icon: image
    });
  }
</script>
<!-- google map js -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA8eaHt9Dh5H57Zh0xVTqxVdBFCvFMqFjQ&callback=initMap">
</script>
<!-- end google map js -->
@endpush