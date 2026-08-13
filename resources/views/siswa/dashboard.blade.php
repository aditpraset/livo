@extends('siswa.layouts.app')

@section('title', 'Dashboard Siswa - LIVO')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        @if($siswaMode === 'orang_tua')
            <h1 class="fs-3 mb-1">Perkembangan Belajar {{ $student->nickname ?: $student->full_name }}</h1>
            <p class="text-muted mb-0">
                Ringkasan untuk orang tua &middot; {{ $student->grade ?: '-' }}
                @if($student->program_label !== '-') &middot; {{ $student->program_label }} @endif
            </p>
        @else
            <h1 class="fs-3 mb-1">Halo, {{ $student->nickname ?: $student->full_name }} 👋</h1>
            <p class="text-muted mb-0">
                {{ $student->grade ?: '-' }}
                @if($student->program_label !== '-') &middot; {{ $student->program_label }} @endif
            </p>
        @endif
    </div>
</div>

{{-- ── Ringkasan ── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card card-sm {{ $stats['quota_sessions'] <= 2 ? 'border-warning' : '' }}">
            <div class="card-body">
                <div class="text-muted small">Sisa Kuota Sesi</div>
                <div class="fs-2 fw-bold {{ $stats['quota_sessions'] <= 2 ? 'text-warning' : 'text-success' }}">{{ $stats['quota_sessions'] }}</div>
                @if($stats['quota_sessions'] <= 2)
                    <small class="text-warning">Segera lakukan perpanjangan.</small>
                @endif
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="text-muted small">Sesi Selesai</div>
                <div class="fs-2 fw-bold">{{ $stats['done_sessions'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="text-muted small">Sesi Akan Datang</div>
                <div class="fs-2 fw-bold text-info">{{ $stats['upcoming_sessions'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="text-muted small">Rata-rata Nilai</div>
                <div class="fs-2 fw-bold text-primary">{{ $stats['avg_post_test'] ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- ── Jadwal terdekat ── --}}
    <div class="col-md-7">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h3 class="card-title fw-bold mb-0">Jadwal Terdekat</h3>
                <a href="{{ route('siswa.schedules.week') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Mata Pelajaran</th>
                            <th>Tutor</th>
                            <th>Ruang</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($upcoming as $s)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $s->class_date->translatedFormat('d M Y') }}</div>
                                    <small class="text-muted">{{ substr($s->start_time, 0, 5) }}–{{ substr($s->end_time, 0, 5) }}</small>
                                </td>
                                <td>{{ $s->subject->subject_name ?? '-' }}</td>
                                <td>{{ $s->tutor->name ?? '-' }}</td>
                                <td>{{ $s->room ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada jadwal mendatang.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Kehadiran & masa aktif ── --}}
    <div class="col-md-5">
        <div class="card mb-4">
            <div class="card-header bg-white"><h3 class="card-title fw-bold mb-0">Rekap Kehadiran</h3></div>
            <div class="card-body">
                <div class="row text-center g-2">
                    <div class="col-4">
                        <div class="text-muted small">Hadir</div>
                        <div class="fs-3 fw-bold text-success">{{ $stats['hadir'] }}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small">Izin</div>
                        <div class="fs-3 fw-bold text-warning">{{ $stats['izin'] }}</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted small">Alfa</div>
                        <div class="fs-3 fw-bold text-danger">{{ $stats['alfa'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white"><h3 class="card-title fw-bold mb-0">Masa Aktif Belajar</h3></div>
            <div class="card-body">
                @if($lastPayment && $lastPayment->expired_date)
                    @php($expired = \Carbon\Carbon::parse($lastPayment->expired_date))
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Berlaku sampai</div>
                            <div class="fs-4 fw-bold">{{ $expired->translatedFormat('d M Y') }}</div>
                        </div>
                        @if($expired->isPast())
                            <span class="badge bg-danger">Kedaluwarsa</span>
                        @else
                            <span class="badge bg-success">Aktif</span>
                        @endif
                    </div>
                    @if(!$expired->isPast())
                        <small class="text-muted">Sisa {{ (int) now()->startOfDay()->diffInDays($expired, false) }} hari lagi.</small>
                    @endif
                @else
                    <p class="text-muted small mb-0">Belum ada data masa aktif. Hubungi admin bila Anda merasa ini keliru.</p>
                @endif
                <a href="{{ route('siswa.payments.index') }}" class="btn btn-sm btn-outline-secondary mt-3">Riwayat Pembayaran</a>
            </div>
        </div>
    </div>
</div>

{{-- ── Evaluasi terbaru ── --}}
<div class="card mt-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h3 class="card-title fw-bold mb-0">Evaluasi Terbaru</h3>
        <a href="{{ route('siswa.evaluations.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua Nilai</a>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Mata Pelajaran</th>
                    <th>Materi</th>
                    <th>Kehadiran</th>
                    <th>Nilai</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentEvaluations as $s)
                    <tr>
                        <td>{{ $s->class_date->translatedFormat('d M Y') }}</td>
                        <td>{{ $s->subject->subject_name ?? '-' }}</td>
                        <td>
                            @if($m = $s->evaluation->materi_display)
                                <div class="small fw-semibold">{{ $m['pokok'] }}</div>
                                @if($m['sub'])<small class="text-muted">{{ $m['sub'] }}</small>@endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @php($att = $s->evaluation->student_attendance)
                            <span class="badge {{ $att === 'hadir' ? 'bg-success' : ($att === 'izin' ? 'bg-warning' : 'bg-danger') }}">{{ ucfirst($att) }}</span>
                        </td>
                        <td>
                            @if(($pt = $s->evaluation->post_test) !== null)
                                <span class="badge {{ $pt >= 85 ? 'bg-success' : ($pt >= 70 ? 'bg-primary' : 'bg-warning') }}">{{ $pt }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada evaluasi yang diterbitkan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
