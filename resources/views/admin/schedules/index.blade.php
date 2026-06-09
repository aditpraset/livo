@extends('admin.layouts.app')

@section('title', 'Penjadwalan - LIVO Admin')

@push('styles')
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet'>
<style>
    #calendar { min-height: 650px; }
    .fc-event { cursor: pointer; }
    .fc-event-title { font-weight: 600; }
    .legend-dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; }
</style>
@endpush

@section('page-header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h2 class="page-title">Penjadwalan Belajar</h2>
        <p class="text-muted mb-0 small">Atur dan pantau seluruh sesi belajar siswa</p>
    </div>
    <button class="btn btn-primary" id="btn-add-schedule">
        <i class="bi bi-plus-lg me-1"></i> Tambah Jadwal
    </button>
</div>
@endsection

@section('content')
{{-- Tabs --}}
<ul class="nav nav-tabs mb-4" id="scheduleTabs">
    <li class="nav-item">
        <button class="nav-link active fw-semibold" id="tab-list-btn" data-bs-toggle="tab" data-bs-target="#tab-list" type="button">
            <i class="bi bi-list-ul me-1"></i> Daftar Jadwal
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link fw-semibold" id="tab-calendar-btn" data-bs-toggle="tab" data-bs-target="#tab-calendar" type="button">
            <i class="bi bi-calendar3 me-1"></i> Kalender
        </button>
    </li>
</ul>

<div class="tab-content">
    {{-- Tab: Daftar Jadwal --}}
    <div class="tab-pane fade show active" id="tab-list">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <table id="schedules-table" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th width="50">#</th>
                            <th>Siswa</th>
                            <th>Tutor</th>
                            <th>Mata Pelajaran</th>
                            <th>Tanggal</th>
                            <th>Jam</th>
                            <th>Status</th>
                            <th>Evaluasi</th>
                            <th width="160" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tab: Kalender --}}
    <div class="tab-pane fade" id="tab-calendar">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-3">
                {{-- Legend --}}
                <div class="d-flex gap-4 mb-3 flex-wrap">
                    <span><span class="legend-dot bg-primary me-1"></span> Dijadwalkan</span>
                    <span><span class="legend-dot bg-success me-1"></span> Selesai</span>
                    <span><span class="legend-dot bg-secondary me-1"></span> Dibatalkan</span>
                </div>
                <div id="calendar"></div>
            </div>
        </div>
    </div>
</div>

{{-- ========== MODAL: Tambah / Edit Jadwal ========== --}}
<div class="modal fade" id="modal-schedule" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-schedule-title">Tambah Jadwal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="schedule-id">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Siswa <span class="text-danger">*</span></label>
                        <select id="field-student" class="form-select">
                            <option value="">-- Pilih Siswa --</option>
                            @foreach($students as $s)
                                <option value="{{ $s->id }}">{{ $s->full_name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="err-student"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tutor <span class="text-danger">*</span></label>
                        <select id="field-tutor" class="form-select">
                            <option value="">-- Pilih Tutor --</option>
                            @foreach($tutors as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="err-tutor"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Mata Pelajaran <span class="text-danger">*</span></label>
                        <select id="field-subject" class="form-select">
                            <option value="">-- Pilih Mapel --</option>
                            @foreach($subjects as $sub)
                                <option value="{{ $sub->id }}">{{ $sub->subject_name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="err-subject"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" id="field-date" class="form-control">
                        <div class="invalid-feedback" id="err-date"></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Sesi Pembelajaran <span class="text-danger">*</span></label>
                        <select id="field-session" class="form-select">
                            <option value="">-- Pilih Sesi --</option>
                            @foreach($scheduleSessions as $session)
                                <option value="{{ $session->id }}"
                                    data-start="{{ substr($session->time_start, 0, 5) }}"
                                    data-end="{{ substr($session->time_end, 0, 5) }}">
                                    {{ $session->name }} ({{ substr($session->time_start, 0, 5) }} – {{ substr($session->time_end, 0, 5) }})
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="err-session"></div>
                        <div class="form-text" id="session-time-display"></div>
                        <input type="hidden" id="field-start">
                        <input type="hidden" id="field-end">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btn-save-schedule">Simpan Jadwal</button>
            </div>
        </div>
    </div>
</div>

{{-- ========== MODAL: Evaluasi ========== --}}
<div class="modal fade" id="modal-evaluation" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info bg-opacity-10">
                <h5 class="modal-title"><i class="bi bi-clipboard2-check me-2"></i>Evaluasi Sesi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="eval-schedule-id">
                <div class="alert alert-light border mb-3" id="eval-info"></div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Kehadiran Siswa <span class="text-danger">*</span></label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="student_attendance" id="att-hadir" value="hadir">
                            <label class="form-check-label" for="att-hadir"><span class="badge bg-success">Hadir</span></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="student_attendance" id="att-izin" value="izin">
                            <label class="form-check-label" for="att-izin"><span class="badge bg-warning text-dark">Izin</span></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="student_attendance" id="att-alfa" value="alfa">
                            <label class="form-check-label" for="att-alfa"><span class="badge bg-danger">Alfa</span></label>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nilai (0–100)</label>
                    <input type="number" id="eval-score" class="form-control" min="0" max="100" placeholder="Kosongkan jika tidak ada kuis">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Catatan Tutor</label>
                    <textarea id="eval-notes" class="form-control" rows="3" placeholder="Perkembangan belajar siswa, materi yang dipelajari, dll."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-info text-white" id="btn-save-eval">Simpan Evaluasi</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script>
$(function () {
    /* ---- DataTable ---- */
    var table = $('#schedules-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.schedules.data') }}",
        order: [[4, 'desc']],
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'student_name' },
            { data: 'tutor_name' },
            { data: 'subject_name' },
            { data: 'class_date' },
            { data: 'time', orderable: false },
            { data: 'status_schedule', orderable: false },
            { data: 'evaluation_status', orderable: false },
            { data: 'action', orderable: false, searchable: false, className: 'text-center' },
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json' }
    });

    /* ---- FullCalendar ---- */
    var calendar;
    $('#tab-calendar-btn').on('shown.bs.tab', function () {
        if (calendar) { calendar.render(); return; }
        calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
            initialView: 'dayGridMonth',
            locale: 'id',
            height: 650,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: "{{ route('admin.schedules.events') }}",
            eventClick: function (info) {
                var p = info.event.extendedProps;
                var statusLabel = { scheduled: 'Dijadwalkan', done: 'Selesai', canceled: 'Dibatalkan' }[p.status] || p.status;
                Swal.fire({
                    title: info.event.title,
                    html: '<div class="text-start">' +
                          '<p class="mb-1"><b>Tutor:</b> ' + p.tutor + '</p>' +
                          '<p class="mb-1"><b>Mapel:</b> ' + p.subject + '</p>' +
                          '<p class="mb-1"><b>Status:</b> ' + statusLabel + '</p>' +
                          '</div>',
                    icon: 'info',
                    confirmButtonText: 'Tutup'
                });
            }
        });
        calendar.render();
    });

    /* ---- Map student_id → schedule_session_id (dari data pendaftaran) ---- */
    var studentDefaultSession = {
        @foreach($students as $s)
            {{ $s->id }}: {{ $s->schedule_session_id ?? 'null' }},
        @endforeach
    };

    /* ---- Saat siswa dipilih, auto-set sesi default pendaftarannya ---- */
    $('#field-student').on('change', function () {
        var studentId  = $(this).val();
        var sessionId  = studentId ? studentDefaultSession[studentId] : null;
        if (sessionId) {
            $('#field-session').val(sessionId).trigger('change');
        } else {
            $('#field-session').val('').trigger('change');
        }
    });

    /* ---- Auto-fill jam dari sesi yang dipilih ---- */
    $(document).on('change', '#field-session', function () {
        var opt   = $(this).find('option:selected');
        var start = opt.data('start') || '';
        var end   = opt.data('end')   || '';
        $('#field-start').val(start);
        $('#field-end').val(end);
        if (start && end) {
            $('#session-time-display').html('<i class="bi bi-clock me-1"></i>Jam: <strong>' + start + ' – ' + end + '</strong>');
        } else {
            $('#session-time-display').text('');
        }
    });

    /* ---- Helper: clear schedule modal errors ---- */
    function resetScheduleModal() {
        $('#schedule-id').val('');
        $('#field-student, #field-tutor, #field-subject, #field-session').val('').removeClass('is-invalid');
        $('#field-date, #field-start, #field-end').val('');
        $('#err-student, #err-tutor, #err-subject, #err-session, #err-date, #err-start, #err-end').text('');
        $('#session-time-display').text('');
    }

    /* ---- Tambah Jadwal ---- */
    $('#btn-add-schedule').on('click', function () {
        resetScheduleModal();
        $('#modal-schedule-title').text('Tambah Jadwal');
        $('#modal-schedule').modal('show');
    });

    /* ---- Edit Jadwal ---- */
    $(document).on('click', '.btn-edit', function () {
        var id = $(this).data('id');
        resetScheduleModal();
        $('#modal-schedule-title').text('Edit Jadwal');
        $.get('/admin/schedules/' + id, function (data) {
            var savedStart = data.start_time.substring(0, 5);
            var savedEnd   = data.end_time.substring(0, 5);

            $('#schedule-id').val(data.id);
            $('#field-student').val(data.student_id);
            $('#field-tutor').val(data.tutor_id);
            $('#field-subject').val(data.subject_id);
            $('#field-date').val(data.class_date.substring(0, 10));

            // Preselect sesi yang jamnya cocok dengan data tersimpan
            var matchedOpt = $('#field-session option').filter(function () {
                return $(this).data('start') === savedStart && $(this).data('end') === savedEnd;
            });
            if (matchedOpt.length) {
                $('#field-session').val(matchedOpt.val());
                $('#session-time-display').html('<i class="bi bi-clock me-1"></i>Jam: <strong>' + savedStart + ' – ' + savedEnd + '</strong>');
            } else {
                // Sesi tidak ada di master → tampilkan tetap sebagai info
                $('#session-time-display').html('<i class="bi bi-clock me-1 text-warning"></i>Jam tersimpan: <strong>' + savedStart + ' – ' + savedEnd + '</strong> (pilih sesi untuk mengubah)');
            }
            $('#field-start').val(savedStart);
            $('#field-end').val(savedEnd);

            $('#modal-schedule').modal('show');
        });
    });

    /* ---- Simpan Jadwal ---- */
    $('#btn-save-schedule').on('click', function () {
        var id   = $('#schedule-id').val();
        var url  = id ? '/admin/schedules/' + id : '{{ route("admin.schedules.store") }}';
        var type = id ? 'PUT' : 'POST';

        $.ajax({
            url: url, type: type,
            data: {
                student_id: $('#field-student').val(),
                tutor_id:   $('#field-tutor').val(),
                subject_id: $('#field-subject').val(),
                class_date: $('#field-date').val(),
                start_time: $('#field-start').val(),
                end_time:   $('#field-end').val(),
                _token:     '{{ csrf_token() }}'
            },
            success: function (res) {
                $('#modal-schedule').modal('hide');
                table.ajax.reload();
                if (calendar) calendar.refetchEvents();
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false });
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    var err = xhr.responseJSON.errors ?? {};
                    var msg = xhr.responseJSON.message ?? '';
                    if (err.student_id) { $('#field-student').addClass('is-invalid'); $('#err-student').text(err.student_id[0]); }
                    if (err.tutor_id)   { $('#field-tutor').addClass('is-invalid');   $('#err-tutor').text(err.tutor_id[0]); }
                    if (err.subject_id) { $('#field-subject').addClass('is-invalid'); $('#err-subject').text(err.subject_id[0]); }
                    if (err.class_date) { $('#field-date').addClass('is-invalid');    $('#err-date').text(err.class_date[0]); }
                    if (err.start_time || err.end_time) { $('#field-session').addClass('is-invalid'); $('#err-session').text('Pilih sesi pembelajaran terlebih dahulu.'); }
                    if (msg && !Object.keys(err).length) Swal.fire('Gagal', msg, 'error');
                } else {
                    Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan.', 'error');
                }
            }
        });
    });

    /* ---- Tandai Selesai ---- */
    $(document).on('click', '.btn-done', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Tandai Sesi Selesai?',
            text: 'Status akan berubah menjadi "Selesai" dan kuota siswa akan berkurang 1.',
            icon: 'question', showCancelButton: true,
            confirmButtonText: 'Ya, Selesai', cancelButtonText: 'Batal'
        }).then(function (r) {
            if (r.isConfirmed) {
                $.ajax({
                    url: '/admin/schedules/' + id + '/status', type: 'PUT',
                    data: { status: 'done', _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        table.ajax.reload();
                        if (calendar) calendar.refetchEvents();
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false });
                    }
                });
            }
        });
    });

    /* ---- Batalkan ---- */
    $(document).on('click', '.btn-cancel', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Batalkan Jadwal?',
            text: 'Jadwal ini akan ditandai sebagai Dibatalkan.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#6c757d', confirmButtonText: 'Ya, Batalkan', cancelButtonText: 'Tidak'
        }).then(function (r) {
            if (r.isConfirmed) {
                $.ajax({
                    url: '/admin/schedules/' + id + '/status', type: 'PUT',
                    data: { status: 'canceled', _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        table.ajax.reload();
                        if (calendar) calendar.refetchEvents();
                        Swal.fire({ icon: 'success', title: 'Dibatalkan', text: res.message, timer: 2000, showConfirmButton: false });
                    }
                });
            }
        });
    });

    /* ---- Hapus Jadwal ---- */
    $(document).on('click', '.btn-delete', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Hapus Jadwal?', text: 'Data jadwal akan dihapus permanen.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal'
        }).then(function (r) {
            if (r.isConfirmed) {
                $.ajax({
                    url: '/admin/schedules/' + id, type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        table.ajax.reload();
                        if (calendar) calendar.refetchEvents();
                        Swal.fire({ icon: 'success', title: 'Dihapus', text: res.message, timer: 2000, showConfirmButton: false });
                    }
                });
            }
        });
    });

    /* ---- Buka Modal Evaluasi ---- */
    $(document).on('click', '.btn-evaluate', function () {
        var id = $(this).data('id');
        $.get('/admin/schedules/' + id, function (data) {
            var dateStr = new Date(data.class_date).toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            $('#eval-schedule-id').val(data.id);
            $('#eval-info').html(
                '<div class="row g-2 small">' +
                '<div class="col-6"><b>Siswa:</b> ' + (data.student?.full_name ?? '-') + '</div>' +
                '<div class="col-6"><b>Tutor:</b> ' + (data.tutor?.name ?? '-') + '</div>' +
                '<div class="col-6"><b>Mapel:</b> ' + (data.subject?.subject_name ?? '-') + '</div>' +
                '<div class="col-6"><b>Tanggal:</b> ' + dateStr + '</div>' +
                '</div>'
            );
            // Reset
            $('input[name="student_attendance"]').prop('checked', false);
            $('#eval-score').val('');
            $('#eval-notes').val('');
            // Pre-fill if evaluation exists
            if (data.evaluation) {
                $('input[name="student_attendance"][value="' + data.evaluation.student_attendance + '"]').prop('checked', true);
                $('#eval-score').val(data.evaluation.score ?? '');
                $('#eval-notes').val(data.evaluation.tutor_notes ?? '');
            }
            $('#modal-evaluation').modal('show');
        });
    });

    /* ---- Simpan Evaluasi ---- */
    $('#btn-save-eval').on('click', function () {
        var attendance = $('input[name="student_attendance"]:checked').val();
        if (!attendance) { Swal.fire('Perhatian', 'Kehadiran siswa harus dipilih.', 'warning'); return; }

        $.ajax({
            url: '{{ route("admin.evaluations.store") }}', type: 'POST',
            data: {
                schedule_id:        $('#eval-schedule-id').val(),
                student_attendance: attendance,
                score:              $('#eval-score').val() || null,
                tutor_notes:        $('#eval-notes').val(),
                _token:             '{{ csrf_token() }}'
            },
            success: function (res) {
                $('#modal-evaluation').modal('hide');
                table.ajax.reload();
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false });
            },
            error: function (xhr) {
                Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan.', 'error');
            }
        });
    });
});
</script>
@endpush
