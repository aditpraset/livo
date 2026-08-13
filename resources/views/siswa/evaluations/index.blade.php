@extends('siswa.layouts.app')

@section('title', 'Nilai & Evaluasi - LIVO Siswa')

@section('content')
<div class="mb-4">
    <h1 class="fs-3 mb-1">Nilai &amp; Evaluasi</h1>
    <p class="text-muted mb-0">Hasil penilaian dari tutor yang sudah diterbitkan.</p>
</div>

{{-- Ringkasan aspek penilaian --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">Sesi Dinilai</div>
            <div class="fs-2 fw-bold">{{ $stats['total'] }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">Rata Post Test</div>
            <div class="fs-2 fw-bold text-primary">{{ $stats['avg_post_test'] ?? '—' }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">Hadir</div>
            <div class="fs-2 fw-bold text-success">{{ $stats['hadir'] }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">Izin</div>
            <div class="fs-2 fw-bold text-warning">{{ $stats['izin'] }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">Alfa</div>
            <div class="fs-2 fw-bold text-danger">{{ $stats['alfa'] }}</div>
        </div></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-white"><h3 class="card-title fw-bold mb-0">Rata-rata per Aspek</h3></div>
            <div class="card-body">
                @php
                    $aspek = [
                        'Pemahaman' => $stats['avg_pemahaman'],
                        'Kemampuan Analisa' => $stats['avg_analisa'],
                        'Kemampuan Hafalan' => $stats['avg_hafalan'],
                        'Kepercayaan Diri' => $stats['avg_kepercayaan'],
                    ];
                @endphp
                @foreach($aspek as $label => $nilai)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small">
                            <span>{{ $label }}</span>
                            <span class="fw-semibold">{{ $nilai ?? '—' }}</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar {{ ($nilai ?? 0) >= 85 ? 'bg-success' : (($nilai ?? 0) >= 70 ? 'bg-primary' : 'bg-warning') }}"
                                style="width: {{ $nilai ?? 0 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white">
                <h3 class="card-title fw-bold mb-0">Rincian per Sesi</h3>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-auto">
                        <label class="form-label small mb-1">Dari Tanggal</label>
                        <input type="date" id="filter-start" class="form-control form-control-sm">
                    </div>
                    <div class="col-auto">
                        <label class="form-label small mb-1">Sampai Tanggal</label>
                        <input type="date" id="filter-end" class="form-control form-control-sm">
                    </div>
                    <div class="col-auto d-flex align-items-end">
                        <button class="btn btn-sm btn-primary" id="btn-filter">Terapkan</button>
                        <button class="btn btn-sm btn-link link-secondary" id="btn-reset">Reset</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter" id="table-nilai" style="width:100%">
                        <thead>
                            <tr>
                                <th style="width:45px">No</th>
                                <th>Tanggal</th>
                                <th>Mapel</th>
                                <th>Tutor</th>
                                <th>Materi</th>
                                <th>Hadir</th>
                                <th>Nilai</th>
                                <th>Paham</th>
                                <th>Analisa</th>
                                <th>Hafalan</th>
                                <th>PD</th>
                                <th>Catatan Tutor</th>
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
    var table = $('#table-nilai').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('siswa.evaluations.data') }}',
            data: function (d) {
                d.start = $('#filter-start').val();
                d.end   = $('#filter-end').val();
            }
        },
        order: [],
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'class_date' },
            { data: 'subject_name', name: 'subject.subject_name', orderable: false },
            { data: 'tutor_name', name: 'tutor.name', orderable: false },
            { data: 'materi', orderable: false, searchable: false },
            { data: 'attendance', orderable: false, searchable: false },
            { data: 'post_test', orderable: false, searchable: false },
            { data: 'pemahaman', orderable: false, searchable: false },
            { data: 'kemampuan_analisa', orderable: false, searchable: false },
            { data: 'kemampuan_hafalan', orderable: false, searchable: false },
            { data: 'kepercayaan_diri', orderable: false, searchable: false },
            { data: 'notes', orderable: false, searchable: false },
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json',
            emptyTable: 'Belum ada nilai yang diterbitkan.'
        }
    });

    $('#btn-filter').on('click', function () { table.ajax.reload(); });
    $('#btn-reset').on('click', function () {
        $('#filter-start, #filter-end').val('');
        table.ajax.reload();
    });
});
</script>
@endpush
