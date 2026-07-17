@extends('tutor.layouts.app')

@section('title', 'History Evaluasi - LIVO Tutor')

@section('content')
<div class="mb-4">
    <a href="{{ route('tutor.students.index') }}" class="btn btn-link link-secondary ps-0"><i class="bi bi-arrow-left me-1"></i> Kembali ke Data Siswa</a>
    <h1 class="fs-3 mb-1 mt-2">History Evaluasi: {{ $student->full_name }}</h1>
    <p class="text-muted mb-0">
        {{ $student->nis ?: '-' }} · {{ $student->grade ?: '-' }} · {{ $student->program_label }}
    </p>
</div>

{{-- Ringkasan evaluasi --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Total Evaluasi</div>
                <div class="fs-3 fw-bold">{{ $stats['evaluated'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Rata-rata Nilai</div>
                <div class="fs-3 fw-bold text-primary">{{ $stats['avg_post_test'] ?? '—' }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Hadir / Izin</div>
                <div class="fs-3 fw-bold"><span class="text-success">{{ $stats['hadir'] }}</span> / <span class="text-warning">{{ $stats['izin'] }}</span></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <div class="text-muted small">Alfa</div>
                <div class="fs-3 fw-bold text-danger">{{ $stats['alfa'] }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h3 class="card-title fw-bold mb-0">Riwayat Evaluasi</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-vcenter" id="table-history" style="width:100%">
                <thead>
                    <tr>
                        <th style="width:44px">No</th>
                        <th>Tanggal</th>
                        <th>Mapel</th>
                        <th>Materi / Silabus</th>
                        <th>Tutor</th>
                        <th>Kehadiran</th>
                        <th>Nilai</th>
                        <th>Analisa</th>
                        <th>Hafalan</th>
                        <th>Percaya Diri</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#table-history').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('tutor.students.data', $student->id) }}',
        order: [],
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'class_date' },
            { data: 'subject_name', name: 'subject.subject_name', orderable: false },
            { data: 'materi', orderable: false, searchable: false },
            { data: 'tutor_name', name: 'tutor.name', orderable: false },
            { data: 'attendance', orderable: false, searchable: false, className: 'text-center' },
            { data: 'post_test', orderable: false, searchable: false, className: 'text-center' },
            { data: 'analisa', orderable: false, searchable: false, className: 'text-center' },
            { data: 'hafalan', orderable: false, searchable: false, className: 'text-center' },
            { data: 'kepercayaan', orderable: false, searchable: false, className: 'text-center' },
            { data: 'notes', orderable: false, searchable: false },
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json',
            emptyTable: 'Belum ada evaluasi untuk siswa ini.'
        }
    });
});
</script>
@endpush
