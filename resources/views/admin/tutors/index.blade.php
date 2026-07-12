@extends('admin.layouts.app')

@section('title', 'Master Tutor - LIVO Admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center p-4">
    <div>
        <h2 class="page-title">Master Tutor</h2>
        <p class="text-muted mb-0 small">Kelola data pengajar / tutor</p>
    </div>
    <button class="btn btn-primary" id="btn-add">
        <i class="bi bi-plus-lg me-1"></i> Tambah Tutor
    </button>
</div>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <table id="tutors-table" class="table table-hover align-middle w-100">
            <thead class="table-light">
                <tr>
                    <th width="50">#</th>
                    <th width="60">Foto</th>
                    <th>Nama Tutor</th>
                    <th>No. HP</th>
                    <th>Email</th>
                    <th>No. Rekening</th>
                    <th>Spesialisasi</th>
                    <th width="100" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

{{-- Modal Tutor --}}
<div class="modal fade" id="modal-tutor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-title">Tambah Tutor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="tutor-id">
                <div class="row g-3">
                    <div class="col-md-4 text-center">
                        <label class="form-label fw-semibold d-block">Foto</label>
                        <img id="photo-preview" src="" alt="Foto Tutor" class="rounded mb-2 d-none" style="width:120px;height:120px;object-fit:cover;">
                        <div id="photo-placeholder" class="rounded bg-light d-flex align-items-center justify-content-center mx-auto mb-2" style="width:120px;height:120px;">
                            <i class="bi bi-person text-muted" style="font-size:2.5rem;"></i>
                        </div>
                        <input type="file" id="field-photo" class="form-control form-control-sm" accept="image/*">
                        <small class="text-muted d-block mt-1">Semua tipe foto, maks 5 MB.</small>
                        <div class="invalid-feedback" id="err-photo"></div>
                    </div>
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" id="field-name" class="form-control" placeholder="cth: Budi Santoso">
                            <div class="invalid-feedback" id="err-name"></div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">No. HP / WhatsApp <span class="text-danger">*</span></label>
                                <input type="text" id="field-phone" class="form-control" placeholder="cth: 08123456789">
                                <div class="invalid-feedback" id="err-phone"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="email" id="field-email" class="form-control" placeholder="cth: budi@email.com">
                                <div class="invalid-feedback" id="err-email"></div>
                            </div>
                        </div>
                        <div class="mt-3 row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">No. Rekening</label>
                                <input type="text" id="field-norek" class="form-control" placeholder="cth: 1234567890 (BCA a.n. Budi)">
                                <div class="invalid-feedback" id="err-norek"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Fee per Sesi (Rp)</label>
                                <input type="number" id="field-fee" min="0" class="form-control" placeholder="cth: 75000">
                                <div class="invalid-feedback" id="err-fee"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Spesialisasi <span class="text-danger">*</span></label>
                        <select id="field-specialization" class="form-select" multiple size="5">
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->subject_name }}">{{ $subject->subject_name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Tahan <kbd>Ctrl</kbd> (atau <kbd>Cmd</kbd>) untuk memilih lebih dari satu mata pelajaran.</div>
                        <div class="invalid-feedback d-block" id="err-specialization"></div>
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
    var table = $('#tutors-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.tutors.data') }}",
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'photo_thumb', orderable: false, searchable: false, className: 'text-center' },
            { data: 'name' },
            { data: 'phone' },
            { data: 'email', defaultContent: '-' },
            { data: 'no_rekening', defaultContent: '-' },
            { data: 'specialization', orderable: false },
            { data: 'action', orderable: false, searchable: false, className: 'text-center' },
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json' }
    });

    function showPhoto(url) {
        if (url) {
            $('#photo-preview').attr('src', url).removeClass('d-none');
            $('#photo-placeholder').addClass('d-none');
        } else {
            $('#photo-preview').attr('src', '').addClass('d-none');
            $('#photo-placeholder').removeClass('d-none');
        }
    }

    function resetModal() {
        $('#tutor-id, #field-name, #field-phone, #field-email, #field-norek, #field-fee, #field-photo').val('');
        $('#field-specialization').val([]);
        $('.form-control, .form-select').removeClass('is-invalid');
        $('#err-name, #err-phone, #err-email, #err-norek, #err-fee, #err-photo, #err-specialization').text('');
        showPhoto('');
    }

    $('#field-photo').on('change', function (e) {
        var file = e.target.files[0];
        if (file) showPhoto(URL.createObjectURL(file));
    });

    $('#btn-add').on('click', function () {
        resetModal();
        $('#modal-title').text('Tambah Tutor');
        $('#modal-tutor').modal('show');
    });

    $(document).on('click', '.btn-edit', function () {
        resetModal();
        $('#modal-title').text('Edit Tutor');
        var btn = $(this);
        $('#tutor-id').val(btn.data('id'));
        $('#field-name').val(btn.data('name'));
        $('#field-phone').val(btn.data('phone'));
        $('#field-email').val(btn.data('email'));
        $('#field-norek').val(btn.data('norek'));
        $('#field-fee').val(btn.data('fee'));
        var specs = btn.data('specialization') || [];
        $('#field-specialization').val(specs);
        showPhoto(btn.data('photo'));
        $('#modal-tutor').modal('show');
    });

    $('#btn-save').on('click', function () {
        var id   = $('#tutor-id').val();
        var url  = id ? '/admin/tutors/' + id : '{{ route("admin.tutors.store") }}';

        var fd = new FormData();
        fd.append('name', $('#field-name').val());
        fd.append('phone', $('#field-phone').val());
        fd.append('email', $('#field-email').val());
        fd.append('no_rekening', $('#field-norek').val());
        fd.append('fee_per_session', $('#field-fee').val());
        ($('#field-specialization').val() || []).forEach(function (s) {
            fd.append('specialization[]', s);
        });
        if ($('#field-photo')[0].files[0]) {
            fd.append('photo', $('#field-photo')[0].files[0]);
        }
        fd.append('_token', '{{ csrf_token() }}');
        if (id) fd.append('_method', 'PUT');

        $.ajax({
            url: url, type: 'POST', data: fd, processData: false, contentType: false,
            success: function (res) {
                $('#modal-tutor').modal('hide');
                table.ajax.reload();
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false });
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    var err = xhr.responseJSON.errors ?? {};
                    if (err.name)           { $('#field-name').addClass('is-invalid');  $('#err-name').text(err.name[0]); }
                    if (err.phone)          { $('#field-phone').addClass('is-invalid'); $('#err-phone').text(err.phone[0]); }
                    if (err.email)          { $('#field-email').addClass('is-invalid'); $('#err-email').text(err.email[0]); }
                    if (err.no_rekening)    { $('#field-norek').addClass('is-invalid'); $('#err-norek').text(err.no_rekening[0]); }
                    if (err.fee_per_session){ $('#field-fee').addClass('is-invalid');   $('#err-fee').text(err.fee_per_session[0]); }
                    if (err.photo)          { $('#field-photo').addClass('is-invalid'); $('#err-photo').text(err.photo[0]); }
                    if (err.specialization) { $('#field-specialization').addClass('is-invalid'); $('#err-specialization').text(err.specialization[0]); }
                } else {
                    Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan.', 'error');
                }
            }
        });
    });

    $(document).on('click', '.btn-delete', function () {
        var id = $(this).data('id'), name = $(this).data('name');
        Swal.fire({
            title: 'Hapus Tutor?',
            text: '"' + name + '" akan dihapus permanen.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal'
        }).then(function (result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/admin/tutors/' + id, type: 'DELETE',
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
