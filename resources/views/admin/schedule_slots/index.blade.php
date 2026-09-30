@extends('admin.layouts.app')

@section('title', 'Siapkan Jadwal - LIVO Admin')

@section('page-header')
<div class="d-flex justify-content-between align-items-center p-4 flex-wrap gap-2">
    <div>
        <h2 class="page-title">Siapkan Jadwal Mingguan</h2>
        <p class="text-muted mb-0 small">
            Slot yang tutornya sudah ada dari minggu sebelumnya tinggal dikonfirmasi tutor;
            slot baru dibuka untuk dilamar, lalu Anda yang menetapkan.
        </p>
    </div>
    <button class="btn btn-primary" id="btn-prepare"><i class="bi bi-magic me-1"></i> Siapkan Jadwal Minggu Ini</button>
</div>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0 h5">{{ $weekStart->translatedFormat('d M Y') }} — {{ $weekEnd->translatedFormat('d M Y') }}</h4>
        <div class="btn-group">
            <a href="{{ route('admin.schedule-slots.index', ['week' => $prevWeek]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-left"></i> Minggu Lalu</a>
            <a href="{{ route('admin.schedule-slots.index') }}" class="btn btn-outline-primary btn-sm">Minggu Ini</a>
            <a href="{{ route('admin.schedule-slots.index', ['week' => $nextWeek]) }}" class="btn btn-outline-secondary btn-sm">Minggu Depan <i class="bi bi-chevron-right"></i></a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    @foreach([
        ['Total Jadwal', $ringkasan['total'], 'bg-secondary-subtle', 'text-secondary'],
        ['Tersedia', $ringkasan['open'], 'bg-primary-subtle', 'text-primary'],
        ['Menunggu Konfirmasi', $ringkasan['konfirm'], 'bg-warning-subtle', 'text-warning'],
        ['Complete', $ringkasan['terisi'], 'bg-success-subtle', 'text-success'],
    ] as [$label, $nilai, $bg, $fg])
        <div class="col-lg-3 col-sm-6 col-12">
            <div class="card p-3 {{ $bg }} border-0 rounded-3 h-100">
                <div class="subheader {{ $fg }} mb-1">{{ $label }}</div>
                <div class="h2 fw-bold mb-0">{{ number_format($nilai) }}</div>
            </div>
        </div>
    @endforeach
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white px-4 py-3"><h4 class="mb-0 h5">Daftar Slot</h4></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Hari / Tanggal</th>
                    <th>Sesi</th>
                    <th>Mata Pelajaran</th>
                    <th>Group Siswa</th>
                    <th>Status</th>
                    <th>Tutor</th>
                    <th class="text-center">Pelamar</th>
                    <th class="text-end" style="min-width:210px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($slots as $slot)
                    @php
                        $badge = match($slot->status) {
                            'open' => 'bg-primary',
                            'pending_confirmation' => 'bg-warning text-dark',
                            'assigned' => 'bg-success',
                            'vacant' => 'bg-danger',
                            default => 'bg-secondary',
                        };
                        $pelamar = $slot->applications->whereIn('status', ['pending','accepted'])->count();
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $slot->hari }}</div>
                            <small class="text-muted">{{ $slot->class_date->translatedFormat('d M Y') }}</small>
                        </td>
                        <td>
                            {{ $slot->session->name ?? '-' }}
                            <br><small class="text-muted">{{ substr($slot->session->time_start ?? '', 0, 5) }}–{{ substr($slot->session->time_end ?? '', 0, 5) }}</small>
                        </td>
                        <td>{{ $slot->subject->subject_name ?? '-' }}</td>
                        <td>{{ $slot->studentGroup->name ?? '-' }}</td>
                        <td>
                            <span class="badge {{ $badge }}">{{ $slot->statusLabel() }}</span>
                            @if($slot->carried_from_slot_id)
                                <br><small class="text-muted"><i class="bi bi-arrow-repeat me-1"></i>bawaan minggu lalu</small>
                            @endif
                        </td>
                        <td>
                            @if($slot->status === 'vacant')
                                <span class="text-danger">{{ $slot->assignedTutor->name ?? '-' }} (izin)</span>
                            @elseif($slot->filled_by_tutor_id)
                                {{ $slot->filledByTutor->name }}
                                <br><small class="text-muted">pengganti {{ $slot->assignedTutor->name ?? '-' }}</small>
                            @elseif($slot->assignedTutor)
                                {{ $slot->assignedTutor->name }}
                                @if($slot->confirmed_at)<br><small class="text-success">dikonfirmasi tutor</small>@endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($pelamar > 0)
                                <button class="btn btn-sm btn-outline-primary btn-applicants" data-id="{{ $slot->id }}">
                                    <i class="bi bi-people me-1"></i>{{ $pelamar }}
                                </button>
                            @else
                                <span class="text-muted">0</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                @if($slot->status === 'assigned')
                                    <button class="btn btn-outline-danger btn-unassign" data-id="{{ $slot->id }}">Batalkan</button>
                                @else
                                    @if($pelamar > 0)
                                        <button class="btn btn-outline-success btn-applicants" data-id="{{ $slot->id }}">Tetapkan</button>
                                    @endif
                                    @if($slot->status === 'vacant')
                                        <button class="btn btn-outline-danger btn-pengganti" data-id="{{ $slot->id }}">Pilih Pengganti</button>
                                    @endif
                                    @if($slot->status === 'closed')
                                        <button class="btn btn-outline-primary btn-reopen" data-id="{{ $slot->id }}">Buka</button>
                                    @else
                                        <button class="btn btn-outline-secondary btn-close-slot" data-id="{{ $slot->id }}">Tutup</button>
                                    @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">
                        Belum ada slot untuk minggu ini. Klik <strong>Siapkan Jadwal</strong> di atas.
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal: siapkan jadwal --}}
<div class="modal fade" id="modal-prepare" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-magic me-2 text-primary"></i>Siapkan Jadwal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="alert alert-info border-0 bg-info bg-opacity-10 py-2 px-3 small">
                    <i class="bi bi-info-circle me-1"></i>
                    Baris pola yang minggu sebelumnya sudah ada tutornya akan dibawa ke minggu ini
                    (menunggu konfirmasi tutor). Sisanya dibuka untuk dilamar.
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Pola Jadwal <span class="text-danger">*</span></label>
                    <select id="prep-pattern" class="form-select">
                        @forelse($patterns as $p)
                            <option value="{{ $p->id }}" {{ $p->is_active ? 'selected' : '' }}>{{ $p->name }}{{ $p->is_active ? ' (aktif)' : '' }}</option>
                        @empty
                            <option value="">-- Belum ada pola --</option>
                        @endforelse
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold">Minggu <span class="text-danger">*</span></label>
                    <input type="date" id="prep-week" class="form-control" value="{{ $weekStart->toDateString() }}">
                    <div class="form-text">Tanggal mana pun dalam minggu yang dituju.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btn-do-prepare">Siapkan</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: pelamar --}}
<div class="modal fade" id="modal-applicants" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-people me-2 text-primary"></i>Tutor yang Memilih Jadwal Ini</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="text-muted small mb-2" id="applicants-label">—</p>
                <p class="text-muted small mb-2"><i class="bi bi-info-circle me-1"></i>Diurutkan dari skor tertinggi: gabungan kecepatan mendaftar + rekam jejak kecepatan &amp; ketepatan mengisi evaluasi.</p>
                <div class="table-responsive">
                <table class="table table-sm table-striped align-middle mb-0">
                    <thead><tr>
                        <th style="width:36px">#</th><th>Tutor</th><th>Spesialisasi</th><th>Waktu Memilih</th>
                        <th>Kecepatan Evaluasi</th><th>Ketepatan Evaluasi</th><th>Skor</th>
                        <th>Status</th><th class="text-end">Aksi</th>
                    </tr></thead>
                    <tbody id="applicants-body"></tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal: pilih tutor pengganti untuk slot yang kosong karena izin --}}
<div class="modal fade" id="modal-pengganti" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-person-plus me-2 text-danger"></i>Pilih Tutor Pengganti</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="text-muted small mb-2">Tutor rutin izin untuk minggu ini. Pilih pengganti khusus minggu ini — jadwal rutin tetap milik tutor asal untuk minggu berikutnya.</p>
                <select id="pengganti-tutor" class="form-select">
                    <option value="">Memuat...</option>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="btn-do-pengganti">Tetapkan Pengganti</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    var token = '{{ csrf_token() }}';

    function gagal(xhr) { Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan.', 'error'); }
    function sukses(res) {
        Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2200, showConfirmButton: false })
            .then(function () { location.reload(); });
    }

    $('#btn-prepare').on('click', function () { $('#modal-prepare').modal('show'); });

    $('#btn-do-prepare').on('click', function () {
        var $b = $(this).prop('disabled', true).text('Menyiapkan...');
        $.ajax({
            url: '{{ route('admin.schedule-slots.prepare') }}', type: 'POST',
            data: { schedule_pattern_id: $('#prep-pattern').val(), week_date: $('#prep-week').val(), _token: token },
            success: function (res) { $('#modal-prepare').modal('hide'); sukses(res); },
            error: gagal,
            complete: function () { $b.prop('disabled', false).text('Siapkan'); }
        });
    });

    $(document).on('click', '.btn-applicants', function () {
        var id = $(this).data('id');
        var body = $('#applicants-body').html('<tr><td colspan="9" class="text-center text-muted py-3">Memuat...</td></tr>');
        $('#modal-applicants').modal('show');
        $.get('/admin/schedule-slots/' + id + '/applicants', function (data) {
            $('#applicants-label').text(data.slot.label + ' · ' + data.slot.mapel + ' · ' + data.slot.group);
            if (!data.applicants.length) {
                body.html('<tr><td colspan="9" class="text-center text-muted py-3">Belum ada tutor yang memilih jadwal ini.</td></tr>');
                return;
            }
            var html = '';
            data.applicants.forEach(function (a, i) {
                var badge = { pending: 'bg-warning text-dark', accepted: 'bg-success', rejected: 'bg-secondary', withdrawn: 'bg-light text-dark' }[a.status] || 'bg-light text-dark';
                var aksi = (a.status === 'pending' && data.slot.status !== 'assigned')
                    ? '<button class="btn btn-sm btn-success btn-assign" data-slot="' + id + '" data-tutor="' + a.tutor_id + '" data-name="' + a.tutor + '">Tetapkan</button>'
                    : '<span class="text-muted small">—</span>';
                html += '<tr' + (i === 0 ? ' class="table-success"' : '') + '><td>' + (i + 1) + '</td><td class="fw-semibold">' + a.tutor + '</td><td class="small text-muted">' + (a.spesialis || '-') +
                        '</td><td class="small">' + (a.applied_at || '-') + '</td>' +
                        '<td class="small">' + a.kecepatan_evaluasi + '</td>' +
                        '<td class="small">' + a.ketepatan_evaluasi + '</td>' +
                        '<td class="fw-semibold">' + a.skor + '</td>' +
                        '<td><span class="badge ' + badge + '">' + a.status_label +
                        '</span></td><td class="text-end">' + aksi + '</td></tr>';
            });
            body.html(html);
        }).fail(function () {
            body.html('<tr><td colspan="9" class="text-center text-danger py-3">Gagal memuat data.</td></tr>');
        });
    });

    $(document).on('click', '.btn-assign', function () {
        var slot = $(this).data('slot'), tutor = $(this).data('tutor'), name = $(this).data('name');
        $('#modal-applicants').modal('hide');
        Swal.fire({
            title: 'Tetapkan ' + name + '?',
            text: 'Jadwal mengajar untuk seluruh siswa di group ini akan langsung dibuat.',
            icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, Tetapkan', cancelButtonText: 'Batal'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({ url: '/admin/schedule-slots/' + slot + '/assign', type: 'PUT', data: { tutor_id: tutor, _token: token },
                success: sukses, error: gagal });
        });
    });

    $(document).on('click', '.btn-unassign', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Batalkan penetapan?',
            html: 'Jadwal yang sudah dibuat akan ditarik.<br><small class="text-muted">Jadwal yang sudah dievaluasi tetap dipertahankan.</small>',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Batalkan', cancelButtonText: 'Tidak'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({ url: '/admin/schedule-slots/' + id + '/unassign', type: 'PUT', data: { _token: token }, success: sukses, error: gagal });
        });
    });

    $(document).on('click', '.btn-close-slot', function () {
        var id = $(this).data('id');
        $.ajax({ url: '/admin/schedule-slots/' + id + '/close', type: 'PUT', data: { _token: token }, success: sukses, error: gagal });
    });

    $(document).on('click', '.btn-reopen', function () {
        var id = $(this).data('id');
        $.ajax({ url: '/admin/schedule-slots/' + id + '/reopen', type: 'PUT', data: { _token: token }, success: sukses, error: gagal });
    });

    $(document).on('click', '.btn-pengganti', function () {
        var id = $(this).data('id');
        $('#btn-do-pengganti').data('id', id);
        var $select = $('#pengganti-tutor').html('<option value="">Memuat...</option>');
        $('#modal-pengganti').modal('show');
        $.get('/admin/schedule-slots/' + id + '/tutor-pengganti', function (data) {
            if (!data.tutors.length) {
                $select.html('<option value="">Tidak ada tutor berkualifikasi</option>');
                return;
            }
            var html = '<option value="">-- Pilih Tutor --</option>';
            data.tutors.forEach(function (t) { html += '<option value="' + t.id + '">' + t.name + '</option>'; });
            $select.html(html);
        }).fail(function () {
            $select.html('<option value="">Gagal memuat data</option>');
        });
    });

    $('#btn-do-pengganti').on('click', function () {
        var id = $(this).data('id');
        var tutorId = $('#pengganti-tutor').val();
        if (!tutorId) { Swal.fire('Pilih tutor', 'Silakan pilih tutor pengganti terlebih dahulu.', 'warning'); return; }
        $.ajax({ url: '/admin/schedule-slots/' + id + '/assign', type: 'PUT', data: { tutor_id: tutorId, _token: token },
            success: function (res) { $('#modal-pengganti').modal('hide'); sukses(res); },
            error: gagal });
    });
});
</script>
@endpush
