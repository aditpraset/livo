@extends('tutor.layouts.app')

@section('title', 'Rekap Fee - LIVO Tutor')

@section('content')
@php $rp = fn ($v) => 'Rp ' . number_format($v, 0, ',', '.'); @endphp

<div class="row mb-4">
    <div class="col-md-7">
        <h1 class="fs-3 mb-1">Rekapitulasi Fee</h1>
        <p class="text-muted mb-0">Tahun {{ $year }} · total fee = fee sesi + fee per siswa (hadir) + fee transport per hari. Fee tampil setelah diterbitkan admin.</p>
    </div>
    <div class="col-md-5 text-md-end mt-2 mt-md-0">
        <form method="GET" class="d-inline-flex gap-2">
            <select name="year" class="form-select" style="width:120px">
                @for($y = now()->year; $y >= now()->year - 4; $y--)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
            <button type="submit" class="btn btn-primary">Tampilkan</button>
        </form>
    </div>
</div>

@if(!$rows->contains('published', true))
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-1"></i>
        Belum ada fee yang diterbitkan admin untuk tahun {{ $year }}. Fee akan tampil di sini setelah admin menerbitkannya.
    </div>
@endif

@if($rates['session'] <= 0 && $rates['private'] <= 0 && $rates['student'] <= 0 && $rates['transport'] <= 0)
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-1"></i>
        Tarif fee Anda belum diatur oleh admin, sehingga nominal fee tampil Rp 0. Hubungi admin untuk pengaturannya.
    </div>
@endif

{{-- Tarif fee (dari data tutor) --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">Fee per Sesi</div>
            <div class="fs-4 fw-bold">{{ $rp($rates['session']) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">Fee / Siswa Privat</div>
            <div class="fs-4 fw-bold">{{ $rp($rates['private']) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">Fee / Siswa Semi-Privat</div>
            <div class="fs-4 fw-bold">{{ $rp($rates['student']) }}</div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">Fee Transport / Hari</div>
            <div class="fs-4 fw-bold">{{ $rp($rates['transport']) }}</div>
        </div></div>
    </div>
</div>

<div class="card mb-4 border-success">
    <div class="card-body d-flex justify-content-between align-items-center">
        <div class="text-muted">Total Fee Tahun {{ $year }}</div>
        <div class="fs-2 fw-bold text-success">{{ $rp($totals['total']) }}</div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th rowspan="2" class="align-middle">Bulan</th>
                    <th colspan="2" class="text-center border-start">Sesi (c)</th>
                    <th colspan="2" class="text-center border-start">Siswa Privat (a)</th>
                    <th colspan="2" class="text-center border-start">Siswa Semi-Privat (b)</th>
                    <th colspan="2" class="text-center border-start">Transport (d)</th>
                    <th rowspan="2" class="text-end align-middle border-start">Total Fee</th>
                    <th rowspan="2" class="text-center align-middle">Slip</th>
                </tr>
                <tr>
                    <th class="text-center border-start">Jml</th><th class="text-end">Fee</th>
                    <th class="text-center border-start">Jml</th><th class="text-end">Fee</th>
                    <th class="text-center border-start">Jml</th><th class="text-end">Fee</th>
                    <th class="text-center border-start">Hari</th><th class="text-end">Fee</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr class="{{ !$row['published'] ? 'text-muted' : '' }}">
                        <td class="fw-semibold">
                            {{ $row['month']->locale('id')->translatedFormat('F') }}
                            @if(!$row['published'])
                                <span class="badge bg-secondary-subtle text-secondary border ms-1">Belum terbit</span>
                            @endif
                        </td>
                        <td class="text-center border-start">{{ $row['session_count'] }}</td>
                        <td class="text-end">{{ $rp($row['fee_session']) }}</td>
                        <td class="text-center border-start">{{ $row['private_count'] }}</td>
                        <td class="text-end">{{ $rp($row['fee_private']) }}</td>
                        <td class="text-center border-start">{{ $row['regular_count'] }}</td>
                        <td class="text-end">{{ $rp($row['fee_regular']) }}</td>
                        <td class="text-center border-start">{{ $row['day_count'] }}</td>
                        <td class="text-end">{{ $rp($row['fee_transport']) }}</td>
                        <td class="text-end fw-bold border-start">{{ $rp($row['total']) }}</td>
                        <td class="text-center">
                            @if($row['published'])
                                <a href="{{ route('tutor.reports.slip-gaji', ['month' => $row['month']->format('Y-m')]) }}" class="btn btn-sm btn-outline-danger" title="Cetak Slip Gaji">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="fw-bold table-light">
                    <td>Total {{ $year }}</td>
                    <td class="text-center border-start">{{ $totals['session_count'] }}</td>
                    <td class="text-end">{{ $rp($totals['fee_session']) }}</td>
                    <td class="text-center border-start">{{ $totals['private_count'] }}</td>
                    <td class="text-end">{{ $rp($totals['fee_private']) }}</td>
                    <td class="text-center border-start">{{ $totals['regular_count'] }}</td>
                    <td class="text-end">{{ $rp($totals['fee_regular']) }}</td>
                    <td class="text-center border-start">{{ $totals['day_count'] }}</td>
                    <td class="text-end">{{ $rp($totals['fee_transport']) }}</td>
                    <td class="text-end text-success border-start">{{ $rp($totals['total']) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<p class="text-muted small mt-3 mb-0">
    <i class="bi bi-info-circle me-1"></i>
    Jumlah siswa (a &amp; b) dihitung per kehadiran (setiap siswa hadir di setiap sesi). Sesi (c) dihitung per slot (tanggal + jam), berapa pun jumlah siswa dalam slot itu. Transport (d) dihitung per hari yang ada sesi.
</p>
@endsection
