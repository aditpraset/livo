@extends('admin.layouts.app')

@section('title', 'Master Jadwal - LIVO Admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center p-4">
    <div>
        <h2 class="page-title">Master Jadwal</h2>
        <p class="text-muted mb-0 small">Buat jadwal kelas berdasarkan sesi & hari</p>
    </div>
    <button class="btn btn-primary" id="btn-add">
        <i class="bi bi-plus-lg me-1"></i> Tambah Jadwal
    </button>
</div>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <table id="schedules-table" class="table table-hover align-middle w-100">
            <thead class="table-light">
                <tr>
                    <th width="50">#</th>
                    <th>Kelas</th>
                    <th>Program</th>
                    <th>Sesi</th>
                    <th>Hari</th>
                    <th width="100" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="modal-schedule" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-title">Tambah Jadwal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="sch-id">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Kelas <span class="text-danger">*</span></label>
                    <select id="sch-kelas" class="form-select">
                        <option value="">— Pilih Kelas —</option>
                        @foreach ($kelasList as $kelas)
                            <option value="{{ $kelas }}">{{ $kelas }}</option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback" id="err-kelas"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Program <span class="text-danger">*</span></label>
                    <select id="sch-program" class="form-select">
                        <option value="">— Pilih Program —</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}">{{ $program->program_name }}</option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback" id="err-program"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Sesi <span class="text-danger">*</span></label>
                    <select id="sch-session" class="form-select">
                        <option value="">— Pilih Sesi —</option>
                        @foreach ($sessions as $session)
                            <option value="{{ $session->id }}">
                                {{ $session->name }} ({{ \Illuminate\Support\Str::of($session->time_start)->substr(0, 5) }} - {{ \Illuminate\Support\Str::of($session->time_end)->substr(0, 5) }})
                            </option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback" id="err-session"></div>
                </div>
                <div class="mb-1">
                    <label class="form-label fw-semibold">Hari <span class="text-danger">*</span></label>
                    <select id="sch-hari" class="form-select">
                        <option value="">— Pilih Hari —</option>
                        @foreach ($hariList as $hari)
                            <option value="{{ $hari }}">{{ $hari }}</option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback" id="err-hari"></div>
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
    var table = $('#schedules-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.class-schedules.data') }}",
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'kelas' },
            { data: 'program_name' },
            { data: 'session_name' },
            { data: 'hari' },
            { data: 'action', orderable: false, searchable: false, className: 'text-center' },
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json' }
    });

    function resetModal() {
        $('#sch-id, #sch-kelas').val('');
        $('#sch-program, #sch-session, #sch-hari').val('');
        $('#sch-kelas, #sch-program, #sch-session, #sch-hari').removeClass('is-invalid');
        $('#err-kelas, #err-program, #err-session, #err-hari').text('');
    }

    $('#btn-add').on('click', function () {
        resetModal();
        $('#modal-title').text('Tambah Jadwal');
        $('#modal-schedule').modal('show');
    });

    $(document).on('click', '.btn-edit', function () {
        resetModal();
        var btn = $(this);
        $('#modal-title').text('Edit Jadwal');
        $('#sch-id').val(btn.data('id'));
        $('#sch-kelas').val(btn.data('kelas'));
        $('#sch-program').val(btn.data('program'));
        $('#sch-session').val(btn.data('session'));
        $('#sch-hari').val(String(btn.data('hari')));
        $('#modal-schedule').modal('show');
    });

    $('#btn-save').on('click', function () {
        var id   = $('#sch-id').val();
        var url  = id ? '/admin/class-schedules/' + id : '{{ route("admin.class-schedules.store") }}';
        var type = id ? 'PUT' : 'POST';

        $.ajax({
            url: url, type: type,
            data: {
                kelas:      $('#sch-kelas').val(),
                program_id: $('#sch-program').val(),
                session_id: $('#sch-session').val(),
                hari:       $('#sch-hari').val(),
                _token:     '{{ csrf_token() }}'
            },
            success: function (res) {
                $('#modal-schedule').modal('hide');
                table.ajax.reload();
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false });
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    var err = xhr.responseJSON.errors ?? {};
                    if (err.kelas)      { $('#sch-kelas').addClass('is-invalid');   $('#err-kelas').text(err.kelas[0]); }
                    if (err.program_id) { $('#sch-program').addClass('is-invalid'); $('#err-program').text(err.program_id[0]); }
                    if (err.session_id) { $('#sch-session').addClass('is-invalid'); $('#err-session').text(err.session_id[0]); }
                    if (err.hari)       { $('#sch-hari').addClass('is-invalid'); $('#err-hari').text(err.hari[0]); }
                } else {
                    Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan.', 'error');
                }
            }
        });
    });

    $(document).on('click', '.btn-delete', function () {
        var id = $(this).data('id'), kelas = $(this).data('kelas');
        Swal.fire({
            title: 'Hapus Jadwal?', text: 'Jadwal "' + kelas + '" akan dihapus permanen.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal'
        }).then(function (r) {
            if (r.isConfirmed) {
                $.ajax({
                    url: '/admin/class-schedules/' + id, type: 'DELETE',
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
