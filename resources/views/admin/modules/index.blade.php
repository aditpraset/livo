@extends('admin.layouts.app')

@section('title', 'Modul Belajar - LIVO Admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center p-4 flex-wrap gap-2">
    <div>
        <h2 class="page-title">Modul Belajar</h2>
        <p class="text-muted mb-0 small">Materi ajar (Excel, PDF, atau Word) per mata pelajaran &amp; kelas — dikelompokkan per kelas.</p>
    </div>
    <button class="btn btn-primary" id="btn-add"><i class="bi bi-plus-lg me-1"></i> Tambah Modul</button>
</div>
@endsection

@section('content')
@forelse($modules as $kelas => $items)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white px-4 py-3 d-flex align-items-center gap-2">
            <h4 class="mb-0 h5">{{ $kelas }}</h4>
            <span class="badge bg-secondary-subtle text-secondary ms-auto">{{ $items->count() }} modul</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nama Modul</th>
                        <th>Mata Pelajaran</th>
                        <th>File</th>
                        <th class="text-end" style="min-width:150px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $module)
                        <tr>
                            <td>{{ $module->nama_modul }}</td>
                            <td>{{ $module->subject->subject_name ?? '-' }}</td>
                            <td>
                                <a href="{{ route('admin.modules.download', $module) }}" class="text-decoration-none">
                                    <i class="bi bi-file-earmark-arrow-down me-1"></i>{{ $module->file_original_name ?: basename($module->file) }}
                                </a>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-warning btn-edit"
                                        data-id="{{ $module->id }}"
                                        data-subject-id="{{ $module->subject_id }}"
                                        data-kelas="{{ $module->kelas }}"
                                        data-nama-modul="{{ $module->nama_modul }}"
                                        data-file-name="{{ $module->file_original_name ?: basename($module->file) }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-delete"
                                        data-id="{{ $module->id }}" data-name="{{ $module->nama_modul }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center text-muted py-5">
            Belum ada modul. Klik <strong>Tambah Modul</strong> untuk mengupload yang pertama.
        </div>
    </div>
@endforelse

{{-- Modal: tambah/edit modul --}}
<div class="modal fade" id="modal-module" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-module-title"><i class="bi bi-journal-plus me-2 text-primary"></i>Tambah Modul</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="module-id">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Mata Pelajaran</label>
                    <select id="field-subject" class="form-select">
                        <option value="">-- Pilih Mata Pelajaran --</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->subject_name }}</option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback" id="err-subject"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Kelas <span class="text-danger">*</span></label>
                    <select id="field-kelas" class="form-select">
                        <option value="">-- Pilih Kelas --</option>
                        @foreach($kelasList as $k)
                            <option value="{{ $k }}">{{ $k }}</option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback" id="err-kelas"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Modul <span class="text-danger">*</span></label>
                    <input type="text" id="field-nama-modul" class="form-control" placeholder="mis. Modul Pecahan Bab 1">
                    <div class="invalid-feedback" id="err-nama-modul"></div>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold">File Modul <span class="text-danger" id="file-required-mark">*</span></label>
                    <input type="file" id="field-file" class="form-control" accept=".xlsx,.xls,.pdf,.doc,.docx">
                    <div class="form-text">Excel (.xlsx/.xls), PDF, atau Word (.doc/.docx) — maksimal 20 MB.</div>
                    <div class="small text-muted mt-1" id="current-file-info" style="display:none;"></div>
                    <div class="invalid-feedback" id="err-file"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btn-save">Simpan</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    var token = '{{ csrf_token() }}';

    function resetForm() {
        $('#module-id').val('');
        $('#field-subject, #field-kelas').val('').trigger('change');
        $('#field-nama-modul').val('');
        $('#field-file').val('');
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        $('#current-file-info').hide().text('');
        $('#file-required-mark').show();
    }

    $('#btn-add').on('click', function () {
        resetForm();
        $('#modal-module-title').html('<i class="bi bi-journal-plus me-2 text-primary"></i>Tambah Modul');
        $('#modal-module').modal('show');
    });

    $(document).on('click', '.btn-edit', function () {
        resetForm();
        var $b = $(this);
        $('#modal-module-title').html('<i class="bi bi-pencil-square me-2 text-warning"></i>Edit Modul');
        $('#module-id').val($b.data('id'));
        $('#field-subject').val($b.data('subject-id') || '');
        $('#field-kelas').val($b.data('kelas'));
        $('#field-nama-modul').val($b.data('nama-modul'));
        $('#current-file-info').show().html('<i class="bi bi-info-circle me-1"></i>File saat ini: <strong>' + $('<div>').text($b.data('file-name')).html() + '</strong>. Kosongkan bila tidak ingin mengganti.');
        $('#file-required-mark').hide();
        $('#modal-module').modal('show');
    });

    $('#btn-save').on('click', function () {
        var id = $('#module-id').val();
        var url = id ? '/admin/modules/' + id : '{{ route('admin.modules.store') }}';

        var fd = new FormData();
        fd.append('subject_id', $('#field-subject').val() || '');
        fd.append('kelas', $('#field-kelas').val());
        fd.append('nama_modul', $('#field-nama-modul').val());
        if ($('#field-file')[0].files[0]) {
            fd.append('file', $('#field-file')[0].files[0]);
        }
        fd.append('_token', token);
        if (id) fd.append('_method', 'PUT');

        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        $.ajax({
            url: url, type: 'POST', data: fd, processData: false, contentType: false,
            success: function (res) {
                $('#modal-module').modal('hide');
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false })
                    .then(function () { location.reload(); });
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    var err = xhr.responseJSON.errors ?? {};
                    if (err.subject_id) { $('#field-subject').addClass('is-invalid'); $('#err-subject').text(err.subject_id[0]); }
                    if (err.kelas)      { $('#field-kelas').addClass('is-invalid'); $('#err-kelas').text(err.kelas[0]); }
                    if (err.nama_modul) { $('#field-nama-modul').addClass('is-invalid'); $('#err-nama-modul').text(err.nama_modul[0]); }
                    if (err.file)       { $('#field-file').addClass('is-invalid'); $('#err-file').text(err.file[0]); }
                } else {
                    Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan.', 'error');
                }
            }
        });
    });

    $(document).on('click', '.btn-delete', function () {
        var id = $(this).data('id'), name = $(this).data('name');
        Swal.fire({
            title: 'Hapus modul ini?',
            html: '<strong>' + $('<div>').text(name).html() + '</strong> akan dihapus permanen beserta filenya.',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url: '/admin/modules/' + id, type: 'DELETE', data: { _token: token },
                success: function (res) {
                    Swal.fire({ icon: 'success', title: 'Dihapus', text: res.message, timer: 2000, showConfirmButton: false })
                        .then(function () { location.reload(); });
                },
                error: function (xhr) { Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan.', 'error'); }
            });
        });
    });
});
</script>
@endpush
