@extends('admin.layouts.app')

@section('title', 'Silabus ' . $subject->subject_name . ' - LIVO Admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <a href="{{ route('admin.subjects.index') }}" class="btn btn-link link-secondary ps-0 mb-1">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Mata Pelajaran
        </a>
        <h2 class="page-title">Silabus — {{ $subject->subject_name }}</h2>
        <p class="text-muted mb-0 small">Kelola pokok bahasan & materi per kelas untuk mata pelajaran ini</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.subjects.syllabi.template', $subject->id) }}" class="btn btn-outline-success">
            <i class="bi bi-download me-1"></i> Download Template
        </a>
        <button class="btn btn-success" id="btn-import">
            <i class="bi bi-file-earmark-excel me-1"></i> Upload Excel
        </button>
        <button class="btn btn-primary" id="btn-add">
            <i class="bi bi-plus-lg me-1"></i> Tambah Silabus
        </button>
    </div>
</div>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <table id="syllabi-table" class="table table-hover align-middle w-100">
            <thead class="table-light">
                <tr>
                    <th width="50">#</th>
                    <th>Pokok Bahasan</th>
                    <th>Sub Pokok Bahasan</th>
                    <th>Jenis Kurikulum</th>
                    <th>Kelas</th>
                    <th width="130" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="modal-syllabus" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-title">Tambah Silabus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="syl-id">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Pokok Bahasan <span class="text-danger">*</span></label>
                    <input type="text" id="syl-pokok" class="form-control" placeholder="cth: Bilangan Bulat">
                    <div class="invalid-feedback" id="err-pokok"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Sub Pokok Bahasan</label>
                    <input type="text" id="syl-sub" class="form-control" placeholder="cth: Operasi penjumlahan & pengurangan">
                    <div class="invalid-feedback" id="err-sub"></div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Jenis Kurikulum <span class="text-danger">*</span></label>
                        <select id="syl-kurikulum" class="form-select">
                            <option value="">-- Pilih Kurikulum --</option>
                            <option value="Kurikulum Merdeka">Kurikulum Merdeka</option>
                            <option value="Kurikulum 2013">Kurikulum 2013</option>
                            <option value="KTSP">KTSP</option>
                        </select>
                        <div class="invalid-feedback" id="err-kurikulum"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Kelas <span class="text-danger">*</span></label>
                        <select id="syl-kelas" class="form-select">
                            <option value="">-- Pilih Kelas --</option>
                            <option value="SD Kelas 1">SD Kelas 1</option>
                            <option value="SD Kelas 2">SD Kelas 2</option>
                            <option value="SD Kelas 3">SD Kelas 3</option>
                            <option value="SD Kelas 4">SD Kelas 4</option>
                            <option value="SD Kelas 5">SD Kelas 5</option>
                            <option value="SD Kelas 6">SD Kelas 6</option>
                            <option value="SMP Kelas 7">SMP Kelas 7</option>
                            <option value="SMP Kelas 8">SMP Kelas 8</option>
                            <option value="SMP Kelas 9">SMP Kelas 9</option>
                            <option value="SMA Kelas 10">SMA Kelas 10</option>
                            <option value="SMA Kelas 11">SMA Kelas 11</option>
                            <option value="SMA Kelas 12">SMA Kelas 12</option>
                        </select>
                        <div class="invalid-feedback" id="err-kelas"></div>
                    </div>
                </div>
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
                <h5 class="modal-title">Upload Silabus dari Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info small d-flex align-items-start gap-2">
                    <i class="bi bi-info-circle-fill mt-1"></i>
                    <div>
                        Gunakan <a href="{{ route('admin.subjects.syllabi.template', $subject->id) }}" class="fw-semibold">template Excel</a>
                        yang disediakan. Kolom: <strong>Pokok Bahasan</strong>, <strong>Sub Pokok Bahasan</strong>,
                        <strong>Jenis Kurikulum</strong>, <strong>Kelas</strong>. Baris pertama (header) tidak akan diimport.
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
    var baseUrl = "{{ url('admin/subjects/' . $subject->id . '/syllabi') }}";

    var table = $('#syllabi-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.subjects.syllabi.data', $subject->id) }}",
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'pokok_bahasan' },
            { data: 'sub_pokok_bahasan' },
            { data: 'jenis_kurikulum' },
            { data: 'kelas' },
            { data: 'action', orderable: false, searchable: false, className: 'text-center' },
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json' }
    });

    function resetModal() {
        $('#syl-id, #syl-pokok, #syl-sub, #syl-kurikulum, #syl-kelas').val('');
        $('#syl-pokok, #syl-sub, #syl-kurikulum, #syl-kelas').removeClass('is-invalid');
        $('#err-pokok, #err-sub, #err-kurikulum, #err-kelas').text('');
    }

    $('#btn-add').on('click', function () {
        resetModal();
        $('#modal-title').text('Tambah Silabus');
        $('#modal-syllabus').modal('show');
    });

    $(document).on('click', '.btn-edit', function () {
        resetModal();
        var btn = $(this);
        $('#modal-title').text('Edit Silabus');
        $('#syl-id').val(btn.data('id'));
        $('#syl-pokok').val(btn.data('pokok'));
        $('#syl-sub').val(btn.data('sub'));
        $('#syl-kurikulum').val(btn.data('kurikulum'));
        $('#syl-kelas').val(btn.data('kelas'));
        $('#modal-syllabus').modal('show');
    });

    $('#btn-save').on('click', function () {
        var id   = $('#syl-id').val();
        var url  = id ? baseUrl + '/' + id : baseUrl;
        var type = id ? 'PUT' : 'POST';

        $.ajax({
            url: url, type: type,
            data: {
                pokok_bahasan:     $('#syl-pokok').val(),
                sub_pokok_bahasan: $('#syl-sub').val(),
                jenis_kurikulum:   $('#syl-kurikulum').val(),
                kelas:             $('#syl-kelas').val(),
                _token:            '{{ csrf_token() }}'
            },
            success: function (res) {
                $('#modal-syllabus').modal('hide');
                table.ajax.reload();
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false });
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    var err = xhr.responseJSON.errors ?? {};
                    if (err.pokok_bahasan)     { $('#syl-pokok').addClass('is-invalid');     $('#err-pokok').text(err.pokok_bahasan[0]); }
                    if (err.sub_pokok_bahasan) { $('#syl-sub').addClass('is-invalid');       $('#err-sub').text(err.sub_pokok_bahasan[0]); }
                    if (err.jenis_kurikulum)   { $('#syl-kurikulum').addClass('is-invalid'); $('#err-kurikulum').text(err.jenis_kurikulum[0]); }
                    if (err.kelas)             { $('#syl-kelas').addClass('is-invalid');     $('#err-kelas').text(err.kelas[0]); }
                } else {
                    Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan.', 'error');
                }
            }
        });
    });

    // ── Import Excel ──────────────────────────────────────────────
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
                var res = xhr.responseJSON ?? {};
                if (res.errors && res.errors.file) {
                    $('#import-file').addClass('is-invalid');
                    $('#err-file').text(res.errors.file[0]);
                } else {
                    var html = '<div class="alert alert-danger small mb-0">' +
                        (res.message ?? 'Terjadi kesalahan.');
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

    $(document).on('click', '.btn-delete', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Hapus Silabus?', text: 'Data silabus ini akan dihapus permanen.',
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
