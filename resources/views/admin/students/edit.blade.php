@extends('admin.layouts.app')

@section('title', 'Edit Siswa - LIVO Admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="mb-4">
            <a href="{{ route('admin.students.index') }}" class="btn btn-link link-secondary ps-0">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
            <h1 class="fs-3 mb-1 mt-2">Edit Data Siswa</h1>
            <p class="text-muted mb-0">Perbarui informasi profil siswa di bawah ini.</p>
        </div>
    </div>
</div>

<form action="{{ route('admin.students.update', $student->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="row g-4">
        <div class="col-md-8">
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Informasi Pribadi</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror" value="{{ old('full_name', $student->full_name) }}" required>
                            @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Panggilan</label>
                            <input type="text" name="nickname" class="form-control @error('nickname') is-invalid @enderror" value="{{ old('nickname', $student->nickname) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">NIS</label>
                            <input type="text" name="nis" class="form-control @error('nis') is-invalid @enderror" value="{{ old('nis', $student->nis) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="1" {{ old('status', $student->status) == 1 ? 'selected' : '' }}>Aktif</option>
                                <option value="2" {{ old('status', $student->status) == 2 ? 'selected' : '' }}>Non-Aktif</option>
                                <option value="3" {{ old('status', $student->status) == 3 ? 'selected' : '' }}>Cuti</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Program</label>
                            <input type="text" name="program" class="form-control @error('program') is-invalid @enderror" value="{{ old('program', $student->program) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kelas / Tingkat</label>
                            <input type="text" name="grade" class="form-control @error('grade') is-invalid @enderror" value="{{ old('grade', $student->grade) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sekolah</label>
                            <input type="text" name="school" class="form-control @error('school') is-invalid @enderror" value="{{ old('school', $student->school) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Kontak & Alamat</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">WhatsApp</label>
                            <input type="text" name="whatsapp" class="form-control @error('whatsapp') is-invalid @enderror" value="{{ old('whatsapp', $student->whatsapp) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $student->email) }}">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Alamat</label>
                            <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="3">{{ old('address', $student->address) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Kuota & Jadwal Belajar</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Kuota Sesi</label>
                            <input type="number" name="quota_sessions" min="0"
                                class="form-control @error('quota_sessions') is-invalid @enderror"
                                value="{{ old('quota_sessions', $student->quota_sessions ?? 0) }}">
                            @error('quota_sessions') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-8 d-flex align-items-end">
                            <p class="text-muted small mb-0">
                                Program: <strong>{{ $program->program_name ?? '-' }}</strong>
                                @if($maxSlots > 0)
                                    &mdash; maksimal <strong>{{ $maxSlots }}</strong> jadwal/minggu.
                                @endif
                            </p>
                        </div>

                        @php
                            // Opsi jadwal (hari — sesi) untuk dipakai ulang di tiap baris & template
                            $scheduleOptions = '';
                            foreach ($classSchedules as $cs) {
                                $time = $cs->session
                                    ? ' (' . \Illuminate\Support\Str::substr($cs->session->time_start, 0, 5) . ' - ' . \Illuminate\Support\Str::substr($cs->session->time_end, 0, 5) . ')'
                                    : '';
                                $scheduleOptions .= '<option value="' . $cs->id . '">' . e($cs->hari . ' — ' . ($cs->session->name ?? '-') . $time) . '</option>';
                            }
                            $selectedRows = old('class_schedule_ids', $selectedScheduleIds ?: []);
                        @endphp

                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label mb-0">Jadwal Belajar (Hari &amp; Sesi)</label>
                                <button type="button" id="btn-add-schedule" class="btn btn-outline-primary btn-sm" @disabled($classSchedules->isEmpty())>
                                    <i class="bi bi-plus-lg me-1"></i>Tambah Jadwal
                                </button>
                            </div>
                            <p class="text-muted small mb-2">Pilih hari &amp; sesi yang diambil siswa. Tambah atau hapus baris sesuai kebutuhan.</p>

                            @if($classSchedules->isEmpty())
                                <p class="text-muted small mb-0">Belum ada master jadwal untuk kelas siswa ini ({{ $student->class_type ?: $student->grade ?: '-' }}). Atur master jadwal terlebih dahulu.</p>
                            @else
                                <div id="schedule-rows" class="row g-2">
                                    @forelse($selectedRows as $sid)
                                        <div class="col-md-6 schedule-row">
                                            <div class="input-group">
                                                <select name="class_schedule_ids[]" class="form-select">
                                                    <option value="">-- Pilih Jadwal --</option>
                                                    @foreach($classSchedules as $cs)
                                                        <option value="{{ $cs->id }}" {{ $sid == $cs->id ? 'selected' : '' }}>
                                                            {{ $cs->hari }} — {{ $cs->session->name ?? '-' }}@if($cs->session) ({{ \Illuminate\Support\Str::substr($cs->session->time_start, 0, 5) }} - {{ \Illuminate\Support\Str::substr($cs->session->time_end, 0, 5) }})@endif
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button type="button" class="btn btn-outline-danger btn-remove-schedule" title="Hapus"><i class="bi bi-trash"></i></button>
                                            </div>
                                        </div>
                                    @empty
                                    @endforelse
                                </div>
                                @if($maxSlots > 0)
                                    <small class="text-muted">Program siswa umumnya <strong>{{ $maxSlots }}</strong> pertemuan/minggu (boleh disesuaikan).</small>
                                @endif
                                <template id="schedule-row-tpl">
                                    <div class="col-md-6 schedule-row">
                                        <div class="input-group">
                                            <select name="class_schedule_ids[]" class="form-select">
                                                <option value="">-- Pilih Jadwal --</option>
                                                {!! $scheduleOptions !!}
                                            </select>
                                            <button type="button" class="btn btn-outline-danger btn-remove-schedule" title="Hapus"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </div>
                                </template>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Foto Siswa</h5>
                </div>
                <div class="card-body text-center">
                    <img id="photo-preview"
                        src="{{ $student->photo ? asset('storage/' . $student->photo) : '' }}"
                        alt="Foto Siswa"
                        class="rounded mb-3 {{ $student->photo ? '' : 'd-none' }}"
                        style="width: 140px; height: 140px; object-fit: cover;">
                    @unless($student->photo)
                        <div id="photo-placeholder" class="rounded bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 140px; height: 140px;">
                            <i class="bi bi-image text-muted" style="font-size: 2.5rem;"></i>
                        </div>
                    @endunless
                    <input type="file" name="photo" id="photo-input"
                        class="form-control @error('photo') is-invalid @enderror" accept="image/*">
                    <small class="text-muted d-block mt-1">Semua tipe foto, maksimal 5 MB.</small>
                    @error('photo') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="card border-0 shadow-sm sticky-top" style="top: 2rem;">
                <div class="card-body py-4 text-center">
                    <i class="bi bi-person-check text-success" style="font-size: 3rem;"></i>
                    <h4 class="mt-3 mb-2">Simpan Perubahan</h4>
                    <p class="text-muted small">Klik tombol di bawah ini untuk memperbarui profil siswa.</p>
                    <button type="submit" class="btn btn-primary w-100 mt-2">
                        <i class="bi bi-check2-circle me-1"></i> Update Data Siswa
                    </button>
                    <a href="{{ route('admin.students.index') }}" class="btn btn-link link-secondary w-100 mt-2">Batal</a>
                </div>
            </div>
        </div>
    </div>
</form>

@push('js')
<script>
document.getElementById('photo-input')?.addEventListener('change', function (e) {
    var file = e.target.files[0];
    if (!file) return;
    var img = document.getElementById('photo-preview');
    var ph  = document.getElementById('photo-placeholder');
    img.src = URL.createObjectURL(file);
    img.classList.remove('d-none');
    if (ph) ph.classList.add('d-none');
});

/* ── Jadwal belajar: tambah / hapus baris (hari & sesi) ── */
(function () {
    var rows   = document.getElementById('schedule-rows');
    var tpl    = document.getElementById('schedule-row-tpl');
    var addBtn = document.getElementById('btn-add-schedule');
    if (!rows || !tpl || !addBtn) return;

    addBtn.addEventListener('click', function () {
        rows.appendChild(tpl.content.cloneNode(true));
    });

    rows.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-remove-schedule');
        if (btn) btn.closest('.schedule-row').remove();
    });

    // Bila belum ada baris tersimpan, sediakan satu baris kosong siap diisi
    if (!rows.querySelector('.schedule-row')) {
        rows.appendChild(tpl.content.cloneNode(true));
    }
})();
</script>
@endpush
@endsection
