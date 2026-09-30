@extends('admin.layouts.app')

@section('title', 'Pola Jadwal - LIVO Admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center p-4 flex-wrap gap-2">
    <div>
        <h2 class="page-title">Pola Jadwal Mingguan</h2>
        <p class="text-muted mb-0 small">Template hari, sesi, mata pelajaran &amp; group siswa yang dipakai berulang tiap minggu.</p>
    </div>
    <button class="btn btn-primary" id="btn-add-pattern"><i class="bi bi-plus-lg me-1"></i> Pola Baru</button>
</div>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label fw-semibold small mb-1">Pola</label>
                <select id="pattern-select" class="form-select">
                    @forelse($patterns as $p)
                        <option value="{{ $p->id }}" {{ $pattern && $pattern->id === $p->id ? 'selected' : '' }}>
                            {{ $p->name }}{{ $p->is_active ? ' (aktif)' : '' }}
                        </option>
                    @empty
                        <option value="">-- Belum ada pola --</option>
                    @endforelse
                </select>
            </div>
            @if($pattern)
            <div class="col-md-auto d-flex gap-2">
                <button class="btn btn-outline-warning" id="btn-edit-pattern"
                    data-id="{{ $pattern->id }}" data-name="{{ $pattern->name }}"
                    data-notes="{{ $pattern->notes }}" data-active="{{ $pattern->is_active ? 1 : 0 }}">
                    <i class="bi bi-pencil me-1"></i> Edit
                </button>
                <button class="btn btn-outline-danger" id="btn-delete-pattern" data-id="{{ $pattern->id }}" data-name="{{ $pattern->name }}">
                    <i class="bi bi-trash me-1"></i> Hapus
                </button>
                <button class="btn btn-success" id="btn-add-item"><i class="bi bi-plus-lg me-1"></i> Tambah Baris</button>
            </div>
            @endif
            @if($pattern?->notes)
            <div class="col-12"><span class="text-muted small"><i class="bi bi-info-circle me-1"></i>{{ $pattern->notes }}</span></div>
            @endif
        </div>
    </div>
</div>

@if(!$pattern)
    <div class="alert alert-info"><i class="bi bi-info-circle me-1"></i> Belum ada pola jadwal. Klik <strong>Pola Baru</strong> untuk membuatnya.</div>
@else
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white px-4 py-3">
        <h4 class="mb-0 h5">{{ $pattern->name }} <span class="text-muted fw-normal small">— {{ $items->count() }} baris</span></h4>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-vcenter mb-0 text-center align-middle">
            <thead class="table-light">
                <tr>
                    <th class="text-start" style="min-width:150px">Sesi</th>
                    @foreach($hariList as $h)<th style="min-width:150px">{{ $h }}</th>@endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($sessions as $session)
                    <tr>
                        <td class="text-start">
                            <div class="fw-semibold">{{ $session->name }}</div>
                            <small class="text-muted">{{ substr($session->time_start,0,5) }}–{{ substr($session->time_end,0,5) }}</small>
                        </td>
                        @foreach($hariList as $h)
                            @php $cell = $matrix[$session->id][$h] ?? collect(); @endphp
                            <td>
                                @forelse($cell as $it)
                                    <div class="d-inline-flex align-items-center gap-1 bg-primary-subtle text-primary rounded px-2 py-1 mb-1 small">
                                        <span><strong>{{ $it->subject->subject_name ?? '-' }}</strong><br>{{ $it->studentGroup->name ?? '-' }}</span>
                                        <button class="btn btn-sm btn-link text-danger p-0 ms-1 btn-del-item"
                                            data-id="{{ $it->id }}" title="Hapus baris"><i class="bi bi-x-lg"></i></button>
                                    </div>
                                @empty
                                    <span class="text-muted">–</span>
                                @endforelse
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($hariList) + 1 }}" class="text-muted py-3">Belum ada Sesi Pembelajaran yang terdaftar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Modal Pola --}}
<div class="modal fade" id="modal-pattern" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="modal-pattern-title">Pola Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" id="pattern-id">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Pola <span class="text-danger">*</span></label>
                    <input type="text" id="field-pattern-name" class="form-control" placeholder="cth: Pola Reguler Semester Ganjil">
                    <div class="invalid-feedback" id="err-pattern-name"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Catatan</label>
                    <textarea id="field-pattern-notes" class="form-control" rows="2"></textarea>
                </div>
                <label class="form-check">
                    <input type="checkbox" class="form-check-input no-select2" id="field-pattern-active" checked>
                    <span class="form-check-label">Pola aktif</span>
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btn-save-pattern">Simpan</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Baris Pola --}}
<div class="modal fade" id="modal-item" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Tambah Baris Pola</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Group Siswa <span class="text-danger">*</span></label>
                    <select id="field-group" class="form-select">
                        <option value="">-- Pilih Group --</option>
                        @foreach($groups as $g)
                            <option value="{{ $g->id }}" data-hari="{{ $g->hari }}" data-session="{{ $g->session_id }}">
                                {{ $g->name }} — {{ $g->session->name ?? '-' }} · {{ $g->hari }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Hari &amp; sesi otomatis terisi dari group, masih bisa diubah.</div>
                    <div class="invalid-feedback" id="err-group"></div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Hari <span class="text-danger">*</span></label>
                        <select id="field-hari" class="form-select">
                            <option value="">-- Pilih Hari --</option>
                            @foreach($hariList as $h)<option value="{{ $h }}">{{ $h }}</option>@endforeach
                        </select>
                        <div class="invalid-feedback" id="err-hari"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Sesi <span class="text-danger">*</span></label>
                        <select id="field-session" class="form-select">
                            <option value="">-- Pilih Sesi --</option>
                            @foreach($sessions as $s)
                                <option value="{{ $s->id }}">{{ $s->name }} ({{ substr($s->time_start,0,5) }}–{{ substr($s->time_end,0,5) }})</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="err-session"></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Mata Pelajaran <span class="text-danger">*</span></label>
                        <select id="field-subject" class="form-select">
                            <option value="">-- Pilih Mapel --</option>
                            @foreach($subjects as $s)<option value="{{ $s->id }}">{{ $s->subject_name }}</option>@endforeach
                        </select>
                        <div class="invalid-feedback" id="err-subject"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btn-save-item">Tambah</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    var patternId = {{ $pattern->id ?? 'null' }};

    $('#pattern-select').on('change', function () {
        if ($(this).val()) window.location = '{{ route('admin.schedule-patterns.index') }}?pattern=' + $(this).val();
    });

    /* ── Pola ── */
    function resetPatternModal() {
        $('#pattern-id, #field-pattern-name, #field-pattern-notes').val('');
        $('#field-pattern-active').prop('checked', true);
        $('.form-control, .form-select').removeClass('is-invalid');
        $('#err-pattern-name').text('');
    }

    $('#btn-add-pattern').on('click', function () {
        resetPatternModal();
        $('#modal-pattern-title').text('Pola Baru');
        $('#modal-pattern').modal('show');
    });

    $('#btn-edit-pattern').on('click', function () {
        resetPatternModal();
        var b = $(this);
        $('#modal-pattern-title').text('Edit Pola');
        $('#pattern-id').val(b.data('id'));
        $('#field-pattern-name').val(b.data('name'));
        $('#field-pattern-notes').val(b.data('notes'));
        $('#field-pattern-active').prop('checked', String(b.data('active')) === '1');
        $('#modal-pattern').modal('show');
    });

    $('#btn-save-pattern').on('click', function () {
        var id = $('#pattern-id').val();
        $.ajax({
            url: id ? '/admin/schedule-patterns/' + id : '{{ route('admin.schedule-patterns.store') }}',
            type: id ? 'PUT' : 'POST',
            data: {
                name: $('#field-pattern-name').val(),
                notes: $('#field-pattern-notes').val(),
                is_active: $('#field-pattern-active').is(':checked') ? 1 : 0,
                _token: '{{ csrf_token() }}'
            },
            success: function (res) {
                $('#modal-pattern').modal('hide');
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 1600, showConfirmButton: false })
                    .then(function () {
                        window.location = '{{ route('admin.schedule-patterns.index') }}?pattern=' + (res.id || id);
                    });
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    var err = xhr.responseJSON.errors ?? {};
                    if (err.name) { $('#field-pattern-name').addClass('is-invalid'); $('#err-pattern-name').text(err.name[0]); }
                } else { Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan.', 'error'); }
            }
        });
    });

    $('#btn-delete-pattern').on('click', function () {
        var id = $(this).data('id'), name = $(this).data('name');
        Swal.fire({
            title: 'Hapus Pola?',
            html: '"' + name + '" akan dihapus.<br><small class="text-muted">Slot yang sudah disiapkan minggu-minggu lalu tetap tersimpan.</small>',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url: '/admin/schedule-patterns/' + id, type: 'DELETE', data: { _token: '{{ csrf_token() }}' },
                success: function (res) {
                    Swal.fire({ icon: 'success', title: 'Dihapus', text: res.message, timer: 1600, showConfirmButton: false })
                        .then(function () { window.location = '{{ route('admin.schedule-patterns.index') }}'; });
                },
                error: function (xhr) { Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan.', 'error'); }
            });
        });
    });

    /* ── Baris pola ── */
    $('#btn-add-item').on('click', function () {
        $('#field-group, #field-hari, #field-session, #field-subject').val('').trigger('change.select2');
        $('.form-select').removeClass('is-invalid');
        $('#err-group, #err-hari, #err-session, #err-subject').text('');
        $('#modal-item').modal('show');
    });

    // Pilih group → hari & sesi ikut terisi
    $('#field-group').on('change', function () {
        var opt = $(this).find('option:selected');
        if (opt.data('hari')) $('#field-hari').val(opt.data('hari')).trigger('change.select2');
        if (opt.data('session')) $('#field-session').val(String(opt.data('session'))).trigger('change.select2');
    });

    $('#btn-save-item').on('click', function () {
        $('.form-select').removeClass('is-invalid');
        $.ajax({
            url: '/admin/schedule-patterns/' + patternId + '/items', type: 'POST',
            data: {
                hari: $('#field-hari').val(),
                session_id: $('#field-session').val(),
                subject_id: $('#field-subject').val(),
                student_group_id: $('#field-group').val(),
                _token: '{{ csrf_token() }}'
            },
            success: function (res) {
                $('#modal-item').modal('hide');
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 1400, showConfirmButton: false })
                    .then(function () { location.reload(); });
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    var err = xhr.responseJSON.errors ?? {};
                    if (err.hari) { $('#field-hari').addClass('is-invalid'); $('#err-hari').text(err.hari[0]); }
                    if (err.session_id) { $('#field-session').addClass('is-invalid'); $('#err-session').text(err.session_id[0]); }
                    if (err.subject_id) { $('#field-subject').addClass('is-invalid'); $('#err-subject').text(err.subject_id[0]); }
                    if (err.student_group_id) { $('#field-group').addClass('is-invalid'); $('#err-group').text(err.student_group_id[0]); }
                    if (!Object.keys(err).length) Swal.fire('Gagal', xhr.responseJSON.message, 'error');
                } else { Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan.', 'error'); }
            }
        });
    });

    $(document).on('click', '.btn-del-item', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Hapus baris pola?', icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url: '/admin/schedule-pattern-items/' + id, type: 'DELETE', data: { _token: '{{ csrf_token() }}' },
                success: function () { location.reload(); },
                error: function (xhr) { Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan.', 'error'); }
            });
        });
    });
});
</script>
@endpush
