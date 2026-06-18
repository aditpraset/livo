@extends('admin.layouts.app')

@section('title', 'Master Harga - LIVO Admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center p-4">
    <div>
        <h2 class="page-title">Master Harga</h2>
        <p class="text-muted mb-0 small">Tentukan harga berdasarkan paket, program, jenjang & durasi</p>
    </div>
    <button class="btn btn-primary" id="btn-add">
        <i class="bi bi-plus-lg me-1"></i> Tambah Harga
    </button>
</div>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <table id="pricings-table" class="table table-hover align-middle w-100">
            <thead class="table-light">
                <tr>
                    <th width="50">#</th>
                    <th>Paket</th>
                    <th>Program</th>
                    <th>Jenjang</th>
                    <th class="text-center">Durasi</th>
                    <th>Harga</th>
                    <th width="100" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

{{-- Modal --}}
<div class="modal fade" id="modal-pricing" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-title">Tambah Harga</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="pr-id">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Paket <span class="text-danger">*</span></label>
                    <select id="pr-package" class="form-select">
                        <option value="">— Pilih Paket —</option>
                        @foreach ($packages as $package)
                            <option value="{{ $package->id }}">{{ $package->package_name }}</option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback" id="err-package"></div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Program <span class="text-danger">*</span></label>
                        <select id="pr-program" class="form-select">
                            <option value="">— Pilih Program —</option>
                            @foreach ($programs as $program)
                                <option value="{{ $program->id }}">{{ $program->program_name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="err-program"></div>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Jenjang <span class="text-danger">*</span></label>
                        <select id="pr-grade" class="form-select">
                            <option value="">— Pilih Jenjang —</option>
                            @foreach ($grades as $grade)
                                <option value="{{ $grade->id }}">{{ $grade->grade_name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="err-grade"></div>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Durasi <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" id="pr-duration" class="form-control" min="1" placeholder="3">
                            <span class="input-group-text">bulan</span>
                        </div>
                        <div class="invalid-feedback" id="err-duration"></div>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Harga <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" id="pr-price" class="form-control" min="0" placeholder="500000">
                        </div>
                        <div class="invalid-feedback" id="err-price"></div>
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
@endsection

@push('js')
<script>
$(function () {
    var table = $('#pricings-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.pricings.data') }}",
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'package_name' },
            { data: 'program_name' },
            { data: 'grade_name' },
            { data: 'duration', className: 'text-center' },
            { data: 'price' },
            { data: 'action', orderable: false, searchable: false, className: 'text-center' },
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json' }
    });

    function resetModal() {
        $('#pr-id, #pr-duration, #pr-price').val('');
        $('#pr-package, #pr-program, #pr-grade').val('');
        $('.form-control, .form-select').removeClass('is-invalid');
        $('#err-package, #err-program, #err-grade, #err-duration, #err-price').text('');
    }

    $('#btn-add').on('click', function () {
        resetModal();
        $('#modal-title').text('Tambah Harga');
        $('#modal-pricing').modal('show');
    });

    $(document).on('click', '.btn-edit', function () {
        resetModal();
        var btn = $(this);
        $('#modal-title').text('Edit Harga');
        $('#pr-id').val(btn.data('id'));
        $('#pr-package').val(btn.data('package'));
        $('#pr-program').val(btn.data('program'));
        $('#pr-grade').val(btn.data('grade'));
        $('#pr-duration').val(btn.data('duration'));
        $('#pr-price').val(btn.data('price'));
        $('#modal-pricing').modal('show');
    });

    $('#btn-save').on('click', function () {
        var id   = $('#pr-id').val();
        var url  = id ? '/admin/pricings/' + id : '{{ route("admin.pricings.store") }}';
        var type = id ? 'PUT' : 'POST';

        $.ajax({
            url: url, type: type,
            data: {
                package_id: $('#pr-package').val(),
                program_id: $('#pr-program').val(),
                grade_id:   $('#pr-grade').val(),
                duration:   $('#pr-duration').val(),
                price:      $('#pr-price').val(),
                _token:     '{{ csrf_token() }}'
            },
            success: function (res) {
                $('#modal-pricing').modal('hide');
                table.ajax.reload();
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false });
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    var err = xhr.responseJSON.errors ?? {};
                    if (err.package_id) { $('#pr-package').addClass('is-invalid');  $('#err-package').text(err.package_id[0]); }
                    if (err.program_id) { $('#pr-program').addClass('is-invalid');  $('#err-program').text(err.program_id[0]); }
                    if (err.grade_id)   { $('#pr-grade').addClass('is-invalid');    $('#err-grade').text(err.grade_id[0]); }
                    if (err.duration)   { $('#pr-duration').addClass('is-invalid'); $('#err-duration').text(err.duration[0]); }
                    if (err.price)      { $('#pr-price').addClass('is-invalid');    $('#err-price').text(err.price[0]); }
                } else {
                    Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan.', 'error');
                }
            }
        });
    });

    $(document).on('click', '.btn-delete', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Hapus Harga?', text: 'Data harga ini akan dihapus permanen.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal'
        }).then(function (r) {
            if (r.isConfirmed) {
                $.ajax({
                    url: '/admin/pricings/' + id, type: 'DELETE',
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
