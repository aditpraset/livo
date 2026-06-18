@extends('admin.layouts.app')

@section('title', 'Master Program - LIVO Admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center p-4">
    <div>
        <h2 class="page-title">Master Program</h2>
        <p class="text-muted mb-0 small">Kelola daftar program beserta kuotanya</p>
    </div>
    <button class="btn btn-primary" id="btn-add">
        <i class="bi bi-plus-lg me-1"></i> Tambah Program
    </button>
</div>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <table id="programs-table" class="table table-hover align-middle w-100">
            <thead class="table-light">
                <tr>
                    <th width="60">#</th>
                    <th>Nama Program</th>
                    <th width="120" class="text-center">Kuota</th>
                    <th width="120" class="text-center">Durasi</th>
                    <th width="140" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="modal-program" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-title">Tambah Program</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="program-id">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Program <span class="text-danger">*</span></label>
                    <input type="text" id="field-name" class="form-control" placeholder="cth: Regular">
                    <div class="invalid-feedback" id="err-name"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Kuota <span class="text-danger">*</span></label>
                    <input type="number" id="field-kuota" class="form-control" min="0" placeholder="cth: 8">
                    <div class="invalid-feedback" id="err-kuota"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Durasi (bulan) <span class="text-danger">*</span></label>
                    <input type="number" id="field-duration" class="form-control" min="0" placeholder="cth: 3">
                    <div class="invalid-feedback" id="err-duration"></div>
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
    var table = $('#programs-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.programs.data') }}",
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'program_name' },
            { data: 'kuota', className: 'text-center' },
            { data: 'duration', className: 'text-center' },
            { data: 'action', orderable: false, searchable: false, className: 'text-center' },
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json' }
    });

    function resetModal() {
        $('#program-id').val('');
        $('#field-name').val('').removeClass('is-invalid');
        $('#field-kuota').val('').removeClass('is-invalid');
        $('#field-duration').val('').removeClass('is-invalid');
        $('#err-name').text('');
        $('#err-kuota').text('');
        $('#err-duration').text('');
    }

    $('#btn-add').on('click', function () {
        resetModal();
        $('#modal-title').text('Tambah Program');
        $('#modal-program').modal('show');
    });

    $(document).on('click', '.btn-edit', function () {
        resetModal();
        $('#modal-title').text('Edit Program');
        $('#program-id').val($(this).data('id'));
        $('#field-name').val($(this).data('name'));
        $('#field-kuota').val($(this).data('kuota'));
        $('#field-duration').val($(this).data('duration'));
        $('#modal-program').modal('show');
    });

    $('#btn-save').on('click', function () {
        var id   = $('#program-id').val();
        var url  = id ? '/admin/programs/' + id : '{{ route("admin.programs.store") }}';
        var type = id ? 'PUT' : 'POST';

        $.ajax({
            url: url, type: type,
            data: {
                program_name: $('#field-name').val(),
                kuota: $('#field-kuota').val(),
                duration: $('#field-duration').val(),
                _token: '{{ csrf_token() }}'
            },
            success: function (res) {
                $('#modal-program').modal('hide');
                table.ajax.reload();
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false });
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    var err = xhr.responseJSON.errors;
                    if (err.program_name) { $('#field-name').addClass('is-invalid'); $('#err-name').text(err.program_name[0]); }
                    if (err.kuota) { $('#field-kuota').addClass('is-invalid'); $('#err-kuota').text(err.kuota[0]); }
                    if (err.duration) { $('#field-duration').addClass('is-invalid'); $('#err-duration').text(err.duration[0]); }
                } else {
                    Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan.', 'error');
                }
            }
        });
    });

    $(document).on('click', '.btn-delete', function () {
        var id = $(this).data('id'), name = $(this).data('name');
        Swal.fire({
            title: 'Hapus Program?',
            text: '"' + name + '" akan dihapus permanen.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/admin/programs/' + id, type: 'DELETE',
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
