@extends('tutor.layouts.app')

@section('title', 'Isi Evaluasi - LIVO Tutor')

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<style>
    .select2-container--bootstrap-5 .select2-selection { min-height: calc(1.5em + 0.75rem + 2px); }
    .select2-container .select2-dropdown { z-index: 1100; }
</style>
@endpush

@section('content')
@php($ev = $schedule->evaluation)
<div class="mb-4">
    <a href="{{ route('tutor.evaluations.index') }}" class="btn btn-link link-secondary ps-0"><i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar</a>
    <h1 class="fs-3 mb-1 mt-2">{{ $ev ? 'Edit Evaluasi Sesi' : 'Isi Evaluasi Sesi' }}</h1>
    <p class="text-muted mb-0">
        {{ $schedule->student->full_name ?? '-' }} · {{ $schedule->subject->subject_name ?? '-' }} ·
        {{ $schedule->class_date->translatedFormat('d M Y') }} ({{ substr($schedule->start_time, 0, 5) }}–{{ substr($schedule->end_time, 0, 5) }})
    </p>
</div>

<form action="{{ route('tutor.evaluations.store', $schedule->id) }}" method="POST" id="eval-form">
    @csrf
    <div class="row g-4">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-white"><h3 class="card-title fw-bold mb-0">Materi & Kehadiran</h3></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Materi dari Silabus</label>
                            <select name="syllabus_id" id="syllabus-select" class="form-select">
                                <option value="">-- Pilih dari silabus (opsional) --</option>
                                @foreach($syllabi as $syl)
                                    <option value="{{ $syl->id }}" {{ old('syllabus_id', $ev->syllabus_id ?? '') == $syl->id ? 'selected' : '' }}>
                                        {{ $syl->pokok_bahasan }}{{ $syl->sub_pokok_bahasan ? ' — ' . $syl->sub_pokok_bahasan : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Kosongkan bila materi tidak ada di silabus.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Materi Manual (Lainnya)</label>
                            <input type="text" name="materi_manual" class="form-control" value="{{ old('materi_manual', $ev->materi_manual ?? '') }}" placeholder="Isi bila tidak memilih silabus">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kehadiran Siswa <span class="text-danger">*</span></label>
                            <select name="student_attendance" class="form-select" required>
                                @foreach(['hadir' => 'Hadir', 'izin' => 'Izin', 'alfa' => 'Alfa'] as $val => $label)
                                    <option value="{{ $val }}" {{ old('student_attendance', $ev->student_attendance ?? 'hadir') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Hadir/Alfa memotong kuota sesi siswa, Izin tidak.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nilai Post Test (1–100)</label>
                            <input type="number" name="post_test" min="1" max="100" class="form-control" value="{{ old('post_test', $ev->post_test ?? '') }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white"><h3 class="card-title fw-bold mb-0">Penilaian Aspek (1–100)</h3></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Pemahaman</label>
                            <input type="number" name="pemahaman" min="1" max="100" class="form-control" value="{{ old('pemahaman', $ev->pemahaman ?? '') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Kemampuan Analisa</label>
                            <input type="number" name="kemampuan_analisa" min="1" max="100" class="form-control" value="{{ old('kemampuan_analisa', $ev->kemampuan_analisa ?? '') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Kemampuan Hafalan</label>
                            <input type="number" name="kemampuan_hafalan" min="1" max="100" class="form-control" value="{{ old('kemampuan_hafalan', $ev->kemampuan_hafalan ?? '') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Kepercayaan Diri</label>
                            <input type="number" name="kepercayaan_diri" min="1" max="100" class="form-control" value="{{ old('kepercayaan_diri', $ev->kepercayaan_diri ?? '') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan Tutor</label>
                            <textarea name="tutor_notes" class="form-control" rows="3" maxlength="1000" placeholder="Catatan perkembangan siswa, kendala, atau rekomendasi...">{{ old('tutor_notes', $ev->tutor_notes ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center py-4">
                    <i class="bi bi-clipboard-check text-primary" style="font-size: 3rem;"></i>
                    <h4 class="mt-3 mb-2">Simpan Evaluasi</h4>
                    <p class="text-muted small">Pastikan penilaian sudah benar sebelum menyimpan.</p>
                    <button type="submit" class="btn btn-primary w-100 mt-2">
                        <i class="bi bi-check-lg me-1"></i> Simpan Evaluasi
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function () {
    // Silabus: dropdown dengan fitur pencarian (Select2)
    $('#syllabus-select').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '-- Pilih dari silabus (opsional) --',
        allowClear: true
    });

    // Cegah Enter menekan submit form secara tidak sengaja.
    // Textarea tetap boleh Enter untuk baris baru.
    $('#eval-form').on('keydown', 'input, select', function (e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
