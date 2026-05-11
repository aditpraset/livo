@extends('admin.layouts.app')

@section('title', 'Sesi Pembelajaran - LIVO Admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="fs-3 mb-1">Sesi Pembelajaran</h1>
                <p class="text-muted">Kelola waktu sesi pembelajaran siswa.</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-session">
                <i class="bi bi-plus-lg me-2"></i> Tambah Sesi
            </button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white px-4 py-3">
                <h4 class="mb-0 h5">Daftar Sesi</h4>
            </div>
            <div class="table-responsive p-3">
                <table class="table table-hover mb-0" id="sessions-table">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4">#</th>
                            <th>Nama Sesi</th>
                            <th>Waktu Mulai</th>
                            <th>Waktu Selesai</th>
                            <th>Catatan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Session -->
<div class="modal fade" id="modal-session" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-session">
                @csrf
                <input type="hidden" name="id" id="session-id">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-title">Tambah Sesi Pembelajaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Sesi <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="Contoh: Sesi Pagi 1" required>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Waktu Mulai <span class="text-danger">*</span></label>
                                <input type="time" name="time_start" id="time_start" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Waktu Selesai <span class="text-danger">*</span></label>
                                <input type="time" name="time_end" id="time_end" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Tambahkan catatan jika ada..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-save">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(document).ready(function() {
    var table = $('#sessions-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.schedule-sessions.data') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'px-4' },
            { data: 'name', name: 'name' },
            { data: 'time_start', name: 'time_start' },
            { data: 'time_end', name: 'time_end' },
            { data: 'notes', name: 'notes', defaultContent: '-' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            paginate: { first: "Pertama", last: "Terakhir", next: "Selanjutnya", previous: "Sebelumnya" }
        }
    });

    $('#modal-session').on('hidden.bs.modal', function () {
        $('#form-session')[0].reset();
        $('#session-id').val('');
        $('#modal-title').text('Tambah Sesi Pembelajaran');
    });

    $('#form-session').on('submit', function(e) {
        e.preventDefault();
        var id = $('#session-id').val();
        var url = id ? "/admin/schedule-sessions/" + id : "{{ route('admin.schedule-sessions.store') }}";
        var type = id ? "PUT" : "POST";

        $.ajax({
            url: url,
            type: type,
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) {
                    Swal.fire({ title: 'Berhasil!', text: res.message, icon: 'success', timer: 2000, showConfirmButton: false });
                    $('#modal-session').modal('hide');
                    table.ajax.reload();
                }
            },
            error: function(xhr) {
                var err = xhr.responseJSON;
                if (err.errors) {
                    var msg = '';
                    $.each(err.errors, function(k, v) { msg += v + '<br>'; });
                    Swal.fire({ title: 'Gagal!', html: msg, icon: 'error' });
                } else {
                    Swal.fire({ title: 'Gagal!', text: 'Terjadi kesalahan sistem.', icon: 'error' });
                }
            }
        });
    });

    $('#sessions-table').on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        $.get('/admin/schedule-sessions/' + id, function(data) {
            $('#session-id').val(data.id);
            $('#name').val(data.name);
            $('#time_start').val(data.time_start);
            $('#time_end').val(data.time_end);
            $('#notes').val(data.notes);
            $('#modal-title').text('Edit Sesi Pembelajaran');
            $('#modal-session').modal('show');
        });
    });

    $('#sessions-table').on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');

        Swal.fire({
            title: 'Hapus Sesi?',
            html: 'Sesi <strong>' + name + '</strong> akan dihapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/admin/schedule-sessions/' + id,
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
