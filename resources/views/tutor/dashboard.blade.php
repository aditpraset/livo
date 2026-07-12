@extends('tutor.layouts.app')

@section('title', 'Dashboard Tutor - LIVO')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="fs-3 mb-1">Halo, {{ $tutor->name }} 👋</h1>
        <p class="text-muted mb-0">Ringkasan aktivitas pengajaran Anda.</p>
    </div>
</div>

{{-- ── Akumulasi sesi & siswa ── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-sm">
            <div class="card-body">
                <div class="text-muted small">Total Sesi Selesai</div>
                <div class="fs-2 fw-bold">{{ $stats['total_sessions'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-sm">
            <div class="card-body">
                <div class="text-muted small">Sesi Bulan Ini</div>
                <div class="fs-2 fw-bold text-primary">{{ $stats['month_sessions'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-sm">
            <div class="card-body">
                <div class="text-muted small">Sesi Akan Datang</div>
                <div class="fs-2 fw-bold text-info">{{ $stats['upcoming_sessions'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-sm">
            <div class="card-body">
                <div class="text-muted small">Total Siswa Diajar</div>
                <div class="fs-2 fw-bold">{{ $stats['total_students'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-sm">
            <div class="card-body">
                <div class="text-muted small">Siswa Bulan Ini</div>
                <div class="fs-2 fw-bold text-primary">{{ $stats['month_students'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-sm {{ $stats['pending_evaluations'] > 0 ? 'border-warning' : '' }}">
            <div class="card-body">
                <div class="text-muted small">Evaluasi Belum Diisi</div>
                <div class="fs-2 fw-bold {{ $stats['pending_evaluations'] > 0 ? 'text-warning' : 'text-success' }}">{{ $stats['pending_evaluations'] }}</div>
                @if($stats['pending_evaluations'] > 0)
                    <a href="{{ route('tutor.evaluations.index') }}" class="small">Isi sekarang →</a>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── Review hasil penilaian ── --}}
<div class="row g-4">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header bg-white">
                <h3 class="card-title fw-bold mb-0">Review Hasil Penilaian</h3>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-4 text-center">
                        <div class="text-muted small">Rata-rata Post Test</div>
                        <div class="fs-2 fw-bold">{{ $review['avg_post_test'] ?? '—' }}</div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="text-muted small">Sesi Dievaluasi</div>
                        <div class="fs-2 fw-bold">{{ $review['evaluated'] }}</div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="text-muted small">Terbit ke Orang Tua</div>
                        <div class="fs-2 fw-bold">{{ $review['published'] }}</div>
                    </div>
                </div>

                @php
                    $aspek = [
                        'Pemahaman' => $review['avg_pemahaman'],
                        'Kemampuan Analisa' => $review['avg_analisa'],
                        'Kemampuan Hafalan' => $review['avg_hafalan'],
                        'Kepercayaan Diri' => $review['avg_kepercayaan'],
                    ];
                @endphp
                @foreach($aspek as $label => $nilai)
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small">
                            <span>{{ $label }}</span>
                            <span class="fw-semibold">{{ $nilai ?? '—' }}</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar {{ ($nilai ?? 0) >= 85 ? 'bg-success' : (($nilai ?? 0) >= 70 ? 'bg-primary' : 'bg-warning') }}" style="width: {{ $nilai ?? 0 }}%"></div>
                        </div>
                    </div>
                @endforeach

                <div class="d-flex gap-3 mt-3 small">
                    <span><span class="badge bg-success me-1">&nbsp;</span>Hadir: {{ $review['hadir'] }}</span>
                    <span><span class="badge bg-warning me-1">&nbsp;</span>Izin: {{ $review['izin'] }}</span>
                    <span><span class="badge bg-danger me-1">&nbsp;</span>Alfa: {{ $review['alfa'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card">
            <div class="card-header bg-white">
                <h3 class="card-title fw-bold mb-0">Evaluasi Terbaru</h3>
            </div>
            <div class="list-group list-group-flush">
                @forelse($recentEvaluations as $ev)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">{{ $ev->schedule->student->full_name ?? '-' }}</div>
                                <small class="text-muted">
                                    {{ $ev->schedule->subject->subject_name ?? '-' }} ·
                                    {{ \Carbon\Carbon::parse($ev->schedule->class_date)->translatedFormat('d M Y') }}
                                </small>
                            </div>
                            <div class="text-end">
                                @if($ev->post_test !== null)
                                    <span class="badge {{ $ev->post_test >= 85 ? 'bg-success' : ($ev->post_test >= 70 ? 'bg-primary' : 'bg-warning') }}">{{ $ev->post_test }}</span>
                                @endif
                                <div><small class="text-muted">{{ $ev->is_published ? 'Terbit' : 'Draft' }}</small></div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="list-group-item text-center text-muted py-4">Belum ada evaluasi.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
