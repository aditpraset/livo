@extends('admin.layouts.app')

@section('title', 'Laporan Evaluasi - ' . $student->full_name . ' - LIVO Admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <a href="{{ route('admin.students.show', $student->id) }}" class="btn btn-link link-secondary ps-0 mb-1">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Profil Siswa
        </a>
        <h2 class="page-title mb-0">Laporan Evaluasi</h2>
        <p class="text-muted mb-0 small">{{ $student->full_name }} — {{ $student->grade ?? '-' }}</p>
    </div>
</div>
@endsection

@section('content')
{{-- Stats Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-primary">{{ $stats['total'] }}</div>
            <div class="small text-muted">Total Sesi Selesai</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-info">{{ $stats['avg_score'] ?? '—' }}</div>
            <div class="small text-muted">Rata-rata Nilai</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-success">{{ $stats['hadir'] }}</div>
            <div class="small text-muted">Hadir</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-warning">{{ $stats['izin'] }}</div>
            <div class="small text-muted">Izin</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card border-0 shadow-sm text-center py-3">
            <div class="fs-2 fw-bold text-danger">{{ $stats['alfa'] }}</div>
            <div class="small text-muted">Alfa</div>
        </div>
    </div>
</div>

{{-- Tabel Evaluasi --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0">Rincian Evaluasi Per Sesi</h5>
        @if($stats['evaluated'] < $stats['total'])
            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                {{ $stats['total'] - $stats['evaluated'] }} sesi belum dievaluasi
            </span>
        @endif
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="py-3">Tanggal</th>
                        <th class="py-3">Mata Pelajaran</th>
                        <th class="py-3">Tutor</th>
                        <th class="py-3 text-center">Kehadiran</th>
                        <th class="py-3 text-center">Nilai</th>
                        <th class="py-3">Catatan Tutor</th>
                        <th class="py-3 text-center">Laporan</th>
                        <th class="py-3 text-center px-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $i => $schedule)
                    <tr>
                        <td class="px-4">{{ $i + 1 }}</td>
                        <td class="text-nowrap">
                            <div class="fw-semibold">{{ \Carbon\Carbon::parse($schedule->class_date)->translatedFormat('d M Y') }}</div>
                            <small class="text-muted">{{ substr($schedule->start_time, 0, 5) }} – {{ substr($schedule->end_time, 0, 5) }}</small>
                        </td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                {{ $schedule->subject->subject_name ?? '-' }}
                            </span>
                        </td>
                        <td>{{ $schedule->tutor->name ?? '-' }}</td>

                        @if($schedule->evaluation)
                        <td class="text-center">
                            @php
                                $attBadge = match($schedule->evaluation->student_attendance) {
                                    'hadir' => 'bg-success',
                                    'izin'  => 'bg-warning text-dark',
                                    'alfa'  => 'bg-danger',
                                    default => 'bg-secondary'
                                };
                                $attLabel = ucfirst($schedule->evaluation->student_attendance);
                            @endphp
                            <span class="badge {{ $attBadge }}">{{ $attLabel }}</span>
                        </td>
                        <td class="text-center fw-bold">
                            @if($schedule->evaluation->score !== null)
                                <span class="badge bg-light text-dark border fs-6">{{ $schedule->evaluation->score }}</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td class="text-muted small" style="max-width:250px;">
                            {{ $schedule->evaluation->tutor_notes ?? '—' }}
                        </td>
                        <td class="text-center">
                            @if($schedule->evaluation->is_published)
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="bi bi-send-check me-1"></i>Diterbitkan
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border">
                                    <i class="bi bi-eye-slash me-1"></i>Privat
                                </span>
                            @endif
                        </td>
                        <td class="text-center px-4">
                            <button class="btn btn-sm btn-outline-primary btn-toggle-publish"
                                data-id="{{ $schedule->evaluation->id }}"
                                data-published="{{ $schedule->evaluation->is_published ? 1 : 0 }}">
                                @if($schedule->evaluation->is_published)
                                    <i class="bi bi-eye-slash me-1"></i>Sembunyikan
                                @else
                                    <i class="bi bi-send me-1"></i>Terbitkan
                                @endif
                            </button>
                        </td>
                        @else
                        <td colspan="5" class="text-center text-muted small py-3">
                            <i class="bi bi-hourglass me-1"></i>Belum ada evaluasi untuk sesi ini.
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-clipboard2-x fs-2 d-block mb-2"></i>
                            Belum ada sesi belajar yang selesai untuk siswa ini.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $(document).on('click', '.btn-toggle-publish', function () {
        var btn         = $(this);
        var id          = btn.data('id');
        var isPublished = parseInt(btn.data('published'));
        var action      = isPublished ? 'Sembunyikan' : 'Terbitkan';
        var text        = isPublished
            ? 'Laporan tidak akan tampil ke orang tua.'
            : 'Laporan evaluasi akan dikirimkan / tampil ke orang tua.';

        Swal.fire({
            title: action + ' Laporan?',
            text: text,
            icon: 'question', showCancelButton: true,
            confirmButtonText: 'Ya, ' + action, cancelButtonText: 'Batal'
        }).then(function (r) {
            if (r.isConfirmed) {
                $.ajax({
                    url: '/admin/evaluations/' + id + '/publish', type: 'PUT',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false })
                            .then(function () { location.reload(); });
                    },
                    error: function () {
                        Swal.fire('Gagal', 'Terjadi kesalahan server.', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
