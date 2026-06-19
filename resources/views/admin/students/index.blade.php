@extends('admin.layouts.app')

@section('title', 'Data Siswa - LIVO Admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="mb-4 d-flex justify-content-between align-items-start">
            <div>
                <h1 class="fs-3 mb-1">Data Siswa LIVO</h1>
                <p class="text-muted mb-0">Kelola semua data siswa yang aktif dan non-aktif.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.students.template') }}" class="btn btn-outline-success">
                    <i class="bi bi-download me-1"></i> Download Template
                </a>
                <button class="btn btn-success" id="btn-import">
                    <i class="bi bi-file-earmark-excel me-1"></i> Upload Excel
                </button>
                <a href="{{ route('admin.students.create') }}" class="btn btn-primary">
                    <i class="bi bi-person-plus me-1"></i> Tambah Data Siswa
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white px-4 py-3">
                <h4 class="mb-0 h5">Daftar Siswa</h4>
            </div>
            <div class="table-responsive p-3">
                <table class="table table-hover mb-0" id="students-table">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4">#</th>
                            <th>Kode Reg</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Program</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal Import Excel --}}
<div class="modal fade" id="modal-import" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Data Siswa dari Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info small d-flex align-items-start gap-2">
                    <i class="bi bi-info-circle-fill mt-1"></i>
                    <div>
                        Gunakan <a href="{{ route('admin.students.template') }}" class="fw-semibold">template Excel</a> yang disediakan.
                        Untuk kolom ber-<strong>ID</strong> (Program, Jenjang, Paket, Mapel, Jadwal),
                        isi dengan <strong>ID</strong> yang tertera pada sheet master terkait
                        (<em>Master Program / Master Jenjang / Master Paket / Master Mapel / Master Jadwal</em>).
                        Kolom <strong>ID Mapel</strong> dan <strong>ID Jadwal</strong> boleh lebih dari satu, dipisahkan koma (mis. <code>1,2</code>). Baris header tidak diimport.
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
$(document).ready(function() {
    var table = $('#students-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.data.students') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'px-4' },
            { data: 'registration_code', name: 'registration_code' },
            { data: 'nis', name: 'nis', defaultContent: '-' },
            { data: 'full_name', name: 'full_name' },
            { data: 'grade', name: 'grade', defaultContent: '-' },
            { data: 'program', name: 'program' },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            paginate: { first: "Pertama", last: "Terakhir", next: "Selanjutnya", previous: "Sebelumnya" }
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
            url: '{{ route('admin.students.import') }}', type: 'POST',
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

    // Delete with SweetAlert2
    $('#students-table').on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');

        Swal.fire({
            title: 'Hapus Data Siswa?',
            html: 'Data siswa <strong>' + name + '</strong> akan dihapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/admin/students/' + id,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(res) {
                        Swal.fire({ title: 'Terhapus!', text: res.message, icon: 'success', timer: 2000, showConfirmButton: false });
                        table.ajax.reload();
                    },
                    error: function() {
                        Swal.fire({ title: 'Gagal!', text: 'Terjadi kesalahan saat menghapus.', icon: 'error' });
                    }
                });
            }
        });
    });
});
</script>
@endpush
