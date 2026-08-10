@extends('admin.layouts.app')

@section('title', 'Grouping Siswa - LIVO Admin')

@push('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="fs-3 mb-1">Grouping Siswa</h1>
                <p class="text-muted">Preset Sesi + Hari bernama — dipakai admin saat "Buat Jadwal per Grouping" supaya tidak perlu pilih ulang tiap kali. Mata pelajaran &amp; tutor diisi saat pembuatan jadwal, bukan di sini.</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-group">
                <i class="bi bi-plus-lg me-2"></i> Tambah Grouping
            </button>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white px-4 py-3">
                <h4 class="mb-0 h5">Daftar Grouping</h4>
            </div>
            <div class="table-responsive p-3">
                <table class="table table-hover mb-0" id="groups-table">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4">#</th>
                            <th>Nama Grouping</th>
                            <th>Sesi</th>
                            <th>Hari</th>
                            <th>Anggota</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Grouping -->
<div class="modal fade" id="modal-group" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="form-group">
                @csrf
                <input type="hidden" name="id" id="group-id">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-title">Tambah Grouping Siswa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Grouping <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="Contoh: SD Sesi Pagi Senin" required>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Sesi <span class="text-danger">*</span></label>
                                <select name="session_id" id="session_id" class="form-select" required>
                                    <option value="">-- Pilih Sesi --</option>
                                    @foreach($scheduleSessions as $sess)
                                        <option value="{{ $sess->id }}">{{ $sess->name }} ({{ substr($sess->time_start, 0, 5) }}–{{ substr($sess->time_end, 0, 5) }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Hari <span class="text-danger">*</span></label>
                                <select name="hari" id="hari" class="form-select" required>
                                    <option value="">-- Pilih Hari --</option>
                                    @foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'] as $h)
                                        <option value="{{ $h }}">{{ $h }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Anggota Siswa</label>
                        <select name="student_ids[]" id="student_ids" class="form-select" multiple>
                            @foreach($students as $s)
                                <option value="{{ $s->id }}">{{ $s->full_name }}{{ $s->grade ? ' — ' . $s->grade : '' }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Siswa yang dipilih di sini yang akan dijadwalkan saat group ini dipakai membuat jadwal.</div>
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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('#student_ids').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Cari & pilih siswa...',
        dropdownParent: $('#modal-group')
    });

    var table = $('#groups-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.student-groups.data') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'px-4' },
            { data: 'name', name: 'name' },
            { data: 'session_name', name: 'session.name' },
            { data: 'hari', name: 'hari' },
            { data: 'students_count', name: 'students_count', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            paginate: { first: "Pertama", last: "Terakhir", next: "Selanjutnya", previous: "Sebelumnya" }
        }
    });

    $('#modal-group').on('hidden.bs.modal', function () {
        $('#form-group')[0].reset();
        $('#group-id').val('');
        $('#student_ids').val(null).trigger('change');
        $('#modal-title').text('Tambah Grouping Siswa');
    });

    $('#form-group').on('submit', function(e) {
        e.preventDefault();
        var id = $('#group-id').val();
        var url = id ? "/admin/student-groups/" + id : "{{ route('admin.student-groups.store') }}";
        var type = id ? "PUT" : "POST";

        $.ajax({
            url: url,
            type: type,
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) {
                    Swal.fire({ title: 'Berhasil!', text: res.message, icon: 'success', timer: 2000, showConfirmButton: false });
                    $('#modal-group').modal('hide');
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

    $('#groups-table').on('click', '.btn-edit', function() {
        var id = $(this).data('id');
        $.get('/admin/student-groups/' + id, function(data) {
            $('#group-id').val(data.id);
            $('#name').val(data.name);
            $('#session_id').val(data.session_id);
            $('#hari').val(data.hari);
            $('#student_ids').val(data.student_ids || []).trigger('change');
            $('#modal-title').text('Edit Grouping Siswa');
            $('#modal-group').modal('show');
        });
    });

    $('#groups-table').on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');

        Swal.fire({
            title: 'Hapus Grouping?',
            html: 'Grouping <strong>' + name + '</strong> akan dihapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/admin/student-groups/' + id,
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
