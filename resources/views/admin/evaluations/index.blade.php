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
                        <th class="text-center">Pre Test</th>
                        <th class="text-center">Post Test</th>
                        <th class="text-center">Pemahaman</th>
                        <th class="text-center">Poin</th>
                        <th width="60" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
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
            { data: 'pre_test', name: 'evaluations.pre_test', className: 'text-center', searchable: false },
            { data: 'post_test', name: 'evaluations.post_test', className: 'text-center', searchable: false },
            { data: 'pemahaman', name: 'evaluations.pemahaman', className: 'text-center', searchable: false },
            { data: 'poin', name: 'evaluations.poin', className: 'text-center', searchable: false },
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
});
</script>
@endpush
