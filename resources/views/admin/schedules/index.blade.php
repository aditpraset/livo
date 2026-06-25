@extends('admin.layouts.app')

@section('title', 'Penjadwalan - LIVO Admin')

@push('css')
{{-- FullCalendar + Bootstrap 5 theme --}}
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet'>
{{-- Bootstrap Icons sudah ada di layout, tapi pastikan tersedia untuk FC --}}
<style>
/* ─── Custom wrapper (Google Calendar inspired) ─────────────────── */
.gcal-card {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(60,64,67,.12), 0 2px 8px rgba(60,64,67,.08);
    border: 1px solid #e0e0e0;
    background: #fff;
}

/* ─── Topbar: prev/next · today · title · views ─────────────────── */
.gcal-topbar {
    display: flex;
    align-items: center;
    padding: 10px 16px;
    gap: 10px;
    border-bottom: 1px solid #e0e0e0;
    background: #fff;
    flex-wrap: nowrap;
}
.gcal-nav { display: flex; align-items: center; gap: 4px; flex-shrink: 0; }

.gcal-btn-nav {
    width: 30px; height: 30px; padding: 0;
    border: 1px solid #dadce0; border-radius: 50%;
    background: #fff; color: #444746; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem; line-height: 1; transition: background .12s;
}
.gcal-btn-nav:hover { background: #f1f3f4; }

.gcal-btn-today {
    padding: 5px 15px; line-height: 1.5;
    border: 1px solid #dadce0; border-radius: 4px;
    background: #fff; color: #444746; cursor: pointer;
    font-size: .82rem; font-weight: 500; white-space: nowrap;
    transition: background .12s;
}
.gcal-btn-today:hover { background: #f1f3f4; }

.gcal-title {
    flex: 1; text-align: center;
    font-size: 1.1rem; font-weight: 600; color: #202124;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

/* View switcher – styled like Bootstrap 5 btn-group outline */
.gcal-views {
    display: flex; flex-shrink: 0;
    border: 1px solid #dee2e6; border-radius: 6px; overflow: hidden;
}
.gcal-view-btn {
    padding: 5px 14px; font-size: .8rem; font-weight: 500;
    color: #495057; background: #fff; border: none;
    border-left: 1px solid #dee2e6; cursor: pointer;
    white-space: nowrap; transition: background .12s, color .12s;
}
.gcal-view-btn:first-child { border-left: none; }
.gcal-view-btn:hover  { background: #f8f9fa; }
.gcal-view-btn.active { background: #0d6efd !important; color: #fff !important; font-weight: 600; }

/* ─── Filter bar ─────────────────────────────────────────────────── */
.gcal-filterbar {
    display: flex;
    align-items: center;
    padding: 7px 16px;
    gap: 8px;
    border-bottom: 1px solid #e0e0e0;
    background: #f8f9fa;
    flex-wrap: nowrap;
}
.gcal-filterbar .form-select {
    font-size: .8rem;
    border-color: #ced4da;
    color: #495057;
}
.gcal-filterbar .form-select:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 .2rem rgba(13,110,253,.15);
}

/* ─── Legend ─────────────────────────────────────────────────────── */
.gcal-legend { display: flex; align-items: center; gap: 12px; margin-left: auto; flex-shrink: 0; }
.gcal-legend-item { display: flex; align-items: center; gap: 5px; font-size: .78rem; color: #6c757d; white-space: nowrap; }
.gcal-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }

/* ─── FullCalendar: tweaks on top of Bootstrap 5 theme ──────────── */
.fc { font-family: inherit; }
.fc .fc-toolbar { display: none !important; } /* hide FC's own toolbar */

/* Column headers */
.fc-col-header-cell-cushion {
    font-size: .75rem; font-weight: 600; text-transform: uppercase;
    letter-spacing: .4px; text-decoration: none !important;
    color: #6c757d;
}

/* Day number */
.fc-daygrid-day-number { font-size: .82rem; text-decoration: none !important; }

/* Today: circular highlight (Bootstrap primary) */
.fc-day-today .fc-daygrid-day-number {
    background: #0d6efd; color: #fff !important;
    border-radius: 50%; width: 26px; height: 26px;
    display: flex !important; align-items: center; justify-content: center;
    margin: 3px; font-weight: 600;
}

/* Events */
.fc-event { cursor: pointer; border: none !important; border-radius: 4px !important; }
.fc-daygrid-event { padding: 1px 5px !important; }
.fc-event-title { font-size: .78rem; font-weight: 600; }
.fc-event-time  { font-size: .71rem; opacity: .9; }

/* "+N more" link */
.fc-daygrid-more-link { font-size: .75rem; font-weight: 500; }

/* Time grid */
.fc-timegrid-slot       { height: 36px; }
.fc-timegrid-slot-label { font-size: .73rem; }
.fc-timegrid-now-indicator-line  { border-color: #dc3545 !important; }
.fc-timegrid-now-indicator-arrow { border-top-color: #dc3545 !important; border-bottom-color: #dc3545 !important; }

/* List view */
.fc-list-event:hover td  { cursor: pointer; }
.fc-list-event-title a   { color: inherit !important; text-decoration: none !important; }

/* ─── Swal detail table ──────────────────────────────────────────── */
.swal-sched-tbl td:first-child { color: #6c757d; font-weight: 500; white-space: nowrap; padding-right: 12px; font-size: .84rem; }
.swal-sched-tbl td:last-child  { font-size: .84rem; }
</style>
@endpush('css')

@section('page-header')
<div class="d-flex justify-content-between align-items-center p-4">
    <div>
        <h2 class="page-title">Penjadwalan Belajar</h2>
        <p class="text-muted mb-0 small">Atur dan pantau seluruh sesi belajar siswa</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-success" href="{{ route('admin.schedules.evaluation-template') }}">
            <i class="bi bi-download me-1"></i> Template Evaluasi
        </a>
        <button class="btn btn-success" id="btn-import-eval">
            <i class="bi bi-file-earmark-excel me-1"></i> Upload Evaluasi
        </button>
        <button class="btn btn-outline-success" id="btn-generate-schedule">
            <i class="bi bi-calendar2-week me-1"></i> Generate Jadwal
        </button>
        <button class="btn btn-primary" id="btn-add-schedule">
            <i class="bi bi-plus-lg me-1"></i> Tambah Jadwal
        </button>
    </div>
</div>
@endsection

@section('content')
<div class="gcal-card">

    {{-- ── Top bar: prev/next · today · title · view switcher ── --}}
    <div class="gcal-topbar">
        <div class="gcal-nav">
            <button class="gcal-btn-nav" id="gcal-prev" title="Sebelumnya"><i class="bi bi-chevron-left"></i></button>
            <button class="gcal-btn-nav" id="gcal-next" title="Berikutnya"><i class="bi bi-chevron-right"></i></button>
            <button class="gcal-btn-today" id="gcal-today">Hari Ini</button>
        </div>

        <div class="gcal-title" id="gcal-title">—</div>

        <div class="gcal-views" id="gcal-view-switcher">
            <button type="button" class="gcal-view-btn active" data-fc-view="dayGridMonth">Bulan</button>
            <button type="button" class="gcal-view-btn" data-fc-view="timeGridWeek">Minggu</button>
            <button type="button" class="gcal-view-btn" data-fc-view="timeGridDay">Hari</button>
            <button type="button" class="gcal-view-btn" data-fc-view="listWeek">Agenda</button>
        </div>
    </div>

    {{-- ── Filter bar + legend ── --}}
    <div class="gcal-filterbar">
        <i class="bi bi-funnel-fill" style="color:#70757a;font-size:.82rem;flex-shrink:0"></i>
        <select id="cal-filter-status" class="form-select form-select-sm" style="width:148px">
            <option value="">Semua Status</option>
            <option value="scheduled">Dijadwalkan</option>
            <option value="done">Selesai</option>
            <option value="canceled">Dibatalkan</option>
        </select>
        <select id="cal-filter-tutor" class="form-select form-select-sm" style="width:172px">
            <option value="">Semua Tutor</option>
            @foreach($tutors as $t)
                <option value="{{ $t->id }}">{{ $t->name }}</option>
            @endforeach
        </select>

        <div class="gcal-legend">
            <span class="gcal-legend-item"><span class="gcal-dot" style="background:#4299e1"></span>Dijadwalkan</span>
            <span class="gcal-legend-item"><span class="gcal-dot" style="background:#2fb344"></span>Selesai</span>
            <span class="gcal-legend-item"><span class="gcal-dot" style="background:#9ca3af"></span>Dibatalkan</span>
        </div>
    </div>

    {{-- ── Calendar ── --}}
    <div id="calendar"></div>

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
                        <label class="form-label fw-semibold">Ruang Kelas</label>
                        <input type="text" id="field-room" class="form-control" placeholder="cth: Ruang A">
                        <div class="invalid-feedback" id="err-room"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" id="field-date" class="form-control">
                        <div class="invalid-feedback" id="err-date"></div>
                    </div>
                    <div class="col-md-6">
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

{{-- ========== MODAL: Generate Jadwal ========== --}}
<div class="modal fade" id="modal-generate" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success bg-opacity-10">
                <h5 class="modal-title"><i class="bi bi-calendar2-week me-2 text-success"></i>Generate Jadwal Mingguan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info border-0 bg-info bg-opacity-10 py-2 px-3 mb-3" style="font-size:.84rem">
                    <i class="bi bi-info-circle me-1"></i>
                    Jadwal akan digenerate otomatis untuk <strong>semua siswa aktif</strong> yang memiliki data hari dan sesi belajar.
                    Tutor dan materi dapat diisi setelah generate dengan klik event di kalender.
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Pilih Tanggal <span class="text-danger">*</span></label>
                    <input type="date" id="gen-week-date" class="form-control" value="{{ date('Y-m-d') }}">
                    <div class="form-text">Pilih tanggal mana saja dalam minggu yang diinginkan.</div>
                    <div class="invalid-feedback" id="err-gen-week"></div>
                </div>

                <div id="gen-week-preview" class="alert alert-success bg-success bg-opacity-10 border-0 py-2 px-3" style="font-size:.84rem">
                    <i class="bi bi-calendar-week me-1 text-success"></i>
                    Minggu: <strong id="gen-week-range">–</strong>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" id="btn-do-generate">
                    <i class="bi bi-calendar2-check me-1"></i> Generate Semua
                </button>
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
                    <label class="form-label fw-semibold">Sub Pokok Bahasan</label>
                    <select id="eval-syllabus" class="form-select">
                        <option value="">— Pilih materi sesuai silabus mapel —</option>
                        <option value="__other__">Lainnya (isi manual)</option>
                    </select>
                    <small class="text-muted" id="eval-syllabus-empty" style="display:none;">
                        Belum ada silabus untuk mata pelajaran ini di kelas siswa.
                    </small>
                    <input type="text" id="eval-materi-manual" class="form-control mt-2" style="display:none;"
                        placeholder="Tulis materi / sub pokok bahasan secara manual">
                </div>
                {{-- Pemahaman disembunyikan dari pengisian (tetap ada agar skrip aman) --}}
                <input type="hidden" id="eval-pemahaman">
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Nilai (1–100)</label>
                        <input type="number" id="eval-posttest" class="form-control" min="1" max="100" placeholder="1–100">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Kemampuan Analisa (1–100)</label>
                        <input type="number" id="eval-analisa" class="form-control" min="1" max="100" placeholder="1–100">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Kemampuan Hafalan (1–100)</label>
                        <input type="number" id="eval-hafalan" class="form-control" min="1" max="100" placeholder="1–100">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Kepercayaan Diri (1–100)</label>
                        <input type="number" id="eval-kepercayaan" class="form-control" min="1" max="100" placeholder="1–100">
                    </div>
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

{{-- ========== MODAL: Upload Jadwal & Evaluasi ========== --}}
<div class="modal fade" id="modal-import-eval" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success bg-opacity-10">
                <h5 class="modal-title"><i class="bi bi-file-earmark-excel me-2"></i>Upload Jadwal & Evaluasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info small d-flex align-items-start gap-2">
                    <i class="bi bi-info-circle-fill mt-1"></i>
                    <div>
                        Gunakan <a href="{{ route('admin.schedules.evaluation-template') }}" class="fw-semibold">template</a> yang disediakan.
                        Satu baris akan membuat <strong>jadwal &amp; evaluasi sekaligus</strong>.
                        Isi <strong>ID Siswa, Tutor, Mapel, dan Sesi</strong> sesuai daftar pada sheet
                        <em>Master Kelas / Tutor / Mapel / Sesi</em>. Jam mulai/selesai diambil dari sesi. Baris header tidak diimport.
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold">File Excel / CSV <span class="text-danger">*</span></label>
                    <input type="file" id="import-eval-file" class="form-control" accept=".xlsx,.xls,.csv">
                    <div class="invalid-feedback" id="err-eval-file"></div>
                    <small class="text-muted">Format: .xlsx, .xls, atau .csv — maksimal 5 MB.</small>
                </div>
                <div id="import-eval-errors" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" id="btn-upload-eval">
                    <i class="bi bi-upload me-1"></i> Upload
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script>
$(function () {

    var calendar;

    /* ── FullCalendar: sync title helper ── */
    function syncCalTitle() {
        if (calendar) $('#gcal-title').text(calendar.view.title);
    }

    function initCalendar() {
        calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
            themeSystem: 'bootstrap5',
            initialView: 'dayGridMonth',
            locale: 'id',
            height: 'auto',
            nowIndicator: true,
            dayMaxEvents: 3,
            headerToolbar: false,   /* we use our own topbar */
            slotMinTime: '06:00:00',
            slotMaxTime: '21:00:00',
            allDaySlot: false,
            slotDuration: '00:30:00',
            slotLabelFormat:  { hour: '2-digit', minute: '2-digit', hour12: false },
            eventTimeFormat:  { hour: '2-digit', minute: '2-digit', hour12: false },
            listDayFormat:    { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' },
            listDaySideFormat: false,

            events: {
                url: "{{ route('admin.schedules.events') }}",
                extraParams: function () {
                    return {
                        filter_status: $('#cal-filter-status').val(),
                        filter_tutor:  $('#cal-filter-tutor').val(),
                    };
                },
                failure: function () {
                    Swal.fire('Error', 'Gagal memuat data kalender.', 'error');
                }
            },

            /* Update custom title whenever view/date changes */
            datesSet: function () { syncCalTitle(); },

            /* Klik tanggal kosong → buka modal tambah jadwal */
            dateClick: function (info) {
                resetScheduleModal();
                $('#field-date').val(info.dateStr);
                $('#modal-schedule-title').text('Tambah Jadwal');
                $('#modal-schedule').modal('show');
            },

            /* Klik event → detail popup + aksi */
            eventClick: function (info) {
                info.jsEvent.preventDefault();
                var p  = info.event.extendedProps;
                var id = info.event.id;

                var pad = function (n) { return ('0' + n).slice(-2); };
                var startStr = info.event.start
                    ? pad(info.event.start.getHours()) + ':' + pad(info.event.start.getMinutes()) : '';
                var endStr = info.event.end
                    ? pad(info.event.end.getHours())   + ':' + pad(info.event.end.getMinutes())   : '';
                var dateStr = info.event.start
                    ? info.event.start.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) : '';

                var statusBadge = {
                    scheduled: '<span class="badge bg-primary">Dijadwalkan</span>',
                    done:      '<span class="badge bg-success">Selesai</span>',
                    canceled:  '<span class="badge bg-secondary">Dibatalkan</span>',
                }[p.status] || p.status;

                var actionsHtml = '<div class="d-flex gap-2 flex-wrap justify-content-center mt-3">';
                if (p.status === 'scheduled') {
                    actionsHtml += '<button class="btn btn-sm btn-success" id="swal-done"><i class="bi bi-check-lg me-1"></i>Selesai</button>';
                    var editLabel = (!p.tutor || p.tutor === '-' || !p.subject || p.subject === '-')
                        ? '<i class="bi bi-pencil me-1"></i>Isi Tutor/Materi'
                        : '<i class="bi bi-pencil me-1"></i>Edit';
                    actionsHtml += '<button class="btn btn-sm btn-warning" id="swal-edit">' + editLabel + '</button>';
                    actionsHtml += '<button class="btn btn-sm btn-outline-secondary" id="swal-cancel-sched"><i class="bi bi-x-circle me-1"></i>Batalkan</button>';
                }
                if (p.status === 'done') {
                    var evalLabel = p.has_eval ? 'Edit Evaluasi' : 'Isi Evaluasi';
                    actionsHtml += '<button class="btn btn-sm btn-info text-white" id="swal-eval"><i class="bi bi-clipboard2-check me-1"></i>' + evalLabel + '</button>';
                }
                actionsHtml += '</div>';

                Swal.fire({
                    title: '<span class="fw-bold" style="font-size:1rem">' + p.student + '</span>',
                    html:  '<table class="table table-sm table-borderless swal-sched-tbl mb-0 text-start">' +
                           '<tr><td>Mata Pelajaran</td><td>' + p.subject  + '</td></tr>' +
                           '<tr><td>Tutor</td><td>'          + p.tutor    + '</td></tr>' +
                           '<tr><td>Tanggal</td><td>'        + dateStr    + '</td></tr>' +
                           '<tr><td>Jam</td><td>'            + startStr   + ' – ' + endStr + '</td></tr>' +
                           '<tr><td>Status</td><td>'         + statusBadge + '</td></tr>' +
                           '</table>' + actionsHtml,
                    showConfirmButton: false,
                    showCloseButton: true,
                    didOpen: function () {
                        var btn;
                        btn = document.getElementById('swal-done');
                        if (btn) btn.addEventListener('click', function () { Swal.close(); doMarkDone(id); });
                        btn = document.getElementById('swal-edit');
                        if (btn) btn.addEventListener('click', function () { Swal.close(); doEdit(id); });
                        btn = document.getElementById('swal-cancel-sched');
                        if (btn) btn.addEventListener('click', function () { Swal.close(); doCancel(id); });
                        btn = document.getElementById('swal-eval');
                        if (btn) btn.addEventListener('click', function () { Swal.close(); doEval(id); });
                    }
                });
            }
        });
        calendar.render();
        syncCalTitle();
    }

    /* Inisialisasi langsung saat halaman dimuat */
    initCalendar();

    /* ── Custom navigation ── */
    $('#gcal-prev').on('click',  function () { if (calendar) { calendar.prev();  syncCalTitle(); } });
    $('#gcal-next').on('click',  function () { if (calendar) { calendar.next();  syncCalTitle(); } });
    $('#gcal-today').on('click', function () { if (calendar) { calendar.today(); syncCalTitle(); } });

    /* ── View switcher ── */
    $(document).on('click', '#gcal-view-switcher [data-fc-view]', function () {
        if (!calendar) return;
        calendar.changeView($(this).data('fc-view'));
        syncCalTitle();
        $('#gcal-view-switcher [data-fc-view]').removeClass('active');
        $(this).addClass('active');
    });

    /* ── Filter → refetch ── */
    $('#cal-filter-status, #cal-filter-tutor').on('change', function () {
        if (calendar) calendar.refetchEvents();
    });

    /* ================================================================
       Map student_id → schedule_session_id (default sesi pendaftaran)
    ================================================================ */
    var studentDefaultSession = {
        @foreach($students as $s)
            {{ $s->id }}: {{ $s->schedule_session_id ?? 'null' }},
        @endforeach
    };

    $('#field-student').on('change', function () {
        var sessionId = studentDefaultSession[$(this).val()] || null;
        $('#field-session').val(sessionId || '').trigger('change');
    });

    $(document).on('change', '#field-session', function () {
        var opt   = $(this).find('option:selected');
        var start = opt.data('start') || '';
        var end   = opt.data('end')   || '';
        $('#field-start').val(start);
        $('#field-end').val(end);
        $('#session-time-display').html(
            start && end
                ? '<i class="bi bi-clock me-1"></i>Jam: <strong>' + start + ' – ' + end + '</strong>'
                : ''
        );
    });

    /* ================================================================
       Schedule Modal helpers
    ================================================================ */
    function resetScheduleModal() {
        $('#schedule-id').val('');
        $('#field-student, #field-tutor, #field-subject, #field-session').val('').removeClass('is-invalid');
        $('#field-room, #field-date, #field-start, #field-end').val('');
        $('#err-student, #err-tutor, #err-subject, #err-session, #err-date, #err-room').text('');
        $('#session-time-display').text('');
    }

    $('#btn-add-schedule').on('click', function () {
        resetScheduleModal();
        $('#modal-schedule-title').text('Tambah Jadwal');
        $('#modal-schedule').modal('show');
    });

    /* ================================================================
       Action Helpers (shared by DataTable buttons & Calendar popup)
    ================================================================ */
    function doEdit(id) {
        resetScheduleModal();
        $('#modal-schedule-title').text('Edit Jadwal');
        $.get('/admin/schedules/' + id, function (data) {
            var savedStart = data.start_time.substring(0, 5);
            var savedEnd   = data.end_time.substring(0, 5);
            $('#schedule-id').val(data.id);
            $('#field-student').val(data.student_id);
            $('#field-tutor').val(data.tutor_id);
            $('#field-subject').val(data.subject_id);
            $('#field-room').val(data.room);
            $('#field-date').val(data.class_date.substring(0, 10));

            var matchedOpt = $('#field-session option').filter(function () {
                return $(this).data('start') === savedStart && $(this).data('end') === savedEnd;
            });
            if (matchedOpt.length) {
                $('#field-session').val(matchedOpt.val());
                $('#session-time-display').html('<i class="bi bi-clock me-1"></i>Jam: <strong>' + savedStart + ' – ' + savedEnd + '</strong>');
            } else {
                $('#session-time-display').html('<i class="bi bi-clock me-1 text-warning"></i>Jam tersimpan: <strong>' + savedStart + ' – ' + savedEnd + '</strong> (pilih sesi untuk mengubah)');
            }
            $('#field-start').val(savedStart);
            $('#field-end').val(savedEnd);
            $('#modal-schedule').modal('show');
        });
    }

    function doMarkDone(id) {
        Swal.fire({
            title: 'Tandai Sesi Selesai?',
            text: 'Status akan berubah menjadi "Selesai" dan kuota siswa akan berkurang 1.',
            icon: 'question', showCancelButton: true,
            confirmButtonText: 'Ya, Selesai', cancelButtonText: 'Batal'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url: '/admin/schedules/' + id + '/status', type: 'PUT',
                data: { status: 'done', _token: '{{ csrf_token() }}' },
                success: function (res) {
                    calendar.refetchEvents();
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false });
                }
            });
        });
    }

    function doCancel(id) {
        Swal.fire({
            title: 'Batalkan Jadwal?',
            text: 'Jadwal ini akan ditandai sebagai Dibatalkan.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#6c757d', confirmButtonText: 'Ya, Batalkan', cancelButtonText: 'Tidak'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url: '/admin/schedules/' + id + '/status', type: 'PUT',
                data: { status: 'canceled', _token: '{{ csrf_token() }}' },
                success: function (res) {
                    calendar.refetchEvents();
                    Swal.fire({ icon: 'success', title: 'Dibatalkan', text: res.message, timer: 2000, showConfirmButton: false });
                }
            });
        });
    }

    function doDelete(id) {
        Swal.fire({
            title: 'Hapus Jadwal?', text: 'Data jadwal akan dihapus permanen.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url: '/admin/schedules/' + id, type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function (res) {
                    calendar.refetchEvents();
                    Swal.fire({ icon: 'success', title: 'Dihapus', text: res.message, timer: 2000, showConfirmButton: false });
                }
            });
        });
    }

    function doEval(id) {
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
            // Isi dropdown Sub Pokok Bahasan sesuai silabus mata pelajaran
            var $syl = $('#eval-syllabus');
            $syl.find('option').not('[value=""]').not('[value="__other__"]').remove();
            var syllabi = (data.subject && data.subject.syllabi) ? data.subject.syllabi : [];
            syllabi.forEach(function (s) {
                var label = s.pokok_bahasan + (s.sub_pokok_bahasan ? ' — ' + s.sub_pokok_bahasan : '');
                $syl.find('option[value="__other__"]').before('<option value="' + s.id + '">' + label + '</option>');
            });
            $('#eval-syllabus-empty').toggle(syllabi.length === 0);

            $('input[name="student_attendance"]').prop('checked', false);
            $('#eval-syllabus, #eval-posttest, #eval-pemahaman, #eval-analisa, #eval-hafalan, #eval-kepercayaan, #eval-notes').val('');
            $('#eval-materi-manual').val('').hide();
            if (data.evaluation) {
                $('input[name="student_attendance"][value="' + data.evaluation.student_attendance + '"]').prop('checked', true);
                if (data.evaluation.syllabus_id) {
                    $('#eval-syllabus').val(String(data.evaluation.syllabus_id));
                } else if (data.evaluation.materi_manual) {
                    $('#eval-syllabus').val('__other__');
                    $('#eval-materi-manual').val(data.evaluation.materi_manual).show();
                }
                $('#eval-posttest').val(data.evaluation.post_test ?? '');
                $('#eval-pemahaman').val(data.evaluation.pemahaman ?? '');
                $('#eval-analisa').val(data.evaluation.kemampuan_analisa ?? '');
                $('#eval-hafalan').val(data.evaluation.kemampuan_hafalan ?? '');
                $('#eval-kepercayaan').val(data.evaluation.kepercayaan_diri ?? '');
                $('#eval-notes').val(data.evaluation.tutor_notes ?? '');
            }
            $('#modal-evaluation').modal('show');
        });
    }

    /* ================================================================
       Simpan Jadwal
    ================================================================ */
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
                room:       $('#field-room').val(),
                class_date: $('#field-date').val(),
                start_time: $('#field-start').val(),
                end_time:   $('#field-end').val(),
                _token:     '{{ csrf_token() }}'
            },
            success: function (res) {
                $('#modal-schedule').modal('hide');
                calendar.refetchEvents();
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

    /* ================================================================
       Generate Jadwal Mingguan (otomatis semua siswa aktif)
    ================================================================ */
    function getMonday(dateStr) {
        var d = new Date(dateStr + 'T00:00:00');
        var day = d.getDay();
        var diff = (day === 0) ? -6 : 1 - day;
        d.setDate(d.getDate() + diff);
        return d;
    }

    function formatDate(d) {
        return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
    }

    function updateGenWeekPreview() {
        var val = $('#gen-week-date').val();
        if (!val) { $('#gen-week-range').text('–'); return; }
        var mon = getMonday(val);
        var sun = new Date(mon); sun.setDate(sun.getDate() + 6);
        $('#gen-week-range').text(formatDate(mon) + ' – ' + formatDate(sun));
    }

    $('#btn-generate-schedule').on('click', function () {
        $('#gen-week-date').val(new Date().toISOString().slice(0,10)).removeClass('is-invalid');
        $('#err-gen-week').text('');
        updateGenWeekPreview();
        $('#modal-generate').modal('show');
    });

    $('#gen-week-date').on('change', updateGenWeekPreview);

    $('#btn-do-generate').on('click', function () {
        var weekDate = $('#gen-week-date').val();
        if (!weekDate) {
            $('#gen-week-date').addClass('is-invalid');
            $('#err-gen-week').text('Tanggal harus diisi.');
            return;
        }
        $('#gen-week-date').removeClass('is-invalid');

        var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Generating...');
        $.ajax({
            url: '{{ route("admin.schedules.generate") }}', type: 'POST',
            data: { week_date: weekDate, _token: '{{ csrf_token() }}' },
            success: function (res) {
                $('#modal-generate').modal('hide');
                calendar.refetchEvents();
                Swal.fire({
                    icon: 'success', title: 'Generate Selesai!',
                    html: '<div style="font-size:.9rem">' + res.message + '</div>' +
                          '<div class="text-muted mt-2" style="font-size:.8rem">Klik event di kalender untuk mengisi tutor dan materi.</div>',
                    confirmButtonText: 'OK'
                });
            },
            error: function (xhr) {
                Swal.fire({ icon: 'warning', title: 'Perhatian', text: xhr.responseJSON?.message ?? 'Terjadi kesalahan.' });
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="bi bi-calendar2-check me-1"></i> Generate Semua');
            }
        });
    });

    /* Tampilkan input materi manual saat memilih "Lainnya" */
    $('#eval-syllabus').on('change', function () {
        $('#eval-materi-manual').toggle($(this).val() === '__other__');
    });

    /* ================================================================
       Simpan Evaluasi
    ================================================================ */
    $('#btn-save-eval').on('click', function () {
        var attendance = $('input[name="student_attendance"]:checked').val();
        if (!attendance) { Swal.fire('Perhatian', 'Kehadiran siswa harus dipilih.', 'warning'); return; }

        var sylVal   = $('#eval-syllabus').val();
        var isOther  = sylVal === '__other__';

        $.ajax({
            url: '{{ route("admin.evaluations.store") }}', type: 'POST',
            data: {
                schedule_id:        $('#eval-schedule-id').val(),
                syllabus_id:        isOther ? null : (sylVal || null),
                materi_manual:      isOther ? ($('#eval-materi-manual').val() || null) : null,
                student_attendance: attendance,
                post_test:          $('#eval-posttest').val() || null,
                pemahaman:          $('#eval-pemahaman').val() || null,
                kemampuan_analisa:  $('#eval-analisa').val() || null,
                kemampuan_hafalan:  $('#eval-hafalan').val() || null,
                kepercayaan_diri:   $('#eval-kepercayaan').val() || null,
                tutor_notes:        $('#eval-notes').val(),
                _token:             '{{ csrf_token() }}'
            },
            success: function (res) {
                $('#modal-evaluation').modal('hide');
                calendar.refetchEvents();
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false });
            },
            error: function (xhr) {
                Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan.', 'error');
            }
        });
    });

    /* ================================================================
       Upload Jadwal & Evaluasi (gabungan)
    ================================================================ */
    $('#btn-import-eval').on('click', function () {
        $('#import-eval-file').val('').removeClass('is-invalid');
        $('#err-eval-file').text('');
        $('#import-eval-errors').html('');
        $('#modal-import-eval').modal('show');
    });

    $('#btn-upload-eval').on('click', function () {
        var fileInput = $('#import-eval-file')[0];
        $('#import-eval-file').removeClass('is-invalid');
        $('#err-eval-file').text('');
        $('#import-eval-errors').html('');

        if (!fileInput.files.length) {
            $('#import-eval-file').addClass('is-invalid');
            $('#err-eval-file').text('Silakan pilih file terlebih dahulu.');
            return;
        }

        var formData = new FormData();
        formData.append('file', fileInput.files[0]);
        formData.append('_token', '{{ csrf_token() }}');

        var $btn = $('#btn-upload-eval').prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm me-1"></span> Mengupload...');

        $.ajax({
            url: '{{ route('admin.schedules.import-evaluation') }}', type: 'POST',
            data: formData, processData: false, contentType: false,
            success: function (res) {
                $('#modal-import-eval').modal('hide');
                calendar.refetchEvents();
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2800, showConfirmButton: false });
            },
            error: function (xhr) {
                var res = xhr.responseJSON || {};
                if (res.errors && res.errors.file) {
                    $('#import-eval-file').addClass('is-invalid');
                    $('#err-eval-file').text(res.errors.file[0]);
                } else {
                    var html = '<div class="alert alert-danger small mb-0">' + (res.message || 'Terjadi kesalahan.');
                    if (Array.isArray(res.errors) && res.errors.length) {
                        html += '<ul class="mb-0 mt-2 ps-3">';
                        res.errors.forEach(function (e) { html += '<li>' + e + '</li>'; });
                        html += '</ul>';
                    }
                    html += '</div>';
                    $('#import-eval-errors').html(html);
                }
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="bi bi-upload me-1"></i> Upload');
            }
        });
    });

});
</script>
@endpush
