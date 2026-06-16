@extends('admin.layouts.app')

@section('title', 'Tambah Siswa - LIVO Admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="mb-4">
            <a href="{{ route('admin.students.index') }}" class="btn btn-link link-secondary ps-0">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
            <h1 class="fs-3 mb-1 mt-2">Tambah Data Siswa</h1>
            <p class="text-muted mb-0">Lengkapi formulir di bawah ini untuk menambahkan siswa baru.</p>
        </div>
    </div>
</div>

<form action="{{ route('admin.students.store') }}" method="POST">
    @csrf

    {{-- ── Informasi Siswa ── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Informasi Siswa</h5>
            <div class="d-flex align-items-center gap-2 small">
                <span class="text-muted">Tanggal Daftar:</span>
                <input type="date" name="registration_date" value="{{ old('registration_date', date('Y-m-d')) }}" class="form-control form-control-sm" style="width:auto;">
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror" value="{{ old('full_name') }}" placeholder="Nama lengkap siswa" required>
                    @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Nama Panggilan</label>
                    <input type="text" name="nickname" class="form-control" value="{{ old('nickname') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">NIS (jika ada)</label>
                    <input type="text" name="nis" class="form-control @error('nis') is-invalid @enderror" value="{{ old('nis') }}">
                    @error('nis') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal Lahir</label>
                    <input type="date" name="birth_date" class="form-control" value="{{ old('birth_date') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Agama</label>
                    <select name="religion" class="form-select">
                        <option value="">-- Pilih Agama --</option>
                        @foreach(['Islam','Kristen','Katolik','Hindu','Budha','Konghucu'] as $r)
                            <option value="{{ $r }}" {{ old('religion') == $r ? 'selected' : '' }}>{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Jenis Kelamin</label>
                    <select name="gender" class="form-select">
                        <option value="">-- Pilih --</option>
                        <option value="Laki-laki" {{ old('gender') == 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="Perempuan" {{ old('gender') == 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Kelas</label>
                    <select name="grade" class="form-select">
                        <option value="">-- Pilih Kelas --</option>
                        @foreach(['TK','SD Kelas 1','SD Kelas 2','SD Kelas 3','SD Kelas 4','SD Kelas 5','SD Kelas 6','SMP Kelas 7','SMP Kelas 8','SMP Kelas 9','SMA Kelas 10','SMA Kelas 11','SMA Kelas 12'] as $g)
                            <option value="{{ $g }}" {{ old('grade') == $g ? 'selected' : '' }}>{{ $g }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Asal Sekolah</label>
                    <input type="text" name="school_origin" class="form-control" value="{{ old('school_origin') }}" placeholder="Nama sekolah saat ini">
                </div>
            </div>
        </div>
    </div>

    {{-- ── Informasi Orangtua / Wali ── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white"><h5 class="mb-0">Informasi Orangtua / Wali Murid</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Nama Ayah</label>
                    <input type="text" name="father_name" class="form-control" value="{{ old('father_name') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nama Ibu</label>
                    <input type="text" name="mother_name" class="form-control" value="{{ old('mother_name') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nama Wali (opsional)</label>
                    <input type="text" name="guardian_name" class="form-control" value="{{ old('guardian_name') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Alamat Lengkap</label>
                    <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">No. Telp / HP</label>
                    <input type="tel" name="phone" class="form-control" value="{{ old('phone') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">No. WhatsApp</label>
                    <input type="tel" name="whatsapp" class="form-control" value="{{ old('whatsapp') }}">
                </div>
            </div>
        </div>
    </div>

    {{-- ── Data Pilihan Program ── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white"><h5 class="mb-0">Data Pilihan Program</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Kelas / Jenjang</label>
                    <select name="class_type" class="form-select">
                        <option value="">-- Pilih Kelas --</option>
                        @foreach(['TK','SD Kelas 1','SD Kelas 2','SD Kelas 3','SD Kelas 4','SD Kelas 5','SD Kelas 6','SMP Kelas 7','SMP Kelas 8','SMP Kelas 9','SMA Kelas 10','SMA Kelas 11','SMA Kelas 12'] as $c)
                            <option value="{{ $c }}" {{ old('class_type') == $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Proses KBM</label>
                    <select name="kbm_process" class="form-select">
                        <option value="">-- Pilih --</option>
                        @foreach(['Offline (Di Livo)','Home Visit (Guru ke Rumah)','Online'] as $k)
                            <option value="{{ $k }}" {{ old('kbm_process') == $k ? 'selected' : '' }}>{{ $k }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Paket Belajar</label>
                    <select name="package_id" id="reg-package" class="form-select">
                        <option value="">-- Pilih Paket --</option>
                        @foreach($packages as $pkg)
                            <option value="{{ $pkg->id }}" data-price="{{ $pkg->price }}" data-sessions="{{ $pkg->total_sessions }}" {{ old('package_id') == $pkg->id ? 'selected' : '' }}>
                                {{ $pkg->package_name }} — {{ $pkg->total_sessions }} sesi
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Program / Mata Pelajaran yang Dipilih</label>
                    <div class="d-flex flex-wrap gap-3 mt-1">
                        @forelse($subjects as $subject)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="program[]" value="{{ $subject->id }}" id="subj-{{ $subject->id }}"
                                    {{ collect(old('program', []))->contains($subject->id) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="subj-{{ $subject->id }}">{{ $subject->subject_name }}</label>
                            </div>
                        @empty
                            <p class="text-muted small mb-0">Belum ada mata pelajaran tersedia.</p>
                        @endforelse
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Pilihan Hari</label>
                    <select name="selected_days" class="form-select">
                        <option value="">-- Pilih Hari --</option>
                        @foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'] as $d)
                            <option value="{{ $d }}" {{ old('selected_days') == $d ? 'selected' : '' }}>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sesi Belajar</label>
                    <select name="schedule_session_id" class="form-select">
                        <option value="">-- Pilih Sesi --</option>
                        @foreach($sessions as $session)
                            <option value="{{ $session->id }}" {{ old('schedule_session_id') == $session->id ? 'selected' : '' }}>
                                {{ $session->name }} ({{ date('H:i', strtotime($session->time_start)) }} - {{ date('H:i', strtotime($session->time_end)) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kurikulum Sekolah</label>
                    <input type="text" name="school_curriculum" class="form-control" value="{{ old('school_curriculum') }}" placeholder="cth: Kurikulum Merdeka">
                </div>
                <div class="col-12">
                    <label class="form-label">Materi Pembelajaran</label>
                    <input type="text" name="learning_material" class="form-control" value="{{ old('learning_material') }}" placeholder="Materi spesifik yang ingin dipelajari">
                </div>
            </div>
        </div>
    </div>

    {{-- ── Informasi Pendaftaran ── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white"><h5 class="mb-0">Informasi Pendaftaran</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Informasi Pendaftaran</label>
                    <select name="registration_info" class="form-select">
                        <option value="">-- Tahu Livo dari mana? --</option>
                        @foreach(['Instagram','Facebook','Teman / Saudara','Brosur','Spanduk / Banner','Lainnya'] as $i)
                            <option value="{{ $i }}" {{ old('registration_info') == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">PIC Marketing</label>
                    <input type="text" name="marketing_pic" class="form-control" value="{{ old('marketing_pic') }}" placeholder="Nama petugas pendaftaran">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status Siswa <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="1" {{ old('status', 1) == 1 ? 'selected' : '' }}>Aktif</option>
                        <option value="2" {{ old('status') == 2 ? 'selected' : '' }}>Non-Aktif</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mb-5">
        <a href="{{ route('admin.students.index') }}" class="btn btn-link link-secondary">Batal</a>
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check2-circle me-1"></i> Simpan Data Siswa
        </button>
    </div>
</form>
@endsection
