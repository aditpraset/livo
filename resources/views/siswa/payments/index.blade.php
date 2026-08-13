@extends('siswa.layouts.app')

@section('title', 'Pembayaran - LIVO Siswa')

@section('content')
<div class="mb-4">
    <h1 class="fs-3 mb-1">Riwayat Pembayaran</h1>
    <p class="text-muted mb-0">Catatan pembayaran dan masa aktif belajar Anda.</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">Total Pembayaran</div>
            <div class="fs-2 fw-bold text-success">Rp {{ number_format($stats['total_paid'], 0, ',', '.') }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">Jumlah Transaksi</div>
            <div class="fs-2 fw-bold">{{ $stats['count'] }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">Masa Aktif Sampai</div>
            @if($stats['last_expired'])
                @php($expired = \Carbon\Carbon::parse($stats['last_expired']))
                <div class="fs-2 fw-bold {{ $expired->isPast() ? 'text-danger' : 'text-primary' }}">{{ $expired->translatedFormat('d M Y') }}</div>
            @else
                <div class="fs-2 fw-bold text-muted">—</div>
            @endif
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-vcenter" id="table-pembayaran" style="width:100%">
                <thead>
                    <tr>
                        <th style="width:50px">No</th>
                        <th>No. Pembayaran</th>
                        <th>Tanggal Bayar</th>
                        <th>Kategori</th>
                        <th>Deskripsi</th>
                        <th>Jumlah</th>
                        <th>Metode</th>
                        <th>Masa Aktif</th>
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
    $('#table-pembayaran').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('siswa.payments.data') }}',
        order: [],
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'no_payment' },
            { data: 'payment_date' },
            { data: 'category_payment', orderable: false },
            { data: 'description' },
            { data: 'amount' },
            { data: 'payment_method', orderable: false },
            { data: 'masa_aktif', orderable: false, searchable: false },
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json',
            emptyTable: 'Belum ada riwayat pembayaran.'
        }
    });
});
</script>
@endpush
