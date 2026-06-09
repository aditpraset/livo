@extends('admin.layouts.app')

@section('title', 'Master Promo - LIVO Admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h2 class="page-title">Master Promo & Diskon</h2>
        <p class="text-muted mb-0 small">Kelola kode promo dan potongan harga pendaftaran</p>
    </div>
    <button class="btn btn-primary" id="btn-add">
        <i class="bi bi-plus-lg me-1"></i> Tambah Promo
    </button>
</div>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <table id="promos-table" class="table table-hover align-middle w-100">
            <thead class="table-light">
                <tr>
                    <th width="50">#</th>
                    <th>Nama Promo</th>
                    <th>Kode</th>
                    <th>Jenis Diskon</th>
                    <th>Nilai Diskon</th>
                    <th>Min. Harga Paket</th>
                    <th>Masa Berlaku</th>
                    <th class="text-center">Status</th>
                    <th width="100" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

{{-- Modal Promo --}}
<div class="modal fade" id="modal-promo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-title">Tambah Promo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="promo-id">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label fw-semibold">Nama Promo <span class="text-danger">*</span></label>
                        <input type="text" id="promo-name" class="form-control" placeholder="cth: Promo Tahun Ajaran Baru">
                        <div class="invalid-feedback" id="err-name"></div>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Kode Promo <span class="text-danger">*</span></label>
                        <input type="text" id="promo-code" class="form-control text-uppercase" placeholder="cth: HEMAT50" style="text-transform:uppercase">
                        <div class="form-text">Akan disimpan dalam huruf kapital.</div>
                        <div class="invalid-feedback" id="err-code"></div>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Jenis Diskon <span class="text-danger">*</span></label>
                        <select id="promo-type" class="form-select">
                            <option value="percentage">Persentase (%)</option>
                            <option value="amount">Nominal (Rp)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Nilai Diskon <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" id="promo-value" class="form-control" min="0" placeholder="10">
                            <span class="input-group-text" id="discount-unit">%</span>
                        </div>
                        <div class="invalid-feedback" id="err-value"></div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Min. Harga Paket</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" id="promo-min" class="form-control" min="0" placeholder="0">
                        </div>
                        <div class="form-text">Kosongkan jika tidak ada minimum.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Berlaku Dari</label>
                        <input type="date" id="promo-from" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Berlaku Sampai</label>
                        <input type="date" id="promo-until" class="form-control">
                        <div class="invalid-feedback" id="err-until"></div>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check form-switch mb-1">
                            <input class="form-check-input" type="checkbox" id="promo-active" checked>
                            <label class="form-check-label fw-semibold" for="promo-active">Promo Aktif</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Deskripsi</label>
                        <textarea id="promo-desc" class="form-control" rows="2" placeholder="Keterangan tambahan tentang promo ini"></textarea>
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
    var table = $('#promos-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.promos.data') }}",
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'name' },
            { data: 'code' },
            { data: 'discount_type', render: function(d) {
                return d === 'percentage' ? '<span class="badge bg-info-subtle text-info border border-info-subtle">Persentase</span>'
                                          : '<span class="badge bg-success-subtle text-success border border-success-subtle">Nominal</span>';
            }},
            { data: 'discount_value' },
            { data: 'min_package_price', defaultContent: '—' },
            { data: 'validity', orderable: false },
            { data: 'is_active', orderable: false, className: 'text-center' },
            { data: 'action', orderable: false, searchable: false, className: 'text-center' },
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json' }
    });

    /* ---- Update unit diskon saat type berubah ---- */
    $(document).on('change', '#promo-type', function () {
        $('#discount-unit').text($(this).val() === 'percentage' ? '%' : 'Rp');
    });

    function resetModal() {
        $('#promo-id').val('');
        $('#promo-name, #promo-code, #promo-value, #promo-min, #promo-from, #promo-until, #promo-desc').val('');
        $('#promo-type').val('percentage');
        $('#discount-unit').text('%');
        $('#promo-active').prop('checked', true);
        $('#promo-name, #promo-code, #promo-value').removeClass('is-invalid');
        $('#err-name, #err-code, #err-value, #err-until').text('');
    }

    $('#btn-add').on('click', function () {
        resetModal();
        $('#modal-title').text('Tambah Promo');
        $('#modal-promo').modal('show');
    });

    $(document).on('click', '.btn-edit', function () {
        resetModal();
        var btn = $(this);
        $('#modal-title').text('Edit Promo');
        $('#promo-id').val(btn.data('id'));
        $('#promo-name').val(btn.data('name'));
        $('#promo-code').val(btn.data('code'));
        $('#promo-type').val(btn.data('type')).trigger('change');
        $('#promo-value').val(btn.data('value'));
        $('#promo-min').val(btn.data('min'));
        $('#promo-active').prop('checked', btn.data('active') == 1);
        $('#promo-from').val(btn.data('from'));
        $('#promo-until').val(btn.data('until'));
        $('#promo-desc').val(btn.data('desc'));
        $('#modal-promo').modal('show');
    });

    $('#btn-save').on('click', function () {
        var id   = $('#promo-id').val();
        var url  = id ? '/admin/promos/' + id : '{{ route("admin.promos.store") }}';
        var type = id ? 'PUT' : 'POST';

        $.ajax({
            url: url, type: type,
            data: {
                name:              $('#promo-name').val(),
                code:              $('#promo-code').val(),
                discount_type:     $('#promo-type').val(),
                discount_value:    $('#promo-value').val(),
                min_package_price: $('#promo-min').val() || null,
                is_active:         $('#promo-active').is(':checked') ? 1 : 0,
                valid_from:        $('#promo-from').val()  || null,
                valid_until:       $('#promo-until').val() || null,
                description:       $('#promo-desc').val(),
                _token:            '{{ csrf_token() }}'
            },
            success: function (res) {
                $('#modal-promo').modal('hide');
                table.ajax.reload();
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false });
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    var err = xhr.responseJSON.errors ?? {};
                    if (err.name)           { $('#promo-name').addClass('is-invalid');  $('#err-name').text(err.name[0]); }
                    if (err.code)           { $('#promo-code').addClass('is-invalid');  $('#err-code').text(err.code[0]); }
                    if (err.discount_value) { $('#promo-value').addClass('is-invalid'); $('#err-value').text(err.discount_value[0]); }
                    if (err.valid_until)    { $('#err-until').text(err.valid_until[0]); }
                } else {
                    Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan.', 'error');
                }
            }
        });
    });

    $(document).on('click', '.btn-delete', function () {
        var id = $(this).data('id'), name = $(this).data('name');
        Swal.fire({
            title: 'Hapus Promo?', text: '"' + name + '" akan dihapus permanen.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal'
        }).then(function (r) {
            if (r.isConfirmed) {
                $.ajax({
                    url: '/admin/promos/' + id, type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        table.ajax.reload();
                        Swal.fire({ icon: 'success', title: 'Dihapus', text: res.message, timer: 2000, showConfirmButton: false });
                    }
                });
            }
        });
    });
});
</script>
@endpush
