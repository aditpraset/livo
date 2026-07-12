@extends('tutor.layouts.app')

@section('title', 'Rekap Fee - LIVO Tutor')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h1 class="fs-3 mb-1">Rekapitulasi Fee</h1>
        <p class="text-muted mb-0">Tahun {{ $year }} · dihitung dari sesi berstatus <strong>selesai</strong> × fee per sesi.</p>
    </div>
    <div class="col-md-6 text-md-end mt-2 mt-md-0">
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

@if($fee <= 0)
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-1"></i>
        Fee per sesi Anda belum diatur oleh admin, sehingga nominal fee tampil Rp 0. Hubungi admin untuk pengaturannya.
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">Fee per Sesi</div>
            <div class="fs-2 fw-bold">Rp {{ number_format($fee, 0, ',', '.') }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card card-sm"><div class="card-body">
            <div class="text-muted small">Total Sesi {{ $year }}</div>
            <div class="fs-2 fw-bold">{{ $totalSessions }}</div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card card-sm border-success"><div class="card-body">
            <div class="text-muted small">Total Fee {{ $year }}</div>
            <div class="fs-2 fw-bold text-success">Rp {{ number_format($totalFee, 0, ',', '.') }}</div>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th class="text-center">Jumlah Sesi Selesai</th>
                    <th class="text-end">Fee</th>
                    <th class="text-end">Slip Gaji</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr class="{{ $row['sessions'] === 0 ? 'text-muted' : '' }}">
                        <td>{{ $row['month']->translatedFormat('F') }}</td>
                        <td class="text-center">{{ $row['sessions'] }}</td>
                        <td class="text-end fw-semibold">Rp {{ number_format($row['fee'], 0, ',', '.') }}</td>
                        <td class="text-end">
                            @if($row['sessions'] > 0)
                                <a href="{{ route('tutor.reports.slip-gaji', ['month' => $row['month']->format('Y-m')]) }}" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> Slip
                                </a>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td>Total</td>
                    <td class="text-center">{{ $totalSessions }}</td>
                    <td class="text-end">Rp {{ number_format($totalFee, 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
