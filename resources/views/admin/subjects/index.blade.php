@extends('admin.layouts.app')

@section('title', 'Master Mata Pelajaran - LIVO Admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center p-4">
    <div>
        <h2 class="page-title">Master Mata Pelajaran</h2>
        <p class="text-muted mb-0 small">Kelola daftar mata pelajaran yang tersedia</p>
    </div>
    <button class="btn btn-primary" id="btn-add">
        <i class="bi bi-plus-lg me-1"></i> Tambah Mata Pelajaran
    </button>
</div>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <table id="subjects-table" class="table table-hover align-middle w-100">
            <thead class="table-light">
                <tr>
                    <th width="60">#</th>
                    <th>Nama Mata Pelajaran</th>
                    <th>Jenjang</th>
                    <th width="170" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="modal-subject" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-title">Tambah Mata Pelajaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="subject-id">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Mata Pelajaran <span class="text-danger">*</span></label>
                    <input type="text" id="field-name" class="form-control" placeholder="cth: Matematika">
                    <div class="invalid-feedback" id="err-name"></div>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold">Jenjang yang Mendapat Mapel Ini</label>
                    <select id="field-grades" class="form-select" multiple size="5">
                        @foreach($grades as $grade)
                            <option value="{{ $grade->id }}">{{ $grade->grade_name }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Tahan Ctrl (Cmd di Mac) untuk memilih lebih dari satu jenjang.</small>
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
    var table = $('#subjects-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.subjects.data') }}",
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'subject_name' },
            { data: 'jenjang', orderable: false, searchable: false },
            { data: 'action', orderable: false, searchable: false, className: 'text-center' },
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json' }
    });

    function resetModal() {
        $('#subject-id').val('');
        $('#field-name').val('').removeClass('is-invalid');
        $('#err-name').text('');
        $('#field-grades').val([]);
    }

    $('#btn-add').on('click', function () {
        resetModal();
        $('#modal-title').text('Tambah Mata Pelajaran');
        $('#modal-subject').modal('show');
    });

    $(document).on('click', '.btn-edit', function () {
        resetModal();
        $('#modal-title').text('Edit Mata Pelajaran');
        $('#subject-id').val($(this).data('id'));
        $('#field-name').val($(this).data('name'));
        var grades = $(this).data('grades') || [];
        $('#field-grades').val(grades.map(String));
        $('#modal-subject').modal('show');
    });

    $('#btn-save').on('click', function () {
        var id   = $('#subject-id').val();
        var url  = id ? '/admin/subjects/' + id : '{{ route("admin.subjects.store") }}';
        var type = id ? 'PUT' : 'POST';

        $.ajax({
            url: url, type: type,
            data: {
                subject_name: $('#field-name').val(),
                grade_ids: $('#field-grades').val() || [],
                _token: '{{ csrf_token() }}'
            },
            success: function (res) {
                $('#modal-subject').modal('hide');
                table.ajax.reload();
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false });
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    var err = xhr.responseJSON.errors;
                    if (err.subject_name) { $('#field-name').addClass('is-invalid'); $('#err-name').text(err.subject_name[0]); }
                } else {
                    Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan.', 'error');
                }
            }
        });
    });

    $(document).on('click', '.btn-delete', function () {
        var id = $(this).data('id'), name = $(this).data('name');
        Swal.fire({
            title: 'Hapus Mata Pelajaran?',
            text: '"' + name + '" akan dihapus permanen.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/admin/subjects/' + id, type: 'DELETE',
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
