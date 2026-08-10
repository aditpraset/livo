@extends('tutor.layouts.app')

@section('title', 'Evaluasi Siswa - LIVO Tutor')

@section('content')
<div class="row mb-3">
    <div class="col-md-6">
        <h1 class="fs-3 mb-1">Evaluasi Siswa</h1>
        <p class="text-muted mb-0">
            {{ $start->translatedFormat('d M Y') }} — {{ $end->translatedFormat('d M Y') }} · {{ $totalWeek }} sesi
            {{ $mode === 'done' ? 'sudah dievaluasi' : 'belum dievaluasi' }}
        </p>
    </div>
    <div class="col-md-6 text-md-end mt-2 mt-md-0">
        <div class="btn-group">
            <a href="{{ route('tutor.evaluations.index', ['mode' => $mode, 'week' => $prevWeek]) }}" class="btn btn-outline-secondary"><i class="bi bi-chevron-left"></i> Minggu Lalu</a>
            <a href="{{ route('tutor.evaluations.index', ['mode' => $mode]) }}" class="btn btn-outline-primary">Minggu Ini</a>
            <a href="{{ route('tutor.evaluations.index', ['mode' => $mode, 'week' => $nextWeek]) }}" class="btn btn-outline-secondary">Minggu Depan <i class="bi bi-chevron-right"></i></a>
        </div>
    </div>
</div>

<div class="btn-group mb-4" role="group">
    <a href="{{ route('tutor.evaluations.index', ['mode' => 'pending', 'week' => $start->toDateString()]) }}"
        class="btn {{ $mode === 'pending' ? 'btn-primary' : 'btn-outline-primary' }}">
        <i class="bi bi-hourglass-split me-1"></i>Belum Dievaluasi
    </a>
    <a href="{{ route('tutor.evaluations.index', ['mode' => 'done', 'week' => $start->toDateString()]) }}"
        class="btn {{ $mode === 'done' ? 'btn-primary' : 'btn-outline-primary' }}">
        <i class="bi bi-check2-circle me-1"></i>Sudah Dievaluasi
    </a>
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
                    <div class="card-body py-3 text-muted small">
                        {{ $mode === 'done' ? 'Belum ada evaluasi pada hari ini.' : 'Tidak ada sesi yang perlu dievaluasi.' }}
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width:130px">Sesi (Jam)</th>
                                    <th style="width:120px">Kelas / Ruang</th>
                                    <th>Mata Pelajaran</th>
                                    <th style="width:170px">Siswa</th>
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
                                            @if($mode === 'pending' && $session['count'] > 0)
                                                <span class="text-muted small d-block mt-1">{{ $session['evaluated'] }}/{{ $session['count'] }} terisi</span>
                                            @endif
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

{{-- Modal: Daftar Siswa pada satu sesi + aksi evaluasi/feedback --}}
<div class="modal fade" id="modal-siswa-sesi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
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
                                <th>Status</th>
                                <th>Feedback</th>
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

{{-- Modal: Ubah Feedback Siswa (ringkas, tanpa buka form evaluasi lengkap) --}}
<div class="modal fade" id="modal-feedback" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Feedback Siswa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="feedback-schedule-id">
                <label class="form-label">Pilih Feedback</label>
                <select id="feedback-value" class="form-select">
                    <option value="">-- Kosongkan --</option>
                    @foreach(\App\Models\Schedule::FEEDBACK_OPTIONS as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btn-save-feedback">Simpan</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    var mode = '{{ $mode }}';
    var createUrlTemplate = '{{ route('tutor.evaluations.create', ['schedule' => '__ID__']) }}';

    function esc(str) {
        return $('<div>').text(str == null ? '' : str).html();
    }

    function statusBadge(s) {
        if (mode === 'done') {
            var map = { hadir: ['Hadir', 'bg-success'], izin: ['Izin', 'bg-warning text-dark'], alfa: ['Alfa', 'bg-danger'] };
            var v = map[s.attendance];
            return v ? '<span class="badge ' + v[1] + '">' + v[0] + '</span>' : '<span class="text-muted">—</span>';
        }
        return s.status_schedule === 'done'
            ? '<span class="badge bg-success">Selesai</span>'
            : '<span class="badge bg-warning">Lewat, belum ditandai</span>';
    }

    function feedbackBadge(s) {
        var colors = {
            buruk: 'bg-danger', kurang_baik: 'bg-danger',
            cukup_baik: 'bg-warning text-dark',
            baik: 'bg-success', sangat_baik: 'bg-success'
        };
        var badge = s.student_feedback_label
            ? '<span class="badge ' + (colors[s.student_feedback] || 'bg-secondary') + '">' + esc(s.student_feedback_label) + '</span>'
            : '<span class="text-muted">—</span>';
        return badge + ' <button type="button" class="btn btn-sm btn-link p-0 ms-1 btn-edit-feedback" ' +
            'data-id="' + s.schedule_id + '" data-feedback="' + (s.student_feedback || '') + '" title="Ubah Feedback">' +
            '<i class="bi bi-pencil-square"></i></button>';
    }

    function renderModalStudents(students) {
        var $body = $('#modal-siswa-body').empty();
        students.forEach(function (s) {
            var actionLabel = s.has_evaluation ? 'Edit Evaluasi' : 'Isi Evaluasi';
            var actionClass = s.has_evaluation ? 'btn-outline-warning' : 'btn-primary';
            var url = createUrlTemplate.replace('__ID__', s.schedule_id);
            $body.append(
                '<tr>' +
                '<td><div class="fw-semibold">' + esc(s.name) + '</div><small class="text-muted">' + esc(s.grade) + '</small></td>' +
                '<td>' + statusBadge(s) + '</td>' +
                '<td>' + feedbackBadge(s) + '</td>' +
                '<td class="text-end"><a href="' + url + '" class="btn btn-sm ' + actionClass + '">' +
                '<i class="bi bi-pencil-square me-1"></i>' + actionLabel + '</a></td>' +
                '</tr>'
            );
        });
    }

    $(document).on('click', '.btn-lihat-siswa', function () {
        var students = $(this).data('students') || [];
        $('#modal-siswa-label').text($(this).data('label'));
        renderModalStudents(students);
        $('#modal-siswa-sesi').modal('show');
    });

    /* ── Ubah Feedback Siswa langsung dari modal (tanpa buka form lengkap) ── */
    $(document).on('click', '.btn-edit-feedback', function () {
        $('#feedback-schedule-id').val($(this).data('id'));
        $('#feedback-value').val($(this).data('feedback') || '');
        $('#modal-feedback').modal('show');
    });

    $('#btn-save-feedback').on('click', function () {
        var id = $('#feedback-schedule-id').val();
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '/evaluasi/' + id + '/feedback',
            type: 'PUT',
            data: { _token: '{{ csrf_token() }}', student_feedback: $('#feedback-value').val() },
            success: function () {
                window.location.reload();
            },
            error: function (xhr) {
                Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan.', 'error');
            },
            complete: function () { $btn.prop('disabled', false); }
        });
    });
});
</script>
@endpush
