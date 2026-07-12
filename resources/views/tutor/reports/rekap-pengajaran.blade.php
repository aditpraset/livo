@extends('tutor.layouts.app')

@section('title', 'Rekap Pengajaran - LIVO Tutor')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h1 class="fs-3 mb-1">Rekapitulasi Hasil Pengajaran</h1>
        <p class="text-muted mb-0">Periode {{ $month->translatedFormat('F Y') }}</p>
    </div>
    <div class="col-md-6 text-md-end mt-2 mt-md-0">
        <form method="GET" class="d-inline-flex gap-2">
            <input type="month" name="month" class="form-control" value="{{ $month->format('Y-m') }}" style="width:180px">
            <button type="submit" class="btn btn-primary">Tampilkan</button>
        </form>
        <a href="{{ route('tutor.reports.summary', ['month' => $month->format('Y-m')]) }}" class="btn btn-outline-danger ms-1">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-2"><div class="card card-sm"><div class="card-body"><div class="text-muted small">Sesi Selesai</div><div class="fs-2 fw-bold">{{ $stats['done'] }}</div></div></div></div>
    <div class="col-6 col-md-2"><div class="card card-sm"><div class="card-body"><div class="text-muted small">Siswa Diajar</div><div class="fs-2 fw-bold">{{ $stats['students'] }}</div></div></div></div>
    <div class="col-6 col-md-2"><div class="card card-sm"><div class="card-body"><div class="text-muted small">Dievaluasi</div><div class="fs-2 fw-bold">{{ $stats['evaluated'] }}</div></div></div></div>
    <div class="col-6 col-md-2"><div class="card card-sm"><div class="card-body"><div class="text-muted small">Rata Post Test</div><div class="fs-2 fw-bold">{{ $stats['avg_post_test'] ?? '—' }}</div></div></div></div>
    <div class="col-6 col-md-2"><div class="card card-sm"><div class="card-body"><div class="text-muted small">Hadir / Izin / Alfa</div><div class="fs-4 fw-bold"><span class="text-success">{{ $stats['hadir'] }}</span> / <span class="text-warning">{{ $stats['izin'] }}</span> / <span class="text-danger">{{ $stats['alfa'] }}</span></div></div></div></div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-vcenter" id="table-rekap" style="width:100%">
                <thead>
                    <tr>
                        <th style="width:50px">No</th>
                        <th>Tanggal</th>
                        <th>Siswa</th>
                        <th>Mapel</th>
                        <th>Materi</th>
                        <th>Kehadiran</th>
                        <th>Post Test</th>
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
    $('#table-rekap').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('tutor.rekap-pengajaran.data', ['month' => $month->format('Y-m')]) }}',
        order: [],
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'class_date' },
            { data: 'student_name', name: 'student.full_name' },
            { data: 'subject_name', name: 'subject.subject_name', orderable: false },
            { data: 'materi', orderable: false, searchable: false },
            { data: 'attendance', orderable: false, searchable: false },
            { data: 'post_test', orderable: false, searchable: false },
            { data: 'notes', orderable: false, searchable: false },
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json',
            emptyTable: 'Tidak ada sesi selesai pada periode ini.'
        }
    });
});
</script>
@endpush
