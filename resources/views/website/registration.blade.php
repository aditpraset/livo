@extends('website.layouts.app')

@section('title', 'Formulir Pendaftaran Siswa Baru - LIVO Learning Innovation')
@section('meta_description', 'Daftar sekarang di LIVO Learning Innovation. Lengkapi formulir pendaftaran bimbingan belajar Matematika dan Bahasa Inggris untuk jenjang TK, SD, dan SMP di Jakarta Selatan.')
@section('meta_keywords', 'pendaftaran bimbel, daftar livo, formulir pendaftaran siswa, kursus matematika jakarta selatan, kursus bahasa inggris jakarta selatan, bimbel srengseng sawah')

@push('css')
<style>
    .reg-section {
        padding: 80px 0;
        background: var(--livo-gray);
    }
    .reg-card {
        background: #fff;
        border-radius: 24px;
        padding: 48px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.05);
    }
    .form-section-title {
        background: #e9ecef;
        padding: 10px 20px;
        border-radius: 8px;
        font-family: var(--font-display);
        font-weight: 800;
        font-size: 18px;
        color: var(--livo-dark);
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .form-label-livo {
        color: var(--livo-dark);
        font-weight: 700;
        font-size: 14px;
        margin-bottom: 8px;
    }
    .form-control-livo {
        background: #f8f9fa;
        border: 1.5px solid #e9ecef;
        color: var(--livo-text);
        border-radius: 10px;
        padding: 12px 16px;
        width: 100%;
        transition: all 0.2s;
    }
    .form-control-livo:focus {
        background: #fff;
        border-color: var(--livo-blue);
        outline: none;
        box-shadow: 0 0 0 4px rgba(26, 79, 214, 0.1);
    }
    .btn-reg-submit {
        background: var(--livo-blue);
        color: #fff;
        font-weight: 800;
        padding: 16px 40px;
        border-radius: 12px;
        border: none;
        transition: all 0.2s;
        width: 100%;
        font-size: 18px;
    }
    .btn-reg-submit:hover {
        background: var(--livo-blue-dark);
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(26, 79, 214, 0.2);
    }
    .reg-header {
        text-align: center;
        margin-bottom: 50px;
    }
    .reg-header h1 {
        font-family: var(--font-display);
        font-weight: 900;
        font-size: 36px;
        color: var(--livo-dark);
    }
    .reg-header p {
        color: var(--livo-muted);
        font-size: 16px;
    }
</style>
@endpush

@section('content')
<section class="reg-section">
    <div class="container">
        <div class="reg-header">
            <div class="section-tag"><i class="bi bi-pencil-square"></i> Registration</div>
            <h1>Formulir Pendaftaran Siswa</h1>
            <p>Silakan lengkapi data di bawah ini untuk mendaftarkan putra/putri Anda di LIVO.</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show mb-4 border-0 rounded-4 p-4" role="alert">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-check-circle-fill fs-2"></i>
                            <div>
                                <h5 class="alert-heading mb-1 fw-bold">Berhasil!</h5>
                                {{ session('success') }}
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form action="{{ route('registration.store') }}" method="POST" class="reg-card">
                    @csrf
                    
                    <!-- Informasi Siswa -->
                    <div class="form-section-title">
                        <span>Informasi Siswa</span>
                        <div class="d-flex align-items-center gap-2" style="font-size: 14px; font-weight: normal;">
                            <span>Tanggal:</span>
                            <input type="date" name="registration_date" value="{{ date('Y-m-d') }}" class="form-control-livo py-1 px-2" style="width: auto; border: none; background: transparent;">
                        </div>
                    </div>
                    <div class="row g-4 mb-5">
                        <div class="col-md-8">
                            <label class="form-label-livo">Nama Lengkap</label>
                            <input type="text" name="full_name" class="form-control-livo" placeholder="Masukkan nama lengkap siswa" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-livo">Nama Panggilan</label>
                            <input type="text" name="nickname" class="form-control-livo" placeholder="Nama panggilan">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-livo">Tanggal Lahir</label>
                            <input type="date" name="birth_date" class="form-control-livo">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-livo">Agama</label>
                            <select name="religion" class="form-control-livo">
                                <option value="">-- Pilih Agama --</option>
                                <option>Islam</option>
                                <option>Kristen</option>
                                <option>Katolik</option>
                                <option>Hindu</option>
                                <option>Budha</option>
                                <option>Konghucu</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-livo">Jenis Kelamin</label>
                            <select name="gender" class="form-control-livo">
                                <option value="">-- Pilih --</option>
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-livo">Kelas</label>
                            <select class="form-control-livo" name="grade">
                                <option value="">-- Pilih Kelas --</option>
                                <option value="TK">TK</option>
                                <option value="SD Kelas 1">SD Kelas 1</option>
                                <option value="SD Kelas 2">SD Kelas 2</option>
                                <option value="SD Kelas 3">SD Kelas 3</option>
                                <option value="SD Kelas 4">SD Kelas 4</option>
                                <option value="SD Kelas 5">SD Kelas 5</option>
                                <option value="SD Kelas 6">SD Kelas 6</option>
                                <option value="SMP Kelas 7">SMP Kelas 7</option>
                                <option value="SMP Kelas 8">SMP Kelas 8</option>
                                <option value="SMP Kelas 9">SMP Kelas 9</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-livo">Asal Sekolah</label>
                            <input type="text" name="school_origin" class="form-control-livo" placeholder="Nama sekolah saat ini">
                        </div>
                    </div>

                    <!-- Informasi Orangtua -->
                    <div class="form-section-title">
                        <span>Informasi Orangtua / Wali Murid</span>
                    </div>
                    <div class="row g-4 mb-5">
                        <div class="col-md-4">
                            <label class="form-label-livo">Nama Ayah</label>
                            <input type="text" name="father_name" class="form-control-livo" placeholder="Nama lengkap ayah">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-livo">Nama Ibu</label>
                            <input type="text" name="mother_name" class="form-control-livo" placeholder="Nama lengkap ibu">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-livo">Nama Wali (Opsional)</label>
                            <input type="text" name="guardian_name" class="form-control-livo" placeholder="Nama wali jika ada">
                        </div>
                        <div class="col-12">
                            <label class="form-label-livo">Alamat Lengkap</label>
                            <textarea name="address" class="form-control-livo" rows="3" placeholder="Masukkan alamat lengkap rumah"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-livo">Alamat Email</label>
                            <input type="email" name="email" class="form-control-livo" placeholder="example@email.com">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-livo">Nomor Tlp / HP</label>
                            <input type="tel" name="phone" class="form-control-livo" placeholder="08xx-xxxx-xxxx">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-livo">Nomor WhatsApp</label>
                            <input type="tel" name="whatsapp" class="form-control-livo" placeholder="08xx-xxxx-xxxx">
                        </div>
                    </div>

                    <!-- Data Pilihan Program -->
                    <div class="form-section-title">
                        <span>Data Pilihan Program</span>
                    </div>
                    <div class="row g-4 mb-5">
                        <div class="col-md-4">
                            <label class="form-label-livo">Kelas / Jenjang</label>
                            <select class="form-control-livo" name="class_type">
                                <option value="">-- Pilih Kelas --</option>
                                <option value="TK">TK</option>
                                <option value="SD Kelas 1">SD Kelas 1</option>
                                <option value="SD Kelas 2">SD Kelas 2</option>
                                <option value="SD Kelas 3">SD Kelas 3</option>
                                <option value="SD Kelas 4">SD Kelas 4</option>
                                <option value="SD Kelas 5">SD Kelas 5</option>
                                <option value="SD Kelas 6">SD Kelas 6</option>
                                <option value="SMP Kelas 7">SMP Kelas 7</option>
                                <option value="SMP Kelas 8">SMP Kelas 8</option>
                                <option value="SMP Kelas 9">SMP Kelas 9</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-livo">Pilihan Proses KBM</label>
                            <select name="kbm_process" class="form-control-livo">
                                <option value="">-- Pilih --</option>
                                <option value="Offline (Di Livo)">Offline (Di Livo)</option>
                                <option value="Home Visit (Guru ke Rumah)">Home Visit (Guru ke Rumah)</option>
                                <option value="Online">Online</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-livo">Paket</label>
                            <input type="text" name="package" class="form-control-livo" placeholder="Masukkan nama paket">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-livo">Program</label>
                            <select name="program" class="form-control-livo">
                                <option value="">-- Pilih Program --</option>
                                <option>Matematika</option>
                                <option>Bahasa Inggris</option>
                                <option>Keduanya (Math & English)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-livo">Pilihan Hari</label>
                            <input type="text" name="selected_days" class="form-control-livo" placeholder="Contoh: Senin & Kamis">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-livo">Sesi Belajar</label>
                            <input type="text" name="study_session" class="form-control-livo" placeholder="Contoh: 14.00 - 15.30">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-livo">Kurikulum Sekolah</label>
                            <input type="text" name="school_curriculum" class="form-control-livo" placeholder="Contoh: Kurikulum Merdeka / Cambridge">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label-livo">Materi Pembelajaran</label>
                            <input type="text" name="learning_material" class="form-control-livo" placeholder="Materi spesifik yang ingin dipelajari">
                        </div>
                    </div>

                    <!-- Informasi Pendaftaran & Promo -->
                    <div class="form-section-title">
                        <span>Informasi Pendaftaran & Promo</span>
                    </div>
                    <div class="row g-4 mb-5">
                        <div class="col-md-4">
                            <label class="form-label-livo">Kode Promo (Jika ada)</label>
                            <input type="text" name="promo_code" class="form-control-livo" placeholder="Masukkan kode promo">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-livo">Informasi Pendaftaran</label>
                            <select name="registration_info" class="form-control-livo">
                                <option value="">-- Tahu Livo dari mana? --</option>
                                <option>Instagram</option>
                                <option>Facebook</option>
                                <option>Teman / Saudara</option>
                                <option>Brosur</option>
                                <option>Spanduk / Banner</option>
                                <option>Lainnya</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-livo">PIC Marketing</label>
                            <input type="text" name="marketing_pic" class="form-control-livo" placeholder="Nama petugas pendaftaran">
                        </div>
                    </div>


                    <!-- reCAPTCHA -->
                    {{-- <div class="mb-4 d-flex flex-column align-items-center">
                        <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
                        @error('g-recaptcha-response')
                            <small class="text-danger mt-2"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</small>
                        @enderror
                    </div> --}}

                    <div class="text-center">
                        <button type="submit" class="btn-reg-submit">
                            Kirim Formulir Pendaftaran <i class="bi bi-send-fill ms-2"></i>
                        </button>
                        <p class="mt-3 text-muted" style="font-size: 14px;">Dengan mengirim formulir ini, Anda menyetujui syarat & ketentuan pendaftaran LIVO.</p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

@push('js')
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endpush
@endsection
