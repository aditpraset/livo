@extends('admin.layouts.app')

@section('title', 'Bank Soal - ' . $syllabus->pokok_bahasan . ' - LIVO Admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <a href="{{ route('admin.subjects.syllabi.index', $subject->id) }}" class="btn btn-link link-secondary ps-0 mb-1">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Silabus
        </a>
        <h2 class="page-title">Bank Soal — {{ $syllabus->pokok_bahasan }}</h2>
        <p class="text-muted mb-0 small">
            {{ $subject->subject_name }}
            @if($syllabus->sub_pokok_bahasan) &middot; {{ $syllabus->sub_pokok_bahasan }} @endif
            &middot; {{ $syllabus->jenis_kurikulum }} &middot; {{ $syllabus->kelas }}
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.subjects.syllabi.questions.template', [$subject->id, $syllabus->id]) }}" class="btn btn-outline-success">
            <i class="bi bi-download me-1"></i> Download Template
        </a>
        <button class="btn btn-success" id="btn-import">
            <i class="bi bi-file-earmark-excel me-1"></i> Upload Excel
        </button>
        <button class="btn btn-primary" id="btn-add">
            <i class="bi bi-plus-lg me-1"></i> Tambah Soal
        </button>
    </div>
</div>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <table id="questions-table" class="table table-hover align-middle w-100">
            <thead class="table-light">
                <tr>
                    <th width="50">#</th>
                    <th>Pertanyaan</th>
                    <th width="220">Jawaban Benar</th>
                    <th width="100" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="modal-question" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-title">Tambah Soal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="q-id">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Pertanyaan <span class="text-danger">*</span></label>
                    <textarea id="q-question" class="form-control" rows="3" placeholder="Tulis soal di sini..."></textarea>
                    <div class="invalid-feedback" id="err-question"></div>
                </div>

                <label class="form-label fw-semibold">Pilihan Jawaban <span class="text-danger">*</span></label>
                <p class="text-muted small mb-2">Tandai bulatan di samping pilihan yang merupakan jawaban benar.</p>

                @foreach(['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D'] as $key => $label)
                    <div class="mb-2">
                        <div class="input-group has-validation">
                            <span class="input-group-text">
                                <input class="form-check-input mt-0" type="radio" name="q-correct" value="{{ $key }}" id="q-correct-{{ $key }}" aria-label="Tandai {{ $label }} sebagai jawaban benar">
                            </span>
                            <span class="input-group-text fw-semibold justify-content-center" style="width:40px;">{{ $label }}</span>
                            <input type="text" id="q-option-{{ $key }}" class="form-control" placeholder="Pilihan {{ $label }}">
                            <div class="invalid-feedback" id="err-option_{{ $key }}"></div>
                        </div>
                    </div>
                @endforeach
                <div class="invalid-feedback d-block" id="err-correct_answer"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btn-save">Simpan</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Import Excel --}}
<div class="modal fade" id="modal-import" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Soal dari Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info small d-flex align-items-start gap-2">
                    <i class="bi bi-info-circle-fill mt-1"></i>
                    <div>
                        Gunakan <a href="{{ route('admin.subjects.syllabi.questions.template', [$subject->id, $syllabus->id]) }}" class="fw-semibold">template Excel</a> yang disediakan.
                        Kolom <strong>Jawaban Benar</strong> diisi salah satu dari <code>A</code>, <code>B</code>, <code>C</code>, atau <code>D</code>. Baris header tidak diimport.
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold">File Excel / CSV <span class="text-danger">*</span></label>
                    <input type="file" id="import-file" class="form-control" accept=".xlsx,.xls,.csv">
                    <div class="invalid-feedback" id="err-file"></div>
                    <small class="text-muted">Format: .xlsx, .xls, atau .csv — maksimal 5 MB.</small>
                </div>
                <div id="import-errors" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" id="btn-upload">
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
    var baseUrl = "{{ url('admin/subjects/' . $subject->id . '/syllabi/' . $syllabus->id . '/questions') }}";
    var optKeys = ['a', 'b', 'c', 'd'];

    var table = $('#questions-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.subjects.syllabi.questions.data', [$subject->id, $syllabus->id]) }}",
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'question' },
            { data: 'correct_answer_label', orderable: false, searchable: false },
            { data: 'action', orderable: false, searchable: false, className: 'text-center' },
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json',
            emptyTable: 'Belum ada soal untuk silabus ini.'
        }
    });

    // ── Import Excel ──
    $('#btn-import').on('click', function () {
        $('#import-file').val('').removeClass('is-invalid');
        $('#err-file').text('');
        $('#import-errors').html('');
        $('#modal-import').modal('show');
    });

    $('#btn-upload').on('click', function () {
        var fileInput = $('#import-file')[0];
        $('#import-file').removeClass('is-invalid');
        $('#err-file').text('');
        $('#import-errors').html('');

        if (!fileInput.files.length) {
            $('#import-file').addClass('is-invalid');
            $('#err-file').text('Silakan pilih file terlebih dahulu.');
            return;
        }

        var formData = new FormData();
        formData.append('file', fileInput.files[0]);
        formData.append('_token', '{{ csrf_token() }}');

        var $btn = $('#btn-upload').prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm me-1"></span> Mengupload...');

        $.ajax({
            url: baseUrl + '/import', type: 'POST',
            data: formData, processData: false, contentType: false,
            success: function (res) {
                $('#modal-import').modal('hide');
                table.ajax.reload();
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2500, showConfirmButton: false });
            },
            error: function (xhr) {
                var res = xhr.responseJSON || {};
                if (res.errors && res.errors.file) {
                    $('#import-file').addClass('is-invalid');
                    $('#err-file').text(res.errors.file[0]);
                } else {
                    var html = '<div class="alert alert-danger small mb-0">' + (res.message || 'Terjadi kesalahan.');
                    if (Array.isArray(res.errors) && res.errors.length) {
                        html += '<ul class="mb-0 mt-2 ps-3">';
                        res.errors.forEach(function (e) { html += '<li>' + e + '</li>'; });
                        html += '</ul>';
                    }
                    html += '</div>';
                    $('#import-errors').html(html);
                }
            },
            complete: function () {
                $btn.prop('disabled', false).html('<i class="bi bi-upload me-1"></i> Upload');
            }
        });
    });

    function resetModal() {
        $('#q-id, #q-question').val('');
        optKeys.forEach(function (k) { $('#q-option-' + k).val(''); });
        $('input[name="q-correct"]').prop('checked', false);
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    }

    $('#btn-add').on('click', function () {
        resetModal();
        $('#modal-title').text('Tambah Soal');
        $('#modal-question').modal('show');
    });

    $(document).on('click', '.btn-edit', function () {
        resetModal();
        var btn = $(this);
        $('#modal-title').text('Edit Soal');
        $('#q-id').val(btn.data('id'));
        $('#q-question').val(btn.data('question'));
        optKeys.forEach(function (k) { $('#q-option-' + k).val(btn.data(k)); });
        $('#q-correct-' + btn.data('correct')).prop('checked', true);
        $('#modal-question').modal('show');
    });

    $('#btn-save').on('click', function () {
        var id   = $('#q-id').val();
        var url  = id ? baseUrl + '/' + id : baseUrl;
        var type = id ? 'PUT' : 'POST';

        var data = {
            question: $('#q-question').val(),
            correct_answer: $('input[name="q-correct"]:checked').val() || '',
            _token: '{{ csrf_token() }}'
        };
        optKeys.forEach(function (k) { data['option_' + k] = $('#q-option-' + k).val(); });

        $.ajax({
            url: url, type: type, data: data,
            success: function (res) {
                $('#modal-question').modal('hide');
                table.ajax.reload();
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false });
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    var err = xhr.responseJSON.errors ?? {};
                    if (err.question) { $('#q-question').addClass('is-invalid'); $('#err-question').text(err.question[0]); }
                    optKeys.forEach(function (k) {
                        if (err['option_' + k]) { $('#q-option-' + k).addClass('is-invalid'); $('#err-option_' + k).text(err['option_' + k][0]); }
                    });
                    if (err.correct_answer) { $('#err-correct_answer').text(err.correct_answer[0]); }
                } else {
                    Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan.', 'error');
                }
            }
        });
    });

    $(document).on('click', '.btn-delete', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Hapus Soal?', text: 'Soal ini akan dihapus permanen.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal'
        }).then(function (r) {
            if (r.isConfirmed) {
                $.ajax({
                    url: baseUrl + '/' + id, type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        table.ajax.reload();
                        Swal.fire({ icon: 'success', title: 'Dihapus', text: res.message, timer: 2000, showConfirmButton: false });
                    },
                    error: function (xhr) {
                        Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan.', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
