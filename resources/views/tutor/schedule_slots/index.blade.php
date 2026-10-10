@extends('tutor.layouts.app')

@section('title', 'Jadwal Tersedia - LIVO Tutor')

@push('css')
<style>
    .slot-card { transition: box-shadow .15s ease, transform .15s ease; }
    .slot-card:hover { box-shadow: 0 .5rem 1rem rgba(0,0,0,.08); transform: translateY(-2px); }
    .slot-card .slot-date { line-height: 1.1; }
</style>
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-md-7">
        <h1 class="fs-3 mb-1">Jadwal Saya</h1>
        <p class="text-muted mb-0">
            Konfirmasi jadwal rutin Anda, dan pilih jadwal baru yang sesuai kualifikasi:
            <strong>{{ $spesialisasi ? implode(', ', $spesialisasi) : 'belum diatur admin' }}</strong>.
        </p>
    </div>
    <div class="col-md-5 text-md-end mt-2 mt-md-0">
        <div class="btn-group">
            <a href="{{ route('tutor.schedule-slots.index', ['week' => $prevWeek]) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-left"></i> Minggu Lalu</a>
            <a href="{{ route('tutor.schedule-slots.index') }}" class="btn btn-outline-primary btn-sm">Minggu Ini</a>
            <a href="{{ route('tutor.schedule-slots.index', ['week' => $nextWeek]) }}" class="btn btn-outline-secondary btn-sm">Minggu Depan <i class="bi bi-chevron-right"></i></a>
        </div>
    </div>
</div>

{{-- ══════════ 1. Perlu dikonfirmasi ══════════ --}}
<div class="d-flex align-items-center justify-content-between mb-2">
    <h2 class="fs-4 mb-0">
        <i class="bi bi-hourglass-split text-warning me-1"></i> Perlu Dikonfirmasi
        <span class="badge bg-warning text-dark ms-1">{{ $perluKonfirmasi->count() }}</span>
    </h2>
</div>
<p class="text-muted small">Jadwal rutin yang Anda pegang sebelumnya. Konfirmasi agar jadwal mengajar dibuat, atau ambil izin bila berhalangan minggu ini saja (jadwal rutin Anda tetap berlanjut minggu depan).</p>

<div class="row row-cards mb-4">
    @forelse($perluKonfirmasi as $slot)
        @include('tutor.schedule_slots._kartu', ['slot' => $slot, 'mode' => 'konfirmasi', 'app' => null])
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center text-muted py-4">
                    <i class="bi bi-check2-circle fs-1 d-block mb-2 text-success"></i>
                    Tidak ada jadwal yang menunggu konfirmasi.
                </div>
            </div>
        </div>
    @endforelse
</div>

{{-- ══════════ 2. Jadwal tersedia ══════════ --}}
<div class="d-flex align-items-center justify-content-between mb-2 mt-4">
    <h2 class="fs-4 mb-0">
        <i class="bi bi-calendar-plus text-primary me-1"></i> Jadwal Tersedia
        <span class="badge bg-primary ms-1">{{ $tersedia->count() }}</span>
    </h2>
</div>
<p class="small">
    <strong class="text-dark">{{ $weekStart->translatedFormat('d M Y') }} – {{ $weekEnd->translatedFormat('d M Y') }}</strong>.
    <span class="text-muted">Anda boleh memilih beberapa; admin yang menentukan siapa yang mengisi.</span>
</p>

<div class="row row-cards mb-4">
    @forelse($tersedia as $slot)
        @include('tutor.schedule_slots._kartu', [
            'slot' => $slot, 'mode' => 'tersedia', 'app' => $lamaran[$slot->id] ?? null,
        ])
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center text-muted py-4">
                    <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                    Belum ada jadwal terbuka yang sesuai kualifikasi Anda pada minggu ini.
                </div>
            </div>
        </div>
    @endforelse
</div>

{{-- ══════════ 3. Daftar jadwal terkonfirmasi ══════════ --}}
@if($keputusan->isNotEmpty())
<h2 class="fs-4 mb-2 mt-4"><i class="bi bi-clipboard-check me-1"></i> Daftar Jadwal Terkonfirmasi</h2>
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table mb-0">
            <thead><tr><th>Hari / Tanggal</th><th>Mata Pelajaran</th><th>Group Siswa</th><th>Hasil</th></tr></thead>
            <tbody>
                @foreach($keputusan as $k)
                    <tr>
                        <td>{{ $k->slot->hari }}, {{ $k->slot->class_date->translatedFormat('d M Y') }}</td>
                        <td>{{ $k->slot->subject->subject_name ?? '-' }}</td>
                        <td>{{ $k->slot->studentGroup->name ?? '-' }}</td>
                        <td><span class="badge {{ $k->status === 'accepted' ? 'bg-success' : 'bg-secondary' }}">{{ $k->statusLabel() }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
{{-- Modal daftar siswa (dipakai bersama oleh semua kartu) --}}
<div class="modal fade" id="modal-siswa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title"><i class="bi bi-people me-2 text-primary"></i>Siswa di Kelas Ini</h5>
                    <p class="text-muted small mb-0" id="siswa-judul">—</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm table-striped align-middle mb-0">
                    <thead>
                        <tr><th style="width:40px">#</th><th>Nama Siswa</th><th>NIS</th><th>Jenjang</th></tr>
                    </thead>
                    <tbody id="siswa-body"></tbody>
                </table>
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

    // Daftar siswa sudah ikut di-render di data-attribute kartu → tanpa request tambahan.
    $(document).on('click', '.btn-lihat-siswa', function () {
        var $b = $(this);
        var siswa = $b.data('siswa') || [];

        $('#siswa-judul').text($b.data('judul') + ' · ' + $b.data('group'));

        var html = '';
        siswa.forEach(function (s, i) {
            html += '<tr><td class="text-muted">' + (i + 1) + '</td>' +
                    '<td class="fw-semibold">' + $('<div>').text(s.nama).html() + '</td>' +
                    '<td>' + $('<div>').text(s.nis).html() + '</td>' +
                    '<td>' + $('<div>').text(s.grade).html() + '</td></tr>';
        });
        $('#siswa-body').html(html || '<tr><td colspan="4" class="text-center text-muted py-3">Tidak ada siswa aktif.</td></tr>');

        $('#modal-siswa').modal('show');
    });

    $(document).on('click', '.btn-confirm', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Konfirmasi jadwal ini?',
            text: 'Jadwal mengajar untuk seluruh siswa di group akan dibuat.',
            icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, Konfirmasi', cancelButtonText: 'Batal'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({ url: '/tutor/jadwal-tersedia/' + id + '/konfirmasi', type: 'PUT', data: { _token: token }, success: sukses, error: gagal });
        });
    });

    $(document).on('click', '.btn-izin', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Ambil izin untuk jadwal ini?',
            text: 'Jadwal minggu ini akan dikosongkan dan dicarikan pengganti oleh admin. Jadwal rutin Anda tetap berlanjut minggu depan.',
            icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Izin', cancelButtonText: 'Batal'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({ url: '/tutor/jadwal-tersedia/' + id + '/izin', type: 'PUT', data: { _token: token }, success: sukses, error: gagal });
        });
    });

    $(document).on('click', '.btn-apply', function () {
        var id = $(this).data('id');
        $.ajax({ url: '/tutor/jadwal-tersedia/' + id + '/pilih', type: 'POST', data: { _token: token }, success: sukses, error: gagal });
    });

    $(document).on('click', '.btn-withdraw', function () {
        var id = $(this).data('id');
        $.ajax({ url: '/tutor/jadwal-tersedia/' + id + '/batal', type: 'PUT', data: { _token: token }, success: sukses, error: gagal });
    });
});
</script>
@endpush
