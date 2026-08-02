@extends('admin.layouts.app')

@section('title', 'Fee Tutor - LIVO Admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center p-4 flex-wrap gap-2">
    <div>
        <h2 class="page-title">Fee Tutor</h2>
        <p class="text-muted mb-0 small">Pilih bulan → Generate untuk menghitung fee seluruh tutor → review → Terbitkan agar tutor dapat melihat.</p>
    </div>
</div>
@endsection

@section('content')
@php
    $statusMap = [
        'draft'     => ['bg-warning text-dark', 'Draft (belum terbit)'],
        'published' => ['bg-success', 'Sudah Diterbitkan'],
    ];
    [$statusClass, $statusText] = $statusMap[$period->status ?? 'none'] ?? ['bg-secondary', 'Belum Digenerate'];
@endphp

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Bulan</label>
                <input type="month" id="field-month" class="form-control" value="{{ $month->format('Y-m') }}">
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-outline-primary w-100" id="btn-load">
                    <i class="bi bi-arrow-repeat me-1"></i> Tampilkan
                </button>
            </div>
            <div class="col-md-6 text-md-end">
                <span class="badge {{ $statusClass }} fs-6 px-3 py-2 me-2" id="period-status">{{ $statusText }}</span>
            </div>
        </div>

        @if($period)
            <div class="text-muted small mt-3">
                Digenerate: {{ $period->generated_at?->translatedFormat('d M Y H:i') ?? '-' }} oleh {{ $period->generatedBy?->name ?? '-' }}
                @if($period->isPublished())
                    <br>Diterbitkan: {{ $period->published_at?->translatedFormat('d M Y H:i') }} oleh {{ $period->publishedBy?->name ?? '-' }}
                @endif
            </div>
        @endif

        <div class="d-flex gap-2 mt-3 flex-wrap" id="action-buttons">
            <button type="button" class="btn btn-primary" id="btn-generate">
                <i class="bi bi-calculator me-1"></i> Generate / Hitung Ulang
            </button>
            <button type="button" class="btn btn-success" id="btn-publish" {{ (!$period || $period->tutorFees()->count() === 0 || $period->isPublished()) ? 'disabled' : '' }}>
                <i class="bi bi-send-check me-1"></i> Terbitkan
            </button>
            <button type="button" class="btn btn-outline-danger" id="btn-unpublish" {{ (!$period || !$period->isPublished()) ? 'disabled' : '' }}>
                <i class="bi bi-arrow-counterclockwise me-1"></i> Batalkan Penerbitan
            </button>
        </div>

        @if($period && $period->isPublished())
            <div class="alert alert-success mt-3 mb-0 small">
                <i class="bi bi-check-circle me-1"></i>
                Fee bulan ini sudah diterbitkan dan dapat dilihat tutor. Generate ulang tidak diperbolehkan sebelum penerbitan dibatalkan.
            </div>
        @endif
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0">Review Fee per Tutor — <span id="header-month">{{ $month->locale('id')->translatedFormat('F Y') }}</span></h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tutor-fees-table" class="table table-vcenter table-hover w-100">
                <thead class="table-light">
                    <tr>
                        <th width="40">#</th>
                        <th>Tutor</th>
                        <th class="text-center">Sesi Semi-Privat (b)</th>
                        <th class="text-center">Sesi Privat (a)</th>
                        <th class="text-center">Total Siswa (c)</th>
                        <th class="text-center">Transport (d)</th>
                        <th class="text-end">Total Fee</th>
                        <th class="text-center" width="70">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
        <p class="text-muted small mt-2 mb-0">
            <i class="bi bi-info-circle me-1"></i> Fee dapat diedit manual selama periode masih berstatus draft (belum diterbitkan).
        </p>
    </div>
</div>

{{-- Modal Edit Fee Tutor --}}
<div class="modal fade" id="modal-edit-fee" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Fee — <span id="edit-fee-tutor-name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit-fee-id">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Jumlah Sesi Semi-Privat (b)</label>
                        <input type="number" min="0" id="edit-session-count" class="form-control fee-count" data-target="edit-fee-session">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Fee Sesi Semi-Privat (Rp)</label>
                        <input type="number" min="0" id="edit-fee-session" class="form-control fee-amount">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Jumlah Sesi Privat (a)</label>
                        <input type="number" min="0" id="edit-private-count" class="form-control fee-count" data-target="edit-fee-private">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Fee Sesi Privat (Rp)</label>
                        <input type="number" min="0" id="edit-fee-private" class="form-control fee-amount">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Total Siswa (c)</label>
                        <input type="number" min="0" id="edit-regular-count" class="form-control fee-count" data-target="edit-fee-regular">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Fee Total Siswa (Rp)</label>
                        <input type="number" min="0" id="edit-fee-regular" class="form-control fee-amount">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Hari Transport (d)</label>
                        <input type="number" min="0" id="edit-day-count" class="form-control fee-count" data-target="edit-fee-transport">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Fee Transport (Rp)</label>
                        <input type="number" min="0" id="edit-fee-transport" class="form-control fee-amount">
                    </div>
                    <div class="col-12">
                        <hr>
                        <label class="form-label fw-semibold">Total Fee (Rp)</label>
                        <input type="number" min="0" id="edit-total" class="form-control fw-bold">
                        <small class="text-muted">Otomatis dijumlahkan dari komponen di atas; dapat diubah manual bila perlu.</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btn-save-fee">Simpan</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    var currentMonth = $('#field-month').val();

    var table = $('#tutor-fees-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: "{{ route('admin.tutor-fees.data') }}", data: function (d) { d.month = currentMonth; } },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'tutor_name' },
            { data: 'session', orderable: false, className: 'text-center' },
            { data: 'private', orderable: false, className: 'text-center' },
            { data: 'regular', orderable: false, className: 'text-center' },
            { data: 'transport', orderable: false, className: 'text-center' },
            { data: 'total', orderable: false, className: 'text-end' },
            { data: 'action', orderable: false, searchable: false, className: 'text-center' },
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json', emptyTable: 'Belum ada data fee untuk bulan ini. Klik "Generate / Hitung Ulang".' }
    });

    function reload() {
        currentMonth = $('#field-month').val();
        window.location = '{{ route('admin.tutor-fees.index') }}?month=' + currentMonth;
    }

    $('#btn-load').on('click', reload);

    /* ── Edit manual fee per tutor (hanya saat periode masih draft) ── */
    function recalcTotal() {
        var total = 0;
        $('.fee-amount').each(function () { total += parseFloat($(this).val()) || 0; });
        $('#edit-total').val(total);
    }

    // Ubah jumlah (count) → tidak tahu tarif per unit, jadi hanya total yang dihitung
    // ulang dari nilai fee saat ini; admin tetap bisa mengubah nominal fee secara manual.
    $(document).on('input', '.fee-amount', recalcTotal);

    $(document).on('click', '.btn-edit-fee', function () {
        var b = $(this);
        $('#edit-fee-id').val(b.data('id'));
        $('#edit-fee-tutor-name').text(b.data('name'));
        $('#edit-session-count').val(b.data('session-count'));
        $('#edit-fee-session').val(b.data('fee-session'));
        $('#edit-private-count').val(b.data('private-count'));
        $('#edit-fee-private').val(b.data('fee-private'));
        $('#edit-regular-count').val(b.data('regular-count'));
        $('#edit-fee-regular').val(b.data('fee-regular'));
        $('#edit-day-count').val(b.data('day-count'));
        $('#edit-fee-transport').val(b.data('fee-transport'));
        $('#edit-total').val(b.data('total'));
        $('#modal-edit-fee').modal('show');
    });

    $('#btn-save-fee').on('click', function () {
        var id = $('#edit-fee-id').val();
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '/admin/tutor-fees/' + id, type: 'POST',
            data: {
                _method: 'PUT',
                _token: '{{ csrf_token() }}',
                session_count: $('#edit-session-count').val(),
                fee_session: $('#edit-fee-session').val(),
                private_count: $('#edit-private-count').val(),
                fee_private: $('#edit-fee-private').val(),
                regular_count: $('#edit-regular-count').val(),
                fee_regular: $('#edit-fee-regular').val(),
                day_count: $('#edit-day-count').val(),
                fee_transport: $('#edit-fee-transport').val(),
                total: $('#edit-total').val(),
            },
            success: function (res) {
                $('#modal-edit-fee').modal('hide');
                table.ajax.reload(null, false);
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false });
            },
            error: function (xhr) { Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan.', 'error'); },
            complete: function () { $btn.prop('disabled', false); }
        });
    });

    $('#btn-generate').on('click', function () {
        var $btn = $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menghitung...');
        $.ajax({
            url: '{{ route("admin.tutor-fees.generate") }}', type: 'POST',
            data: { month: $('#field-month').val(), _token: '{{ csrf_token() }}' },
            success: function (res) {
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2500, showConfirmButton: false })
                    .then(function () { reload(); });
            },
            error: function (xhr) { Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan.', 'error'); },
            complete: function () { $btn.prop('disabled', false).html('<i class="bi bi-calculator me-1"></i> Generate / Hitung Ulang'); }
        });
    });

    $('#btn-publish').on('click', function () {
        Swal.fire({
            title: 'Terbitkan Fee Tutor?',
            text: 'Setelah diterbitkan, seluruh tutor dapat melihat fee bulan ini.',
            icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, terbitkan', cancelButtonText: 'Batal'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url: '{{ route("admin.tutor-fees.publish") }}', type: 'POST',
                data: { month: $('#field-month').val(), _token: '{{ csrf_token() }}' },
                success: function (res) {
                    Swal.fire({ icon: 'success', title: 'Diterbitkan', text: res.message, timer: 2500, showConfirmButton: false })
                        .then(function () { reload(); });
                },
                error: function (xhr) { Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan.', 'error'); }
            });
        });
    });

    $('#btn-unpublish').on('click', function () {
        Swal.fire({
            title: 'Batalkan Penerbitan?',
            text: 'Tutor tidak akan bisa melihat fee bulan ini sampai diterbitkan kembali.',
            icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, batalkan', cancelButtonText: 'Tutup', confirmButtonColor: '#d33'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url: '{{ route("admin.tutor-fees.unpublish") }}', type: 'POST',
                data: { month: $('#field-month').val(), _token: '{{ csrf_token() }}' },
                success: function (res) {
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2500, showConfirmButton: false })
                        .then(function () { reload(); });
                },
                error: function (xhr) { Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan.', 'error'); }
            });
        });
    });
});
</script>
@endpush
