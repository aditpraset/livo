@extends('admin.layouts.app')

@section('title', 'Laporan Evaluasi - ' . $student->full_name . ' - LIVO Admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <a href="{{ route('admin.students.show', $student->id) }}" class="btn btn-link link-secondary ps-0 mb-1">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Profil Siswa
        </a>
        <h2 class="page-title mb-0">Laporan Evaluasi</h2>
        <p class="text-muted mb-0 small">{{ $student->full_name }} — {{ $student->grade ?? '-' }}</p>
    </div>
</div>
@endsection

@section('content')
{{-- Stats Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-primary">{{ $stats['total'] }}</div>
            <div class="small text-muted">Total Sesi Selesai</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-info">{{ $stats['avg_score'] ?? '—' }}</div>
            <div class="small text-muted">Rata-rata Nilai</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-success">{{ $stats['hadir'] }}</div>
            <div class="small text-muted">Hadir</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-warning">{{ $stats['izin'] }}</div>
            <div class="small text-muted">Izin</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-danger">{{ $stats['alfa'] }}</div>
            <div class="small text-muted">Alfa</div>
        </div>
    </div>
</div>

{{-- Filter --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Tanggal Mulai</label>
                <input type="date" id="filter-start" class="form-control" value="{{ now()->subYear()->format('Y-m-d') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Tanggal Akhir</label>
                <input type="date" id="filter-end" class="form-control" value="{{ now()->format('Y-m-d') }}">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary" id="btn-filter"><i class="bi bi-funnel me-1"></i> Filter</button>
                <button class="btn btn-outline-secondary" id="btn-reset" title="Reset ke default"><i class="bi bi-arrow-counterclockwise"></i></button>
            </div>
        </div>
    </div>
</div>

{{-- Tabel Evaluasi --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0">Rincian Evaluasi Per Sesi</h5>
        <div class="d-flex align-items-center gap-2">
            @if($stats['evaluated'] < $stats['total'])
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                    {{ $stats['total'] - $stats['evaluated'] }} sesi belum dievaluasi
                </span>
            @endif
            <button class="btn btn-success btn-sm" id="btn-export-excel">
                <i class="bi bi-file-earmark-excel me-1"></i> Download Excel
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="report-table">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Tanggal</th>
                        <th>Mata Pelajaran</th>
                        <th>Tutor</th>
                        <th>Sub Pokok Bahasan</th>
                        <th class="text-center">Kehadiran</th>
                        <th class="text-center">Nilai</th>
                        <th class="text-center">Kemampuan Analisa</th>
                        <th class="text-center">Kemampuan Hafalan</th>
                        <th class="text-center">Kepercayaan Diri</th>
                        <th>Catatan Tutor</th>
                        <th class="text-center">Laporan</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    var table = $('#report-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.data.evaluations.student', $student->id) }}",
            data: function (d) {
                d.start = $('#filter-start').val();
                d.end   = $('#filter-end').val();
            }
        },
        order: [[1, 'desc']],
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'class_date', name: 'schedules.class_date', searchable: false },
            { data: 'subject_name', name: 'subjects.subject_name' },
            { data: 'tutor_name', name: 'tutors.name' },
            { data: 'materi', orderable: false, searchable: false },
            { data: 'attendance', name: 'evaluations.student_attendance', className: 'text-center', searchable: false },
            { data: 'post_test', name: 'evaluations.post_test', className: 'text-center', searchable: false },
            { data: 'kemampuan_analisa', name: 'evaluations.kemampuan_analisa', className: 'text-center', searchable: false },
            { data: 'kemampuan_hafalan', name: 'evaluations.kemampuan_hafalan', className: 'text-center', searchable: false },
            { data: 'kepercayaan_diri', name: 'evaluations.kepercayaan_diri', className: 'text-center', searchable: false },
            { data: 'notes', orderable: false, searchable: false },
            { data: 'published', orderable: false, searchable: false, className: 'text-center' },
            { data: 'action', orderable: false, searchable: false, className: 'text-center' },
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json' }
    });

    $('#btn-filter').on('click', function () { table.ajax.reload(); });
    $('#btn-reset').on('click', function () {
        $('#filter-start').val('{{ now()->subYear()->format('Y-m-d') }}');
        $('#filter-end').val('{{ now()->format('Y-m-d') }}');
        table.ajax.reload();
    });

    $('#btn-export-excel').on('click', function () {
        var base = "{{ route('admin.evaluations.student.excel', $student->id) }}";
        var params = [];
        if ($('#filter-start').val()) params.push('start=' + $('#filter-start').val());
        if ($('#filter-end').val())   params.push('end=' + $('#filter-end').val());
        window.location.href = base + (params.length ? '?' + params.join('&') : '');
    });

    $(document).on('click', '.btn-toggle-publish', function () {
        var btn         = $(this);
        var id          = btn.data('id');
        var isPublished = parseInt(btn.data('published'));
        var action      = isPublished ? 'Sembunyikan' : 'Terbitkan';
        var text        = isPublished
            ? 'Laporan tidak akan tampil ke orang tua.'
            : 'Laporan evaluasi akan dikirimkan / tampil ke orang tua.';

        Swal.fire({
            title: action + ' Laporan?',
            text: text,
            icon: 'question', showCancelButton: true,
            confirmButtonText: 'Ya, ' + action, cancelButtonText: 'Batal'
        }).then(function (r) {
            if (r.isConfirmed) {
                $.ajax({
                    url: '/admin/evaluations/' + id + '/publish', type: 'PUT',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        table.ajax.reload(null, false);
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false });
                    },
                    error: function () {
                        Swal.fire('Gagal', 'Terjadi kesalahan server.', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
