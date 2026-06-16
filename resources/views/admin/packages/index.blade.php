@extends('admin.layouts.app')

@section('title', 'Master Paket - LIVO Admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center p-4">
    <div>
        <h2 class="page-title">Master Paket Belajar</h2>
        <p class="text-muted mb-0 small">Kelola daftar paket yang dapat dipilih saat pendaftaran</p>
    </div>
    <button class="btn btn-primary" id="btn-add">
        <i class="bi bi-plus-lg me-1"></i> Tambah Paket
    </button>
</div>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <table id="packages-table" class="table table-hover align-middle w-100">
            <thead class="table-light">
                <tr>
                    <th width="50">#</th>
                    <th>Nama Paket</th>
                    <th>Harga</th>
                    <th>Jumlah Sesi</th>
                    <th>Deskripsi</th>
                    <th width="100" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="modal-package" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-title">Tambah Paket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="pkg-id">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Paket <span class="text-danger">*</span></label>
                    <input type="text" id="pkg-name" class="form-control" placeholder="cth: Paket Intensif UTBK">
                    <div class="invalid-feedback" id="err-name"></div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Harga <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" id="pkg-price" class="form-control" min="0" placeholder="500000">
                        </div>
                        <div class="invalid-feedback" id="err-price"></div>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Jumlah Sesi <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" id="pkg-sessions" class="form-control" min="1" placeholder="20">
                            <span class="input-group-text">sesi</span>
                        </div>
                        <div class="invalid-feedback" id="err-sessions"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Deskripsi</label>
                    <textarea id="pkg-desc" class="form-control" rows="3" placeholder="Keterangan singkat tentang paket ini"></textarea>
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
    var table = $('#packages-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.packages.data') }}",
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'package_name' },
            { data: 'price' },
            { data: 'total_sessions' },
            { data: 'description', orderable: false, defaultContent: '<span class="text-muted">—</span>' },
            { data: 'action', orderable: false, searchable: false, className: 'text-center' },
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json' }
    });

    function resetModal() {
        $('#pkg-id, #pkg-name, #pkg-price, #pkg-sessions, #pkg-desc').val('');
        $('#pkg-name, #pkg-price, #pkg-sessions').removeClass('is-invalid');
        $('#err-name, #err-price, #err-sessions').text('');
    }

    $('#btn-add').on('click', function () {
        resetModal();
        $('#modal-title').text('Tambah Paket');
        $('#modal-package').modal('show');
    });

    $(document).on('click', '.btn-edit', function () {
        resetModal();
        var btn = $(this);
        $('#modal-title').text('Edit Paket');
        $('#pkg-id').val(btn.data('id'));
        $('#pkg-name').val(btn.data('name'));
        $('#pkg-price').val(btn.data('price'));
        $('#pkg-sessions').val(btn.data('sessions'));
        $('#pkg-desc').val(btn.data('desc'));
        $('#modal-package').modal('show');
    });

    $('#btn-save').on('click', function () {
        var id   = $('#pkg-id').val();
        var url  = id ? '/admin/packages/' + id : '{{ route("admin.packages.store") }}';
        var type = id ? 'PUT' : 'POST';

        $.ajax({
            url: url, type: type,
            data: {
                package_name:   $('#pkg-name').val(),
                price:          $('#pkg-price').val(),
                total_sessions: $('#pkg-sessions').val(),
                description:    $('#pkg-desc').val(),
                _token:         '{{ csrf_token() }}'
            },
            success: function (res) {
                $('#modal-package').modal('hide');
                table.ajax.reload();
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false });
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    var err = xhr.responseJSON.errors ?? {};
                    if (err.package_name)   { $('#pkg-name').addClass('is-invalid');     $('#err-name').text(err.package_name[0]); }
                    if (err.price)          { $('#pkg-price').addClass('is-invalid');    $('#err-price').text(err.price[0]); }
                    if (err.total_sessions) { $('#pkg-sessions').addClass('is-invalid'); $('#err-sessions').text(err.total_sessions[0]); }
                } else {
                    Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan.', 'error');
                }
            }
        });
    });

    $(document).on('click', '.btn-delete', function () {
        var id = $(this).data('id'), name = $(this).data('name');
        Swal.fire({
            title: 'Hapus Paket?', text: '"' + name + '" akan dihapus permanen.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal'
        }).then(function (r) {
            if (r.isConfirmed) {
                $.ajax({
                    url: '/admin/packages/' + id, type: 'DELETE',
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
