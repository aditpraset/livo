@extends('tutor.layouts.app')

@section('title', 'Detail Siswa - LIVO Tutor')

@section('content')
<div class="mb-4">
    <a href="{{ url()->previous() }}" class="btn btn-link link-secondary ps-0"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
    <h1 class="fs-3 mb-1 mt-2">Detail Siswa: {{ $student->full_name }}</h1>
    <p class="text-muted mb-0">Riwayat belajar siswa bersama Anda.</p>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-body">
                <h3 class="card-title fw-bold">Informasi Siswa</h3>
                <dl class="row mb-0 small">
                    <dt class="col-5">NIS</dt><dd class="col-7">{{ $student->nis ?: '-' }}</dd>
                    <dt class="col-5">Nama Panggilan</dt><dd class="col-7">{{ $student->nickname ?: '-' }}</dd>
                    <dt class="col-5">Jenjang/Kelas</dt><dd class="col-7">{{ $student->grade ?: '-' }}</dd>
                    <dt class="col-5">Asal Sekolah</dt><dd class="col-7">{{ $student->school_origin ?: '-' }}</dd>
                    <dt class="col-5">Program</dt><dd class="col-7">{{ $student->program_label }}</dd>
                    <dt class="col-5">Kurikulum</dt><dd class="col-7">{{ $student->school_curriculum ?: '-' }}</dd>
                    <dt class="col-5">Sisa Kuota Sesi</dt>
                    <dd class="col-7"><span class="badge {{ ($student->quota_sessions ?? 0) > 2 ? 'bg-success' : 'bg-warning' }}">{{ $student->quota_sessions ?? 0 }}</span></dd>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h3 class="card-title fw-bold">Statistik Bersama Anda</h3>
                <div class="row text-center g-2">
                    <div class="col-4">
                        <div class="text-muted small">Sesi</div>
                        <div class="fs-3 fw-bold">{{ $stats['done'] }}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small">Dievaluasi</div>
                        <div class="fs-3 fw-bold">{{ $stats['evaluated'] }}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small">Rata Post Test</div>
                        <div class="fs-3 fw-bold">{{ $stats['avg_post_test'] ?? '—' }}</div>
                    </div>
                </div>
                <div class="d-flex justify-content-center gap-3 mt-2 small">
                    <span class="text-success">Hadir: {{ $stats['hadir'] }}</span>
                    <span class="text-warning">Izin: {{ $stats['izin'] }}</span>
                    <span class="text-danger">Alfa: {{ $stats['alfa'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white">
                <h3 class="card-title fw-bold mb-0">Riwayat Sesi</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-vcenter" id="table-riwayat" style="width:100%">
                        <thead>
                            <tr>
                                <th style="width:50px">No</th>
                                <th>Tanggal</th>
                                <th>Mapel</th>
                                <th>Materi</th>
                                <th>Kehadiran</th>
                                <th>Post Test</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#table-riwayat').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('tutor.students.data', $student->id) }}',
        order: [],
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'class_date' },
            { data: 'subject_name', name: 'subject.subject_name', orderable: false },
            { data: 'materi', orderable: false, searchable: false },
            { data: 'attendance', orderable: false, searchable: false },
            { data: 'post_test', orderable: false, searchable: false },
            { data: 'status_schedule' },
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json',
            emptyTable: 'Belum ada riwayat sesi.'
        }
    });
});
</script>
@endpush
