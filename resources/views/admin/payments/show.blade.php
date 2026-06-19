@extends('admin.layouts.app')

@section('title', 'Detail Pembayaran - LIVO Admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="mb-4">
            <a href="{{ route('admin.payments.index') }}" class="btn btn-link link-secondary ps-0">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <div>
                    <h1 class="fs-3 mb-1">Detail Pembayaran: {{ $payment->no_payment }}</h1>
                    <p class="text-muted mb-0">Dibuat pada {{ $payment->created_at->format('d M Y H:i') }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.payments.receipt', $payment->id) }}" target="_blank" class="btn btn-outline-success">
                        <i class="bi bi-printer me-1"></i> Cetak Kwitansi
                    </a>
                    <a href="{{ route('admin.payments.edit', $payment->id) }}" class="btn btn-outline-warning">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h3 class="card-title fw-bold">Informasi Pembayaran</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="small text-muted d-block">No Pembayaran</label>
                        <span class="fw-semibold text-primary">{{ $payment->no_payment }}</span>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-muted d-block">Kategori</label>
                        @php
                            $categoryLabel = match ($payment->category_payment) {
                                1 => 'Registrasi',
                                2 => 'SPP',
                                3 => 'Kegiatan',
                                4 => 'Registrasi dan SPP',
                                default => '-'
                            };
                            $categoryClass = match ($payment->category_payment) {
                                1 => 'bg-primary-subtle text-primary',
                                2 => 'bg-info-subtle text-info',
                                3 => 'bg-warning-subtle text-warning',
                                4 => 'bg-success-subtle text-success',
                                default => 'bg-secondary-subtle text-secondary'
                            };
                        @endphp
                        <span class="badge {{ $categoryClass }}">{{ $categoryLabel }}</span>
                    </div>
                    <div class="col-md-12">
                        <label class="small text-muted d-block">Deskripsi</label>
                        <span class="fw-semibold">{{ $payment->description }}</span>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-muted d-block">Tanggal Pembayaran</label>
                        <span class="fw-semibold">{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}</span>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-muted d-block">Tanggal Expired</label>
                        <span class="fw-semibold">{{ $payment->expired_date ? \Carbon\Carbon::parse($payment->expired_date)->format('d M Y') : '-' }}</span>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-muted d-block">Metode Pembayaran</label>
                        <span class="fw-semibold">
                            <i class="bi {{ $payment->payment_method === 'transfer' ? 'bi-bank' : 'bi-cash-stack' }} me-1"></i>
                            {{ ucfirst($payment->payment_method) }}
                        </span>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-muted d-block">Kuota Sesi</label>
                        <span class="fw-semibold">{{ $payment->quota ?? '-' }}</span>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-muted d-block">Dari (Pembayar)</label>
                        <span class="fw-semibold">{{ $payment->from ?? '-' }}</span>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-muted d-block">Diterima Oleh</label>
                        <span class="fw-semibold">{{ $payment->receiver ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h3 class="card-title fw-bold">Jumlah Pembayaran</h3>
            </div>
            <div class="card-body text-center py-4">
                <div style="font-size: 2rem; font-weight: 800; color: #22c55e;">
                    Rp {{ number_format($payment->amount, 0, ',', '.') }}
                </div>
                <p class="text-muted mt-1">Total Dibayarkan</p>
            </div>
        </div>

        @if($payment->student)
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h3 class="card-title fw-bold">Informasi Siswa</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="small text-muted d-block">Nama Lengkap</label>
                    <span class="fw-semibold">{{ $payment->student->full_name }}</span>
                </div>
                <div class="mb-3">
                    <label class="small text-muted d-block">NIS</label>
                    <span class="fw-semibold">{{ $payment->student->nis ?? '-' }}</span>
                </div>
                <div class="mb-3">
                    <label class="small text-muted d-block">Program</label>
                    <span class="badge bg-primary-subtle text-primary">{{ $payment->student?->program_label ?? '-' }}</span>
                </div>
                <div>
                    <label class="small text-muted d-block">No. WhatsApp</label>
                    <span class="fw-semibold text-primary">{{ $payment->student->whatsapp ?? '-' }}</span>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
