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

@if($errors->any())
    <div class="alert alert-danger d-flex align-items-start gap-2 shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle-fill fs-5 mt-1"></i>
        <div>
            <div class="fw-semibold mb-1">Beberapa data wajib belum lengkap. Mohon periksa kembali:</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

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
                    <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                    <input type="date" name="birth_date" class="form-control @error('birth_date') is-invalid @enderror" value="{{ old('birth_date') }}">
                    @error('birth_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                    <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                    <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                        <option value="">-- Pilih --</option>
                        <option value="Laki-laki" {{ old('gender') == 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="Perempuan" {{ old('gender') == 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                    @error('gender') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Kelas <span class="text-danger">*</span></label>
                    <select name="grade" id="reg-kelas" class="form-select @error('grade') is-invalid @enderror">
                        <option value="">-- Pilih Kelas --</option>
                        @foreach(['TK','SD Kelas 1','SD Kelas 2','SD Kelas 3','SD Kelas 4','SD Kelas 5','SD Kelas 6','SMP Kelas 7','SMP Kelas 8','SMP Kelas 9','SMA Kelas 10','SMA Kelas 11','SMA Kelas 12'] as $g)
                            <option value="{{ $g }}" {{ old('grade') == $g ? 'selected' : '' }}>{{ $g }}</option>
                        @endforeach
                    </select>
                    @error('grade') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Asal Sekolah <span class="text-danger">*</span></label>
                    <input type="text" name="school_origin" class="form-control @error('school_origin') is-invalid @enderror" value="{{ old('school_origin') }}" placeholder="Nama sekolah saat ini">
                    @error('school_origin') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
                    <label class="form-label">Nama Ayah <span class="text-danger">*</span></label>
                    <input type="text" name="father_name" class="form-control @error('father_name') is-invalid @enderror" value="{{ old('father_name') }}">
                    @error('father_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <small class="text-muted">Minimal salah satu dari Ayah/Ibu/Wali diisi.</small>
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
                    <label class="form-label">No. Telp / HP <span class="text-danger">*</span></label>
                    <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}">
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <small class="text-muted">Isi Telp/HP atau WhatsApp.</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">No. WhatsApp <span class="text-danger">*</span></label>
                    <input type="tel" name="whatsapp" class="form-control @error('whatsapp') is-invalid @enderror" value="{{ old('whatsapp') }}">
                    @error('whatsapp') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>

    {{-- ── Data Pilihan Program ── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white"><h5 class="mb-0">Data Pilihan Program</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Pilihan Proses KBM</label>
                    <select name="kbm_process" class="form-select">
                        <option value="">-- Pilih --</option>
                        @foreach(['Offline (Di Livo)','Home Visit (Guru ke Rumah)','Online'] as $k)
                            <option value="{{ $k }}" {{ old('kbm_process') == $k ? 'selected' : '' }}>{{ $k }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Program Belajar <span class="text-danger">*</span></label>
                    <select name="program_id" id="reg-program" class="form-select @error('program_id') is-invalid @enderror">
                        <option value="">-- Pilih Program --</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}" data-duration="{{ $program->duration }}" {{ old('program_id') == $program->id ? 'selected' : '' }}>{{ $program->program_name }}</option>
                        @endforeach
                    </select>
                    @error('program_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Jenjang <span class="text-danger">*</span></label>
                    <select name="grade_id" id="reg-grade" class="form-select @error('grade_id') is-invalid @enderror">
                        <option value="">-- Pilih Jenjang --</option>
                        @foreach($grades as $grade)
                            <option value="{{ $grade->id }}" {{ old('grade_id') == $grade->id ? 'selected' : '' }}>{{ $grade->grade_name }}</option>
                        @endforeach
                    </select>
                    @error('grade_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Durasi <span class="text-danger">*</span></label>
                    <select name="duration" id="reg-duration" class="form-select @error('duration') is-invalid @enderror">
                        <option value="">-- Pilih Durasi --</option>
                        @foreach([1,3,6,12] as $d)
                            <option value="{{ $d }}" {{ old('duration') == $d ? 'selected' : '' }}>{{ $d }} Bulan</option>
                        @endforeach
                    </select>
                    @error('duration') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Jenis Kelas (Paket) <span class="text-danger">*</span></label>
                    <select name="package_id" id="reg-package" class="form-select @error('package_id') is-invalid @enderror">
                        <option value="">-- Pilih Paket --</option>
                        @foreach($packages as $pkg)
                            <option value="{{ $pkg->id }}" {{ old('package_id') == $pkg->id ? 'selected' : '' }}>{{ $pkg->package_name }}</option>
                        @endforeach
                    </select>
                    @error('package_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Program / Mata Pelajaran yang Dipilih <span class="text-danger">*</span></label>
                    <div class="d-flex flex-wrap gap-3 mt-1" id="subject-list">
                        @foreach($subjects as $subject)
                            <div class="form-check subject-item" style="min-width: 160px;" data-grades="{{ json_encode($subject->grade_ids ?? []) }}">
                                <input class="form-check-input" type="checkbox" name="program[]" value="{{ $subject->id }}" id="subj-{{ $subject->id }}"
                                    {{ collect(old('program', []))->contains($subject->id) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="subj-{{ $subject->id }}">{{ $subject->subject_name }}</label>
                            </div>
                        @endforeach
                        @if($subjects->isEmpty())
                            <p class="text-muted small mb-0">Belum ada mata pelajaran tersedia.</p>
                        @endif
                    </div>
                    @error('program') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    <small class="text-muted" id="subject-hint">Pilih jenjang terlebih dahulu untuk menampilkan mata pelajaran yang sesuai.</small>
                </div>
                <div class="col-12">
                    <label class="form-label">Pilihan Jadwal <span class="text-danger">*</span></label>
                    @error('class_schedule_ids') <div class="text-danger small mb-1">{{ $message }}</div> @enderror
                    <div id="schedule-hint" class="small text-muted mb-2" style="display:none;"></div>
                    <div id="schedule-container" class="row g-3">
                        <div class="col-12">
                            <p class="text-muted small mb-0">Pilih Kelas & Program terlebih dahulu.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Kurikulum Sekolah</label>
                    <select name="school_curriculum" class="form-select">
                        <option value="">-- Pilih Kurikulum --</option>
                        @foreach(['Kurikulum Merdeka','Kurikulum 2013','Kurikulum Nasional Plus','Internasional'] as $kur)
                            <option value="{{ $kur }}" {{ old('school_curriculum') == $kur ? 'selected' : '' }}>{{ $kur }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Catatan Khusus</label>
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
                    <label class="form-label">Kode Promo (jika ada)</label>
                    <input type="text" name="promo_code" class="form-control text-uppercase" value="{{ old('promo_code') }}" placeholder="cth: HEMAT50">
                </div>
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
                        <option value="3" {{ old('status') == 3 ? 'selected' : '' }}>Cuti</option>
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

@push('js')
<script>
(function () {
    var classSchedules = @json($classSchedules);
    var oldSchedules   = @json(old('class_schedule_ids', []));

    var programSelect = document.getElementById('reg-program');
    var classSelect   = document.getElementById('reg-kelas');   // dropdown "Kelas" (teks) → filter jadwal
    var gradeSelect   = document.getElementById('reg-grade');   // dropdown "Jenjang" (master) → filter mapel
    var scheduleBox   = document.getElementById('schedule-container');
    var scheduleHint  = document.getElementById('schedule-hint');
    var subjectHint   = document.getElementById('subject-hint');

    /* ---- Mata pelajaran tampil sesuai jenjang yang dipilih ---- */
    function filterSubjectsByGrade() {
        var gradeId = gradeSelect ? parseInt(gradeSelect.value) : NaN;
        var items   = document.querySelectorAll('#subject-list .subject-item');
        var shown   = 0;
        items.forEach(function (item) {
            var grades = [];
            try { grades = JSON.parse(item.getAttribute('data-grades') || '[]'); } catch (e) { grades = []; }
            var match = !isNaN(gradeId) && grades.map(Number).indexOf(gradeId) !== -1;
            item.style.display = match ? '' : 'none';
            if (!match) {
                var cb = item.querySelector('input[type="checkbox"]');
                if (cb) cb.checked = false;
            } else { shown++; }
        });
        if (subjectHint) {
            if (isNaN(gradeId)) {
                subjectHint.textContent = 'Pilih jenjang terlebih dahulu untuk menampilkan mata pelajaran yang sesuai.';
                subjectHint.style.display = '';
            } else if (shown === 0) {
                subjectHint.textContent = 'Belum ada mata pelajaran untuk jenjang ini.';
                subjectHint.style.display = '';
            } else {
                subjectHint.style.display = 'none';
            }
        }
    }

    /* ---- Jadwal: jumlah pilihan mengikuti durasi (x per minggu) program ---- */
    function scheduleOptionsHtml(selectedKelas, selectedId) {
        var list = classSchedules.filter(function (s) { return s.kelas === selectedKelas; });
        var html = '<option value="">-- Pilih Jadwal --</option>';
        list.forEach(function (s) {
            var sel = (String(s.id) === String(selectedId)) ? ' selected' : '';
            html += '<option value="' + s.id + '"' + sel + '>' + s.hari_label + ' — ' + s.session_name +
                    (s.session_time ? ' (' + s.session_time + ')' : '') + '</option>';
        });
        return html;
    }

    function renderSchedules() {
        scheduleBox.innerHTML = '';
        scheduleHint.style.display = 'none';

        var kelas = classSelect ? classSelect.value : '';
        if (!kelas) {
            scheduleBox.innerHTML = '<div class="col-12"><p class="text-muted small mb-0">Pilih Kelas terlebih dahulu.</p></div>';
            return;
        }
        var opt      = programSelect.options[programSelect.selectedIndex];
        var duration = programSelect.value ? (parseInt(opt.getAttribute('data-duration')) || 0) : 0;
        if (!programSelect.value || duration < 1) {
            scheduleBox.innerHTML = '<div class="col-12"><p class="text-muted small mb-0">Pilih Program Belajar terlebih dahulu.</p></div>';
            return;
        }
        var available = classSchedules.filter(function (s) { return s.kelas === kelas; });
        if (available.length === 0) {
            scheduleBox.innerHTML = '<div class="col-12"><p class="text-danger small mb-0">Belum ada jadwal untuk kelas ini.</p></div>';
            return;
        }
        scheduleHint.textContent = 'Program ini ' + duration + 'x per minggu. Silakan pilih ' + duration + ' jadwal pertemuan.';
        scheduleHint.style.display = 'block';

        for (var i = 0; i < duration; i++) {
            var col = document.createElement('div');
            col.className = 'col-md-6';
            col.innerHTML =
                '<label class="form-label">Pertemuan ' + (i + 1) + '</label>' +
                '<select name="class_schedule_ids[]" class="form-select sch-select">' + scheduleOptionsHtml(kelas, oldSchedules[i] || '') + '</select>';
            scheduleBox.appendChild(col);
        }
    }

    // Pakai event jQuery karena <select> di admin diubah menjadi Select2,
    // yang memicu event "change" via jQuery (tidak tertangkap addEventListener native).
    $('#reg-grade').on('change', filterSubjectsByGrade);
    $('#reg-program, #reg-kelas').on('change', renderSchedules);

    filterSubjectsByGrade();
    renderSchedules();
})();
</script>
@endpush
