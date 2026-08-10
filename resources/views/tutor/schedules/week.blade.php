@extends('tutor.layouts.app')

@section('title', 'Jadwal Mingguan - LIVO Tutor')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h1 class="fs-3 mb-1">Jadwal Mingguan</h1>
        <p class="text-muted mb-0">{{ $start->translatedFormat('d M Y') }} — {{ $end->translatedFormat('d M Y') }} · {{ $totalWeek }} sesi</p>
    </div>
    <div class="col-md-6 text-md-end mt-2 mt-md-0">
        <div class="btn-group">
            <a href="{{ route('tutor.schedules.week', ['week' => $prevWeek]) }}" class="btn btn-outline-secondary"><i class="bi bi-chevron-left"></i> Minggu Lalu</a>
            <a href="{{ route('tutor.schedules.week') }}" class="btn btn-outline-primary">Minggu Ini</a>
            <a href="{{ route('tutor.schedules.week', ['week' => $nextWeek]) }}" class="btn btn-outline-secondary">Minggu Depan <i class="bi bi-chevron-right"></i></a>
        </div>
    </div>
</div>

<div class="row g-3">
    @foreach($days as $day)
        @php
            $sessions = $sessionsByDay->get($day->toDateString(), collect());
            $isToday = $day->isToday();
            $studentTotal = $sessions->sum('count');
        @endphp
        <div class="col-12">
            <div class="card {{ $isToday ? 'border-primary' : '' }}">
                <div class="card-header bg-white py-2">
                    <h3 class="card-title mb-0 {{ $isToday ? 'text-primary' : '' }}">
                        {{ $day->translatedFormat('l, d M Y') }}
                        @if($isToday)<span class="badge bg-primary ms-2">Hari Ini</span>@endif
                        <span class="text-muted small ms-2">{{ $sesiPerDay->get($day->toDateString(), 0) }} sesi · {{ $studentTotal }} siswa</span>
                    </h3>
                </div>
                @if($sessions->isEmpty())
                    <div class="card-body py-3 text-muted small">Tidak ada jadwal.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width:130px">Sesi (Jam)</th>
                                    <th style="width:120px">Kelas / Ruang</th>
                                    <th>Mata Pelajaran</th>
                                    <th style="width:150px">Siswa</th>
                                    <th style="width:170px">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sessions as $session)
                                    <tr>
                                        <td><span class="badge bg-secondary-subtle text-secondary">{{ substr($session['start_time'], 0, 5) }}–{{ substr($session['end_time'], 0, 5) }}</span></td>
                                        <td>{{ $session['room'] }}</td>
                                        <td>{{ $session['subject'] }}</td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-lihat-siswa"
                                                data-label="{{ $day->translatedFormat('l, d M Y') }} · {{ substr($session['start_time'], 0, 5) }}–{{ substr($session['end_time'], 0, 5) }}"
                                                data-students="{{ json_encode($session['students']) }}">
                                                <i class="bi bi-people me-1"></i> Lihat Siswa ({{ $session['count'] }})
                                            </button>
                                        </td>
                                        <td>
                                            @if($session['done'] > 0)<span class="badge bg-success">{{ $session['done'] }} Selesai</span>@endif
                                            @if($session['scheduled'] > 0)<span class="badge bg-info">{{ $session['scheduled'] }} Terjadwal</span>@endif
                                            @if($session['canceled'] > 0)<span class="badge bg-danger">{{ $session['canceled'] }} Batal</span>@endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>

{{-- Modal: Daftar Siswa pada satu sesi --}}
<div class="modal fade" id="modal-siswa-sesi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Siswa — <span id="modal-siswa-label"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter mb-0">
                        <thead>
                            <tr>
                                <th>Siswa</th>
                                <th>Mata Pelajaran</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="modal-siswa-body"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    var studentHistoryUrl = '{{ route('tutor.students.show', ['student' => '__ID__']) }}';

    var statusBadge = {
        done: '<span class="badge bg-success">Selesai</span>',
        canceled: '<span class="badge bg-danger">Batal</span>',
        scheduled: '<span class="badge bg-info">Terjadwal</span>',
    };

    function esc(str) {
        return $('<div>').text(str == null ? '' : str).html();
    }

    $(document).on('click', '.btn-lihat-siswa', function () {
        var students = $(this).data('students') || [];
        $('#modal-siswa-label').text($(this).data('label'));

        var $body = $('#modal-siswa-body').empty();
        students.forEach(function (s) {
            var badge = statusBadge[s.status_schedule] || '';
            if (s.pending_eval) {
                badge += '<div><small class="text-warning">Belum dievaluasi</small></div>';
            }
            var url = studentHistoryUrl.replace('__ID__', s.id);
            $body.append(
                '<tr>' +
                '<td><div class="fw-semibold">' + esc(s.name) + '</div><small class="text-muted">' + esc(s.grade) + '</small></td>' +
                '<td>' + esc(s.subject) + '</td>' +
                '<td>' + badge + '</td>' +
                '<td class="text-end"><a href="' + url + '" class="btn btn-sm btn-outline-primary" title="History Evaluasi"><i class="bi bi-person-lines-fill"></i></a></td>' +
                '</tr>'
            );
        });

        $('#modal-siswa-sesi').modal('show');
    });
});
</script>
@endpush
