@extends('admin.layouts.app')

@section('title', 'Evaluasi Belajar - LIVO Admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center p-4">
    <div>
        <h2 class="page-title">Evaluasi Belajar</h2>
        <p class="text-muted mb-0 small">Daftar jadwal/sesi belajar yang sudah dievaluasi</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-success" href="{{ route('admin.schedules.evaluation-template') }}">
            <i class="bi bi-download me-1"></i> Download Template
        </a>
        <button class="btn btn-success" id="btn-import-eval">
            <i class="bi bi-file-earmark-excel me-1"></i> Upload Evaluasi
        </button>
    </div>
</div>
@endsection

@section('content')
{{-- Filter --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Kelas</label>
                <select id="filter-grade" class="form-select">
                    <option value="">Semua Kelas</option>
                    @foreach($grades as $grade)
                        <option value="{{ $grade }}">{{ $grade }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small">Mata Pelajaran</label>
                <select id="filter-subject" class="form-select">
                    <option value="">Semua Mapel</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->subject_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Tanggal Mulai</label>
                <input type="date" id="filter-start" class="form-control">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-semibold small">Tanggal Akhir</label>
                <input type="date" id="filter-end" class="form-control">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary flex-fill" id="btn-filter">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                <button class="btn btn-outline-secondary" id="btn-reset" title="Reset filter">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Tabel --}}
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="evaluations-table" class="table table-hover align-middle w-100">
                <thead class="table-light">
                    <tr>
                        <th width="40">#</th>
                        <th>Tanggal</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Mata Pelajaran</th>
                        <th>Tutor</th>
                        <th>Sub Pokok Bahasan</th>
                        <th class="text-center">Kehadiran</th>
                        <th class="text-center">Nilai</th>
                        <th width="60" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Edit Evaluasi --}}
<div class="modal fade" id="modal-evaluation" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning bg-opacity-10">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Evaluasi</h5>
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
                    <select id="eval-syllabus" class="form-select no-select2">
                        <option value="">— Pilih materi sesuai silabus mapel —</option>
                        <option value="__other__">Lainnya (isi manual)</option>
                    </select>
                    <small class="text-muted" id="eval-syllabus-empty" style="display:none;">
                        Belum ada silabus untuk mata pelajaran ini di kelas siswa.
                    </small>
                    <input type="text" id="eval-materi-manual" class="form-control mt-2" style="display:none;"
                        placeholder="Tulis materi / sub pokok bahasan secara manual">
                </div>
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
                <button type="button" class="btn btn-warning" id="btn-save-eval">Simpan Perubahan</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Upload Jadwal & Evaluasi --}}
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
                        <em>Master Kelas / Tutor / Mapel / Sesi</em>. Baris header tidak diimport.
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
<script>
$(function () {
    var table = $('#evaluations-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.data.evaluations') }}",
            data: function (d) {
                d.grade      = $('#filter-grade').val();
                d.subject_id = $('#filter-subject').val();
                d.start_date = $('#filter-start').val();
                d.end_date   = $('#filter-end').val();
            }
        },
        order: [[1, 'desc']],
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'class_date', name: 'schedules.class_date', searchable: false },
            { data: 'student_name', name: 'students.full_name' },
            { data: 'grade', name: 'students.grade', className: 'text-center' },
            { data: 'subject_name', name: 'subjects.subject_name' },
            { data: 'tutor_name', name: 'tutors.name' },
            { data: 'materi', name: 'evaluations.pokok_bahasan', orderable: false, searchable: false },
            { data: 'attendance', name: 'evaluations.student_attendance', className: 'text-center', searchable: false },
            { data: 'post_test', name: 'evaluations.post_test', className: 'text-center', searchable: false },
            { data: 'action', orderable: false, searchable: false, className: 'text-center' },
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json' }
    });

    $('#btn-filter').on('click', function () { table.ajax.reload(); });

    $('#btn-reset').on('click', function () {
        $('#filter-grade, #filter-subject').val('').trigger('change');
        $('#filter-start, #filter-end').val('');
        table.ajax.reload();
    });

    // Submit otomatis saat menekan Enter pada input tanggal
    $('#filter-start, #filter-end').on('keypress', function (e) {
        if (e.which === 13) table.ajax.reload();
    });

    /* ── Upload Jadwal & Evaluasi ── */
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
                table.ajax.reload();
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

    /* ── Edit Evaluasi ── */
    $('#eval-syllabus').on('change', function () {
        $('#eval-materi-manual').toggle($(this).val() === '__other__');
    });

    $('#evaluations-table').on('click', '.btn-edit-eval', function () {
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
    });

    $('#btn-save-eval').on('click', function () {
        var attendance = $('input[name="student_attendance"]:checked').val();
        if (!attendance) { Swal.fire('Perhatian', 'Kehadiran siswa harus dipilih.', 'warning'); return; }

        var sylVal  = $('#eval-syllabus').val();
        var isOther = sylVal === '__other__';

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
                table.ajax.reload(null, false);
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
