@extends('admin.layouts.app')

@section('title', 'Dashboard Siswa - LIVO Admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="mb-4 d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-link link-secondary ps-0 mb-1">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
                </a>
                <h1 class="fs-3 mb-1">Dashboard Siswa</h1>
                <p class="text-muted mb-0">Akumulasi status, pembayaran, pertumbuhan, dan sebaran jenjang siswa.</p>
            </div>
        </div>
    </div>
</div>

{{-- ══════════ (a) Akumulasi Siswa & Status Pembayaran ══════════ --}}
<div class="mb-2"><h3 class="fs-5 fw-bold mb-0">Akumulasi Siswa</h3></div>
<div class="row g-3 mb-4">
    <div class="col-lg col-sm-6 col-12">
        <div class="card p-3 bg-secondary-subtle border-0 rounded-3 h-100">
            <div class="subheader text-secondary mb-1">Total Siswa</div>
            <div class="h2 fw-bold mb-0">{{ number_format($stats['total']) }}</div>
            <div class="small text-muted">Keseluruhan (aktif + non-aktif)</div>
        </div>
    </div>
    <div class="col-lg col-sm-6 col-12">
        <div class="card p-3 bg-success-subtle border-0 rounded-3 h-100">
            <div class="subheader text-success mb-1">Aktif</div>
            <div class="h2 fw-bold mb-0">{{ number_format($stats['aktif']) }}</div>
            <div class="small text-muted">Status aktif</div>
        </div>
    </div>
    <div class="col-lg col-sm-6 col-12">
        <div class="card p-3 bg-danger-subtle border-0 rounded-3 h-100">
            <div class="subheader text-danger mb-1">Non-Aktif</div>
            <div class="h2 fw-bold mb-0">{{ number_format($stats['non_aktif']) }}</div>
            <div class="small text-muted">Status non-aktif</div>
        </div>
    </div>
    <div class="col-lg col-sm-6 col-12">
        <div class="card p-3 bg-warning-subtle border-0 rounded-3 h-100">
            <div class="subheader text-warning mb-1">Cuti</div>
            <div class="h2 fw-bold mb-0">{{ number_format($stats['cuti']) }}</div>
            <div class="small text-muted">Status cuti</div>
        </div>
    </div>
    <div class="col-lg col-sm-6 col-12">
        <div class="card p-3 border-0 rounded-3 h-100" style="background:#fde8e8;">
            <div class="subheader mb-1" style="color:#b42318;">Belum Bayar Bulan Ini</div>
            <div class="h2 fw-bold mb-0" style="color:#b42318;">{{ number_format($unpaidCount) }}</div>
            <div class="small text-muted">Siswa aktif · {{ $monthLabel }}</div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white px-4 py-3">
        <h4 class="mb-0 h5">Siswa Aktif Belum Bayar SPP — {{ $monthLabel }}</h4>
        <p class="text-muted small mb-0">Siswa berstatus aktif yang belum tercatat pembayaran SPP untuk bulan berjalan.</p>
    </div>
    <div class="table-responsive p-3">
        <table class="table table-hover align-middle mb-0" id="unpaid-table" style="width:100%">
            <thead class="table-light">
                <tr>
                    <th width="40">#</th>
                    <th>Nama Siswa</th>
                    <th>Kelas</th>
                    <th>No. WhatsApp</th>
                    <th>Status SPP Terakhir</th>
                    <th class="text-center" width="100">Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

{{-- ══════════ (b) Pertumbuhan Siswa Baru ══════════ --}}
<div class="mb-2"><h3 class="fs-5 fw-bold mb-0">Penambahan Siswa Baru</h3></div>
<div class="row g-3 mb-3">
    <div class="col-lg-4 col-sm-6 col-12">
        <div class="card p-3 bg-primary-subtle border-0 rounded-3 h-100">
            <div class="subheader text-primary mb-1">Bulan Ini</div>
            <div class="h2 fw-bold mb-0">{{ number_format($newThisMonth) }}</div>
            <div class="small text-muted">{{ $monthLabel }}</div>
        </div>
    </div>
    <div class="col-lg-4 col-sm-6 col-12">
        <div class="card p-3 bg-info-subtle border-0 rounded-3 h-100">
            <div class="subheader text-info mb-1">Tahun Ini</div>
            <div class="h2 fw-bold mb-0">{{ number_format($newThisYear) }}</div>
            <div class="small text-muted">{{ now()->format('Y') }}</div>
        </div>
    </div>
    <div class="col-lg-4 col-sm-6 col-12">
        <div class="card p-3 bg-secondary-subtle border-0 rounded-3 h-100">
            <div class="subheader text-secondary mb-1">Keseluruhan</div>
            <div class="h2 fw-bold mb-0">{{ number_format($stats['total']) }}</div>
            <div class="small text-muted">Sejak awal berdiri</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8 col-12">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white px-4 py-3"><h4 class="mb-0 h5">Siswa Baru per Bulan (12 Bulan Terakhir)</h4></div>
            <div class="card-body"><div id="chart-per-month"></div></div>
        </div>
    </div>
    <div class="col-lg-4 col-12">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white px-4 py-3"><h4 class="mb-0 h5">Siswa Baru per Tahun</h4></div>
            <div class="card-body"><div id="chart-per-year"></div></div>
        </div>
    </div>
</div>

{{-- ══════════ (c) Sebaran per Jenjang ══════════ --}}
<div class="mb-2"><h3 class="fs-5 fw-bold mb-0">Sebaran Siswa per Jenjang</h3></div>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white px-4 py-3"><h4 class="mb-0 h5">Jumlah Siswa per Jenjang / Kelas</h4></div>
    <div class="card-body"><div id="chart-per-grade"></div></div>
</div>
@endsection

@push('js')
<script>
$(function () {
    var unpaidTable = $('#unpaid-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.students.dashboard.data-unpaid') }}",
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'full_name' },
            { data: 'grade' },
            { data: 'whatsapp' },
            { data: 'last_expired', orderable: false },
            { data: 'action', orderable: false, searchable: false, className: 'text-center' },
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json',
            emptyTable: 'Semua siswa aktif sudah bayar SPP bulan ini. 🎉'
        }
    });

    var brand = '#2C3E73';
    var gridColor = '#e5e7eb';
    var baseOptions = {
        chart: { toolbar: { show: false }, fontFamily: 'inherit' },
        colors: [brand],
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        dataLabels: { enabled: true, offsetY: -18, style: { fontSize: '11px', colors: ['#455870'] } },
        plotOptions: { bar: { borderRadius: 4, borderRadiusApplication: 'end', columnWidth: '45%' } },
        xaxis: { axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { labels: { formatter: function (v) { return Math.round(v); } } },
        tooltip: { y: { formatter: function (v) { return v + ' siswa'; } } },
    };

    new ApexCharts(document.querySelector('#chart-per-month'), Object.assign({}, baseOptions, {
        chart: Object.assign({}, baseOptions.chart, { type: 'bar', height: 280 }),
        series: [{ name: 'Siswa Baru', data: @json($newPerMonth->pluck('total')) }],
        xaxis: Object.assign({}, baseOptions.xaxis, { categories: @json($newPerMonth->pluck('label')) }),
    })).render();

    new ApexCharts(document.querySelector('#chart-per-year'), Object.assign({}, baseOptions, {
        chart: Object.assign({}, baseOptions.chart, { type: 'bar', height: 280 }),
        series: [{ name: 'Siswa Baru', data: @json($newPerYear->pluck('total')) }],
        xaxis: Object.assign({}, baseOptions.xaxis, { categories: @json($newPerYear->pluck('label')) }),
        plotOptions: { bar: { borderRadius: 4, borderRadiusApplication: 'end', columnWidth: '55%' } },
    })).render();

    new ApexCharts(document.querySelector('#chart-per-grade'), Object.assign({}, baseOptions, {
        chart: Object.assign({}, baseOptions.chart, { type: 'bar', height: 340 }),
        series: [{ name: 'Jumlah Siswa', data: @json($perGrade->pluck('total')) }],
        xaxis: Object.assign({}, baseOptions.xaxis, { categories: @json($perGrade->pluck('label')) }),
        dataLabels: { enabled: true, offsetY: -18, style: { fontSize: '11px', colors: ['#455870'] } },
    })).render();
});
</script>
@endpush
