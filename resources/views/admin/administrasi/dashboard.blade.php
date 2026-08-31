@extends('admin.layouts.app')

@section('title', 'Dashboard Administrasi - LIVO Admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="mb-4">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-link link-secondary ps-0 mb-1">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
            </a>
            <h1 class="fs-3 mb-1">Dashboard Administrasi</h1>
            <p class="text-muted mb-0">Pembayaran, piutang, breakdown paket, kepadatan sesi, dan reminder evaluasi.</p>
        </div>
    </div>
</div>

{{-- ══════════ (a) Pembayaran ══════════ --}}
<div class="mb-2"><h3 class="fs-5 fw-bold mb-0">a. Pembayaran</h3></div>
<div class="row g-3 mb-3">
    <div class="col-lg-4 col-sm-6 col-12">
        <div class="card p-3 bg-success-subtle border-0 rounded-3 h-100">
            <div class="subheader text-success mb-1">Total Keseluruhan</div>
            <div class="h2 fw-bold mb-0">Rp {{ number_format($totalPembayaran, 0, ',', '.') }}</div>
            <div class="small text-muted">Semua transaksi, semua kategori</div>
        </div>
    </div>
    <div class="col-lg-4 col-sm-6 col-12">
        <div class="card p-3 bg-primary-subtle border-0 rounded-3 h-100">
            <div class="subheader text-primary mb-1">Bulan Ini</div>
            <div class="h2 fw-bold mb-0">Rp {{ number_format($revenueThisMonth, 0, ',', '.') }}</div>
            <div class="small text-muted">{{ $monthLabel }}</div>
        </div>
    </div>
</div>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white px-4 py-3"><h4 class="mb-0 h5">Pembayaran per Bulan (12 Bulan Terakhir)</h4></div>
    <div class="card-body"><div id="chart-revenue"></div></div>
</div>

{{-- ══════════ (b) Outstanding / Piutang ══════════ --}}
<div class="mb-2"><h3 class="fs-5 fw-bold mb-0">b. Outstanding (Piutang Estimasi)</h3></div>
<div class="row g-3 mb-3">
    <div class="col-lg-4 col-sm-6 col-12">
        <div class="card p-3 border-0 rounded-3 h-100" style="background:#fde8e8;">
            <div class="subheader mb-1" style="color:#b42318;">Jumlah Siswa</div>
            <div class="h2 fw-bold mb-0" style="color:#b42318;">{{ number_format($outstandingCount) }}</div>
            <div class="small text-muted">SPP lewat / akan expired ≤7 hari</div>
        </div>
    </div>
    <div class="col-lg-4 col-sm-6 col-12">
        <div class="card p-3 border-0 rounded-3 h-100" style="background:#fde8e8;">
            <div class="subheader mb-1" style="color:#b42318;">Estimasi Piutang</div>
            <div class="h2 fw-bold mb-0" style="color:#b42318;">Rp {{ number_format($outstandingTotal, 0, ',', '.') }}</div>
            <div class="small text-muted">Estimasi dari nominal SPP terakhir tiap siswa</div>
        </div>
    </div>
</div>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white px-4 py-3">
        <h4 class="mb-0 h5">Daftar Piutang (dari data Pengingat Pembayaran)</h4>
        <p class="text-muted small mb-0">Estimasi memakai nominal pembayaran SPP terakhir siswa — bukan tagihan pasti.</p>
    </div>
    <div class="table-responsive p-3">
        <table class="table table-hover align-middle mb-0" id="outstanding-table" style="width:100%">
            <thead class="table-light">
                <tr>
                    <th width="40">#</th>
                    <th>Nama Siswa</th>
                    <th>Kelas</th>
                    <th>No. WhatsApp</th>
                    <th>Status SPP</th>
                    <th class="text-end">Estimasi Nominal</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

{{-- ══════════ (c) Breakdown Paket Multi-Bulan ══════════ --}}
<div class="mb-2"><h3 class="fs-5 fw-bold mb-0">c. Breakdown Pembayaran Paket Multi-Bulan (3/6/12 Bulan)</h3></div>
<div class="row g-3 mb-3">
    <div class="col-lg-4 col-sm-6 col-12">
        <div class="card p-3 bg-info-subtle border-0 rounded-3 h-100">
            <div class="subheader text-info mb-1">Jumlah Transaksi</div>
            <div class="h2 fw-bold mb-0">{{ number_format($multiMonthCount) }}</div>
            <div class="small text-muted">Periode &gt; 1 bulan</div>
        </div>
    </div>
    <div class="col-lg-4 col-sm-6 col-12">
        <div class="card p-3 bg-info-subtle border-0 rounded-3 h-100">
            <div class="subheader text-info mb-1">Total Nilai</div>
            <div class="h2 fw-bold mb-0">Rp {{ number_format($multiMonthTotal, 0, ',', '.') }}</div>
            <div class="small text-muted">Sebelum dipecah per bulan</div>
        </div>
    </div>
</div>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white px-4 py-3">
        <h4 class="mb-0 h5">Rincian Pembayaran per Bulan</h4>
        <p class="text-muted small mb-0">Nominal pembayaran paket 2/3/6/12 bulan dipecah rata per bulan cakupannya.</p>
    </div>
    <div class="table-responsive p-3">
        <table class="table table-hover align-middle mb-0" id="multi-month-table" style="width:100%">
            <thead class="table-light">
                <tr>
                    <th width="40">#</th>
                    <th>Nama Siswa</th>
                    <th>Periode</th>
                    <th class="text-end">Total Bayar</th>
                    <th class="text-end">Per Bulan</th>
                    <th>Cakupan Bulan</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

{{-- ══════════ (d) Kepadatan Sesi per Minggu ══════════ --}}
<div class="mb-2 d-flex justify-content-between align-items-end flex-wrap gap-2">
    <h3 class="fs-5 fw-bold mb-0">d. Kepadatan Siswa per Sesi (Mingguan)</h3>
    <div class="btn-group">
        <a href="{{ route('admin.administrasi.dashboard', ['week' => $prevWeek]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-left"></i> Minggu Lalu</a>
        <a href="{{ route('admin.administrasi.dashboard') }}" class="btn btn-outline-primary btn-sm">Minggu Ini</a>
        <a href="{{ route('admin.administrasi.dashboard', ['week' => $nextWeek]) }}" class="btn btn-outline-secondary btn-sm">Minggu Depan <i class="bi bi-chevron-right"></i></a>
    </div>
</div>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white px-4 py-3">
        <h4 class="mb-0 h5">{{ $weekStart->translatedFormat('d M Y') }} — {{ $weekEnd->translatedFormat('d M Y') }}</h4>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-vcenter mb-0 text-center">
            <thead class="table-light">
                <tr>
                    <th class="text-start">Sesi</th>
                    @foreach($days as $day)
                        <th class="{{ $day->isToday() ? 'table-primary' : '' }}">{{ $day->translatedFormat('D') }}<br><small class="text-muted fw-normal">{{ $day->format('d/m') }}</small></th>
                    @endforeach
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($densityMatrix as $row)
                    <tr>
                        <td class="text-start fw-semibold">{{ $row['session'] }}</td>
                        @foreach($row['counts'] as $c)
                            <td>
                                @if($c > 0)
                                    <span class="badge {{ $c >= 10 ? 'bg-danger' : ($c >= 5 ? 'bg-warning text-dark' : 'bg-success-subtle text-success') }}">{{ $c }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="fw-bold">{{ $row['total'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $days->count() + 2 }}" class="text-muted py-3">Belum ada Sesi Pembelajaran yang terdaftar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ══════════ (e) Reminder Evaluasi Belum Diisi ══════════ --}}
<div class="mb-2"><h3 class="fs-5 fw-bold mb-0">e. Reminder Evaluasi Belum Diisi</h3></div>
<div class="row g-3 mb-3">
    <div class="col-lg-4 col-sm-6 col-12">
        <div class="card p-3 bg-warning-subtle border-0 rounded-3 h-100">
            <div class="subheader text-warning mb-1">Sesi Belum Dievaluasi</div>
            <div class="h2 fw-bold mb-0">{{ number_format($pendingEvalCount) }}</div>
            <div class="small text-muted">Selesai / sudah lewat, seluruh tutor</div>
        </div>
    </div>
</div>
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white px-4 py-3"><h4 class="mb-0 h5">Daftar Sesi Belum Dievaluasi</h4></div>
    <div class="table-responsive p-3">
        <table class="table table-hover align-middle mb-0" id="pending-eval-table" style="width:100%">
            <thead class="table-light">
                <tr>
                    <th width="40">#</th>
                    <th>Tanggal</th>
                    <th>Siswa</th>
                    <th>Tutor</th>
                    <th>Mapel</th>
                    <th>Terlambat</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#outstanding-table').DataTable({
        processing: true, serverSide: true,
        ajax: "{{ route('admin.administrasi.dashboard.data-outstanding') }}",
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'full_name' },
            { data: 'grade' },
            { data: 'whatsapp' },
            { data: 'expired_label', orderable: false },
            { data: 'amount_label', orderable: false, className: 'text-end' },
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json', emptyTable: 'Tidak ada piutang saat ini. 🎉' }
    });

    $('#multi-month-table').DataTable({
        processing: true, serverSide: true,
        ajax: "{{ route('admin.administrasi.dashboard.data-multi-month') }}",
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'full_name' },
            { data: 'period_label', orderable: false },
            { data: 'amount_label', orderable: false, className: 'text-end' },
            { data: 'per_month_label', orderable: false, className: 'text-end' },
            { data: 'coverage', orderable: false },
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json', emptyTable: 'Belum ada pembayaran paket multi-bulan.' }
    });

    $('#pending-eval-table').DataTable({
        processing: true, serverSide: true,
        ajax: "{{ route('admin.administrasi.dashboard.data-pending-evaluations') }}",
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'class_date', orderable: false },
            { data: 'student_name', orderable: false },
            { data: 'tutor_name', orderable: false },
            { data: 'subject_name', orderable: false },
            { data: 'days_overdue', orderable: false },
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json', emptyTable: 'Semua sesi sudah dievaluasi. 🎉' }
    });

    var brand = '#2C3E73';
    new ApexCharts(document.querySelector('#chart-revenue'), {
        chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
        colors: [brand],
        series: [{ name: 'Pembayaran', data: @json($revenuePerMonth->pluck('total')) }],
        xaxis: { categories: @json($revenuePerMonth->pluck('label')), axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { labels: { formatter: function (v) { return 'Rp ' + (v / 1000000).toFixed(1) + 'jt'; } } },
        grid: { borderColor: '#e5e7eb', strokeDashArray: 4 },
        plotOptions: { bar: { borderRadius: 4, borderRadiusApplication: 'end', columnWidth: '45%' } },
        dataLabels: { enabled: false },
        tooltip: { y: { formatter: function (v) { return 'Rp ' + Number(v).toLocaleString('id-ID'); } } },
    }).render();
});
</script>
@endpush
