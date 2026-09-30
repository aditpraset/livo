{{--
    Kartu satu slot jadwal.
    $slot : ScheduleSlot (sudah eager-load session, subject, studentGroup.students aktif)
    $mode : 'konfirmasi' (jadwal bawaan minggu lalu) | 'tersedia' (slot dibuka, bisa dilamar)
    $app  : lamaran tutor ini pada slot tsb (khusus mode 'tersedia'), boleh null
--}}
@php
    $siswa   = $slot->studentGroup?->students ?? collect();
    $konfirm = $mode === 'konfirmasi';

    // Dipakai modal daftar siswa — dibawa lewat data-attribute agar tidak perlu request tambahan.
    $daftarSiswa = $siswa->map(fn ($s) => [
        'nama'  => $s->full_name,
        'nis'   => $s->nis ?: '-',
        'grade' => $s->grade ?: '-',
    ])->values();

    $judulSlot = $slot->hari . ', ' . $slot->class_date->translatedFormat('d M Y')
        . ' · ' . ($slot->session->name ?? '-')
        . ' · ' . ($slot->subject->subject_name ?? '-');
@endphp

<div class="col-md-6 col-xl-4">
    <div class="card slot-card h-100 {{ $konfirm ? 'border-warning' : '' }}">
        <div class="card-header d-flex align-items-center justify-content-between {{ $konfirm ? 'bg-warning bg-opacity-10' : '' }}">
            <div class="slot-date">
                <div class="fw-bold fs-3">{{ $slot->hari }}</div>
                <div class="text-muted small">{{ $slot->class_date->translatedFormat('d M Y') }}</div>
            </div>
            @if($konfirm)
                <span class="badge bg-warning text-dark"><i class="bi bi-arrow-repeat me-1"></i>Jadwal Rutin</span>
            @elseif($slot->status === 'vacant')
                <span class="badge bg-danger">Butuh Pengganti</span>
            @elseif($app && $app->status === 'pending')
                <span class="badge bg-warning text-dark">Menunggu Admin</span>
            @else
                <span class="badge bg-primary">Terbuka</span>
            @endif
        </div>

        <div class="card-body">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-clock text-muted"></i>
                <span>{{ $slot->session->name ?? '-' }}
                    <span class="text-muted">· {{ substr($slot->session->time_start ?? '', 0, 5) }}–{{ substr($slot->session->time_end ?? '', 0, 5) }}</span>
                </span>
            </div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-journal-bookmark text-muted"></i>
                <span class="fw-semibold">{{ $slot->subject->subject_name ?? '-' }}</span>
            </div>
            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-collection text-muted"></i>
                <span>{{ $slot->studentGroup->name ?? '-' }}</span>
            </div>

            <div class="d-flex align-items-center justify-content-between border-top pt-3">
                @if($siswa->isEmpty())
                    <span class="text-warning small">
                        <i class="bi bi-exclamation-triangle me-1"></i>Belum ada siswa aktif
                    </span>
                @else
                    <span class="d-flex align-items-center gap-2">
                        <i class="bi bi-people text-muted"></i>
                        <span><span class="fw-bold fs-4">{{ $siswa->count() }}</span> <span class="text-muted">siswa</span></span>
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-primary btn-lihat-siswa"
                        data-judul="{{ $judulSlot }}"
                        data-group="{{ $slot->studentGroup->name ?? '-' }}"
                        data-siswa='{{ json_encode($daftarSiswa) }}'>
                        <i class="bi bi-list-ul me-1"></i>Lihat Siswa
                    </button>
                @endif
            </div>
        </div>

        <div class="card-footer d-flex gap-2">
            @if($konfirm)
                <button class="btn btn-success flex-fill btn-confirm" data-id="{{ $slot->id }}">
                    <i class="bi bi-check-lg me-1"></i>Konfirmasi
                </button>
                <button class="btn btn-outline-danger btn-izin" data-id="{{ $slot->id }}">Izin</button>
            @elseif($app && $app->status === 'pending')
                <button class="btn btn-outline-secondary flex-fill btn-withdraw" data-id="{{ $slot->id }}">
                    <i class="bi bi-x-lg me-1"></i>Batalkan Pilihan
                </button>
            @else
                <button class="btn btn-primary flex-fill btn-apply" data-id="{{ $slot->id }}">
                    <i class="bi bi-hand-index me-1"></i>Pilih Jadwal Ini
                </button>
            @endif
        </div>
    </div>
</div>
