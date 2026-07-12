@extends('tutor.layouts.app')

@section('title', 'Laporan - LIVO Tutor')

@section('content')
<div class="mb-4">
    <h1 class="fs-3 mb-1">Laporan</h1>
    <p class="text-muted mb-0">Unduh slip gaji dan summary pengajaran dalam format PDF.</p>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body text-center py-4">
                <i class="bi bi-cash-stack text-success" style="font-size: 3rem;"></i>
                <h3 class="mt-3 mb-1">Slip Gaji</h3>
                <p class="text-muted small">Rincian fee mengajar untuk bulan terpilih.</p>
                <form action="{{ route('tutor.reports.slip-gaji') }}" method="GET" class="d-flex gap-2 justify-content-center">
                    <input type="month" name="month" class="form-control" value="{{ now()->format('Y-m') }}" style="max-width:180px" required>
                    <button type="submit" class="btn btn-success"><i class="bi bi-download me-1"></i> Unduh PDF</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body text-center py-4">
                <i class="bi bi-journal-richtext text-primary" style="font-size: 3rem;"></i>
                <h3 class="mt-3 mb-1">Summary Pengajaran</h3>
                <p class="text-muted small">Rekap lengkap sesi, materi & penilaian untuk bulan terpilih.</p>
                <form action="{{ route('tutor.reports.summary') }}" method="GET" class="d-flex gap-2 justify-content-center">
                    <input type="month" name="month" class="form-control" value="{{ now()->format('Y-m') }}" style="max-width:180px" required>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-download me-1"></i> Unduh PDF</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
