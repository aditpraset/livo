@extends('admin.layouts.app')

@section('title', 'Detail Tutor - LIVO Admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="mb-4">
            <a href="{{ route('admin.tutors.index') }}" class="btn btn-link link-secondary ps-0">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
            <h1 class="fs-3 mb-1 mt-2">Profil Tutor: {{ $tutor->name }}</h1>
            <p class="text-muted mb-0">Terdaftar sejak {{ $tutor->created_at->format('d M Y') }}</p>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body text-center py-4">
                @if($tutor->photo)
                    <img src="{{ asset('storage/' . $tutor->photo) }}" alt="{{ $tutor->name }}"
                        class="mx-auto mb-3 d-block" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover;">
                @else
                    <div class="mx-auto mb-3 bg-primary text-white d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; border-radius: 50%; font-size: 2.5rem; font-weight: 800;">
                        {{ substr($tutor->name, 0, 1) }}
                    </div>
                @endif
                <h4 class="fw-bold mb-1">{{ $tutor->name }}</h4>
                <p class="text-muted small mb-3">{{ $schedulesCount }} jadwal tercatat</p>
                <div class="d-flex justify-content-center flex-wrap gap-2">
                    @php
                        $kategoriLabel = \App\Models\Tutor::KATEGORI_OPTIONS[$tutor->kategori] ?? '-';
                        $kategoriBadge = $tutor->kategori === 'tetap' ? 'bg-success' : 'bg-secondary';
                    @endphp
                    <span class="badge {{ $kategoriBadge }}">{{ $kategoriLabel }}</span>
                    @forelse(is_array($tutor->specialization) ? $tutor->specialization : [] as $spec)
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $spec }}</span>
                    @empty
                        <span class="badge bg-secondary-subtle text-secondary">Tanpa Spesialisasi</span>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0 py-1">Kontak</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-4 py-3">
                        <div class="small text-muted mb-1">No. HP / WhatsApp</div>
                        <div class="fw-semibold">{{ $tutor->phone ?? '-' }}</div>
                    </div>
                    <div class="list-group-item px-4 py-3">
                        <div class="small text-muted mb-1">Email</div>
                        <div class="fw-semibold">{{ $tutor->email ?? '-' }}</div>
                    </div>
                    <div class="list-group-item px-4 py-3">
                        <div class="small text-muted mb-1">No. Rekening</div>
                        <div class="fw-semibold">{{ $tutor->no_rekening ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0 py-1">Tarif Fee</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    @php
                        $fmt = fn ($v) => $v !== null ? 'Rp ' . number_format($v, 0, ',', '.') : '-';
                        $tarif = [
                            'Fee per Sesi Semi-Privat' => $tutor->fee_per_session,
                            'Fee per Sesi Privat' => $tutor->fee_per_student_private,
                            'Fee per Siswa' => $tutor->fee_per_student,
                            'Fee Transport per Hari' => $tutor->fee_transport_per_day,
                            'Fee Private Khusus' => $tutor->fee_private_khusus,
                            'Fee TKA Reguler' => $tutor->fee_tka_regular,
                            'Fee Private SMA' => $tutor->fee_private_sma,
                            'Fee TKA Visit' => $tutor->fee_tka_visit,
                        ];
                    @endphp
                    @foreach($tarif as $label => $value)
                        <div class="list-group-item px-4 py-2 d-flex justify-content-between">
                            <span class="small text-muted">{{ $label }}</span>
                            <span class="fw-semibold small">{{ $fmt($value) }}</span>
                        </div>
                    @endforeach
                    @if($tutor->kategori === 'tetap')
                        <div class="list-group-item px-4 py-2 d-flex justify-content-between">
                            <span class="small text-muted">Gaji Pokok</span>
                            <span class="fw-semibold small">{{ $fmt($tutor->gaji_pokok) }}</span>
                        </div>
                        <div class="list-group-item px-4 py-2 d-flex justify-content-between">
                            <span class="small text-muted">Tunjangan per Bulan</span>
                            <span class="fw-semibold small">{{ $fmt($tutor->tunjangan_per_bulan) }}</span>
                        </div>
                        <div class="list-group-item px-4 py-2 d-flex justify-content-between">
                            <span class="small text-muted">Maks. Sesi Tunjangan/Bulan</span>
                            <span class="fw-semibold small">{{ $tutor->maks_sesi_tunjangan_per_bulan ?? 'Tanpa batas' }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light-subtle">
                    <h6 class="mb-0 fw-bold">Jadwal Mengajar</h6>
                    <button type="button" class="btn btn-primary btn-sm" id="btn-add-sched">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Jadwal
                    </button>
                </div>
                <div class="table-responsive">
                    <table id="tutor-schedules-table" class="table table-hover align-middle mb-0 w-100">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="py-3">Siswa</th>
                                <th class="py-3">Mata Pelajaran</th>
                                <th class="py-3">Ruang</th>
                                <th class="py-3">Jam</th>
                                <th class="py-3 text-center">Status</th>
                                <th class="py-3 text-center">Evaluasi</th>
                                <th class="px-4 py-3 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light-subtle flex-wrap gap-2">
                    <div>
                        <h6 class="mb-0 fw-bold">Pola Jadwal Mingguan</h6>
                        <small class="text-muted">Baris pola (hari + sesi + mapel + group) yang rutin dipegang tutor ini — ditampilkan untuk minggu {{ $weekStart->translatedFormat('d M Y') }}.</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="btn-group">
                            <a href="{{ route('admin.tutors.show', $tutor) }}?minggu=ini" class="btn btn-sm {{ $mingguPilihan === 'ini' ? 'btn-primary' : 'btn-outline-primary' }}">Minggu Ini</a>
                            <a href="{{ route('admin.tutors.show', $tutor) }}?minggu=depan" class="btn btn-sm {{ $mingguPilihan === 'depan' ? 'btn-primary' : 'btn-outline-primary' }}">Minggu Depan</a>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" id="btn-add-pola">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Pola
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4 py-3">Pola</th>
                                <th class="py-3">Hari</th>
                                <th class="py-3">Sesi (Jam)</th>
                                <th class="py-3">Mata Pelajaran</th>
                                <th class="py-3">Group Siswa</th>
                                <th class="py-3 text-center">Status Minggu Ini</th>
                                <th class="px-4 py-3 text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($polaSaya as $row)
                                @php
                                    $item = $row['item'];
                                    $slot = $row['slot'];
                                    $polaBadge = match($slot?->status) {
                                        \App\Models\ScheduleSlot::STATUS_ASSIGNED => 'bg-success',
                                        \App\Models\ScheduleSlot::STATUS_PENDING_CONFIRMATION => 'bg-warning text-dark',
                                        \App\Models\ScheduleSlot::STATUS_VACANT => 'bg-danger',
                                        \App\Models\ScheduleSlot::STATUS_OPEN => 'bg-secondary',
                                        default => 'bg-light text-dark border',
                                    };
                                    $milikTutorIni = $slot && (int) $slot->effectiveTutorId() === $tutor->id && $slot->isAssigned();
                                @endphp
                                <tr>
                                    <td class="px-4">{{ $item->pattern->name ?? '-' }}</td>
                                    <td>{{ $item->hari }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            {{ substr($item->session->time_start ?? '', 0, 5) }} – {{ substr($item->session->time_end ?? '', 0, 5) }}
                                        </span>
                                        <div class="small text-muted">{{ $item->session->name ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                            {{ $item->subject->subject_name ?? '-' }}
                                        </span>
                                    </td>
                                    <td>{{ $item->studentGroup->name ?? '-' }}</td>
                                    <td class="text-center">
                                        @if($slot)
                                            <span class="badge {{ $polaBadge }}">{{ $slot->statusLabel() }}</span>
                                            @if($slot->status === \App\Models\ScheduleSlot::STATUS_VACANT)
                                                <div class="small text-danger mt-1">Tutor ini izin minggu ini</div>
                                            @elseif(!$milikTutorIni && $slot->effectiveTutorId())
                                                <div class="small text-muted mt-1">Dipegang tutor lain</div>
                                            @endif
                                        @else
                                            <span class="badge bg-light text-dark border">Belum Disiapkan</span>
                                        @endif
                                    </td>
                                    <td class="px-4 text-end">
                                        <div class="btn-group btn-group-sm">
                                            @if($milikTutorIni)
                                                <button class="btn btn-outline-secondary btn-release-pola"
                                                    data-slot-id="{{ $slot->id }}"
                                                    data-slot-status="{{ $slot->status }}"
                                                    data-label="{{ $item->hari }} · {{ $item->session->name ?? '-' }} · {{ $item->studentGroup->name ?? '-' }}"
                                                    title="Lepas dari Tutor Ini">
                                                    <i class="bi bi-person-dash"></i>
                                                </button>
                                            @elseif(!$slot || in_array($slot->status, [\App\Models\ScheduleSlot::STATUS_OPEN, \App\Models\ScheduleSlot::STATUS_VACANT], true))
                                                <button class="btn btn-outline-success btn-assign-week-pola"
                                                    data-item-id="{{ $item->id }}"
                                                    data-label="{{ $item->hari }} · {{ $item->session->name ?? '-' }} · {{ $item->studentGroup->name ?? '-' }}"
                                                    title="Tetapkan ke Tutor Ini untuk Minggu Ini">
                                                    <i class="bi bi-person-check"></i>
                                                </button>
                                            @endif
                                            <button class="btn btn-outline-warning btn-edit-pola"
                                                data-id="{{ $item->id }}"
                                                data-hari="{{ $item->hari }}"
                                                data-session="{{ $item->session_id }}"
                                                data-subject="{{ $item->subject_id }}"
                                                data-group="{{ $item->student_group_id }}"
                                                title="Edit Pola">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-outline-danger btn-delete-pola"
                                                data-id="{{ $item->id }}"
                                                title="Hapus Baris Pola Ini">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-calendar-week fs-2 d-block mb-2"></i>
                                        Belum ada pola jadwal rutin yang dipegang tutor ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Tambah / Edit Pola Jadwal --}}
<div class="modal fade" id="modal-pola-tutor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-pola-title">Tambah Pola Jadwal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="pola-item-id">
                <div class="row g-3">
                    <div class="col-md-6" id="pola-pattern-wrap">
                        <label class="form-label fw-semibold">Pola Jadwal <span class="text-danger">*</span></label>
                        <select id="pola-pattern" class="form-select">
                            <option value="">-- Pilih Pola --</option>
                            @foreach($patterns as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}{{ $p->is_active ? ' (aktif)' : '' }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="err-pola-pattern"></div>
                        <small class="text-muted" id="pola-pattern-hint" style="display:none;">Pola tidak bisa diubah saat edit — hapus &amp; buat ulang bila ingin memindah ke pola lain.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Hari <span class="text-danger">*</span></label>
                        <select id="pola-hari" class="form-select">
                            <option value="">-- Pilih Hari --</option>
                            @foreach(\App\Models\SchedulePatternItem::HARI as $h)
                                <option value="{{ $h }}">{{ $h }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="err-pola-hari"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Sesi Pembelajaran <span class="text-danger">*</span></label>
                        <select id="pola-session" class="form-select">
                            <option value="">-- Pilih Sesi --</option>
                            @foreach($scheduleSessions as $sess)
                                <option value="{{ $sess->id }}">{{ $sess->name }} ({{ substr($sess->time_start, 0, 5) }} – {{ substr($sess->time_end, 0, 5) }})</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="err-pola-session"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Mata Pelajaran <span class="text-danger">*</span></label>
                        <select id="pola-subject" class="form-select">
                            <option value="">-- Pilih Mapel --</option>
                            @foreach($subjects as $sub)
                                <option value="{{ $sub->id }}">{{ $sub->subject_name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="err-pola-subject"></div>
                        <small class="text-muted">Dibatasi sesuai spesialisasi tutor ini.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Group Siswa <span class="text-danger">*</span></label>
                        <select id="pola-group" class="form-select">
                            <option value="">-- Pilih Group --</option>
                            @foreach($studentGroups as $g)
                                <option value="{{ $g->id }}">{{ $g->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="err-pola-group"></div>
                    </div>
                </div>
                <div class="alert alert-info border-0 bg-info bg-opacity-10 py-2 px-3 mt-3 mb-0 small" id="pola-add-note">
                    <i class="bi bi-info-circle me-1"></i>
                    Baris baru akan langsung ditetapkan ke tutor ini untuk minggu yang sedang dilihat ({{ $weekStart->translatedFormat('d M Y') }}) — jadwal mengajar minggu itu langsung dibuat, dan otomatis dibawa sebagai jadwal rutin di minggu-minggu berikutnya.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btn-save-pola">Simpan</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Tambah / Edit Jadwal (tutor sudah terpilih) --}}
<div class="modal fade" id="modal-sched-tutor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-sched-title">Tambah Jadwal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="sched-id">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Siswa <span class="text-danger">*</span></label>
                        <select id="sched-student" class="form-select">
                            <option value="">-- Pilih Siswa --</option>
                            @foreach($students as $s)
                                <option value="{{ $s->id }}">{{ $s->full_name }}{{ $s->nis ? ' (' . $s->nis . ')' : '' }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="err-sched-student"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Mata Pelajaran <span class="text-danger">*</span></label>
                        <select id="sched-subject" class="form-select">
                            <option value="">-- Pilih Mapel --</option>
                            @foreach($subjects as $sub)
                                <option value="{{ $sub->id }}">{{ $sub->subject_name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="err-sched-subject"></div>
                        <small class="text-muted">Dibatasi sesuai spesialisasi tutor ini.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Ruang Kelas</label>
                        <input type="text" id="sched-room" class="form-control" placeholder="cth: Ruang A">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" id="sched-date" class="form-control">
                        <div class="invalid-feedback" id="err-sched-date"></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Sesi Pembelajaran <span class="text-danger">*</span></label>
                        <select id="sched-session" class="form-select">
                            <option value="">-- Pilih Sesi --</option>
                            @foreach($scheduleSessions as $sess)
                                <option value="{{ $sess->id }}"
                                    data-start="{{ substr($sess->time_start, 0, 5) }}"
                                    data-end="{{ substr($sess->time_end, 0, 5) }}">
                                    {{ $sess->name }} ({{ substr($sess->time_start, 0, 5) }} – {{ substr($sess->time_end, 0, 5) }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text" id="sched-time-display"></div>
                        <div class="invalid-feedback" id="err-sched-session"></div>
                        <input type="hidden" id="sched-start">
                        <input type="hidden" id="sched-end">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btn-save-sched">Simpan Jadwal</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Evaluasi --}}
<div class="modal fade" id="modal-eval-tutor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info bg-opacity-10">
                <h5 class="modal-title"><i class="bi bi-clipboard2-check me-2"></i>Evaluasi Sesi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="eval-sched-id">
                <div class="alert alert-light border mb-3 small" id="eval-info-box"></div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Kehadiran Siswa <span class="text-danger">*</span></label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="eval_att" id="ev-hadir" value="hadir">
                            <label class="form-check-label" for="ev-hadir"><span class="badge bg-success">Hadir</span></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="eval_att" id="ev-izin" value="izin">
                            <label class="form-check-label" for="ev-izin"><span class="badge bg-warning text-dark">Izin</span></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="eval_att" id="ev-alfa" value="alfa">
                            <label class="form-check-label" for="ev-alfa"><span class="badge bg-danger">Alfa</span></label>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Sub Pokok Bahasan</label>
                    <select id="eval-syllabus" class="form-select">
                        <option value="">— Pilih materi sesuai silabus mapel —</option>
                        <option value="__other__">Lainnya (isi manual)</option>
                    </select>
                    <small class="text-muted" id="eval-syllabus-empty" style="display:none;">
                        Belum ada silabus untuk mata pelajaran ini di kelas siswa.
                    </small>
                    <input type="text" id="eval-materi-manual" class="form-control mt-2" style="display:none;"
                        placeholder="Tulis materi / sub pokok bahasan secara manual">
                </div>
                <input type="hidden" id="eval-pemahaman">
                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Nilai (1–100)</label>
                        <input type="number" id="eval-posttest" class="form-control" min="1" max="100" placeholder="1–100">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Kemampuan Analisa (1–100)</label>
                        <input type="number" id="eval-analisa" class="form-control" min="1" max="100" placeholder="1–100">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Kemampuan Hafalan (1–100)</label>
                        <input type="number" id="eval-hafalan" class="form-control" min="1" max="100" placeholder="1–100">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Kepercayaan Diri (1–100)</label>
                        <input type="number" id="eval-kepercayaan" class="form-control" min="1" max="100" placeholder="1–100">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Catatan Tutor</label>
                    <textarea id="eval-notes" class="form-control" rows="3" placeholder="Perkembangan belajar siswa, materi yang dipelajari, dll."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-info text-white" id="btn-save-eval">Simpan Evaluasi</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    var TUTOR_ID = {{ $tutor->id }};
    var WEEK_START = '{{ $weekStart->toDateString() }}';
    var token = '{{ csrf_token() }}';

    var schedTable = $('#tutor-schedules-table').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 10,
        ajax: "{{ route('admin.tutors.schedules.data', $tutor) }}",
        columns: [
            { data: 'class_date' },
            { data: 'student_name' },
            { data: 'subject_name' },
            { data: 'room' },
            { data: 'time', orderable: false },
            { data: 'status_schedule', orderable: false, className: 'text-center' },
            { data: 'evaluasi', orderable: false, searchable: false, className: 'text-center' },
            { data: 'action', orderable: false, searchable: false, className: 'text-end' },
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json' },
        order: []
    });

    function showAjaxError(xhr) {
        if (xhr.status === 419) {
            Swal.fire({
                icon: 'warning', title: 'Sesi Berakhir',
                text: 'Sesi Anda sudah kadaluarsa. Muat ulang halaman lalu coba lagi.',
                confirmButtonText: 'Muat Ulang', showCancelButton: true, cancelButtonText: 'Tutup'
            }).then(function (r) { if (r.isConfirmed) location.reload(); });
            return;
        }
        Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan. Silakan coba lagi.', 'error');
    }

    /* ================================================================
       Sesi → isi jam mulai/selesai otomatis
    ================================================================ */
    $(document).on('change', '#sched-session', function () {
        var opt = $(this).find('option:selected');
        var start = opt.data('start') || '';
        var end = opt.data('end') || '';
        $('#sched-start').val(start);
        $('#sched-end').val(end);
        $('#sched-time-display').html(
            start && end ? '<i class="bi bi-clock me-1"></i>Jam: <strong>' + start + ' – ' + end + '</strong>' : ''
        );
    });

    function resetSchedModal() {
        $('#sched-id').val('');
        $('#sched-student, #sched-subject, #sched-session').val('').removeClass('is-invalid');
        $('#sched-room, #sched-date, #sched-start, #sched-end').val('');
        $('#sched-time-display').text('');
        $('#err-sched-student, #err-sched-subject, #err-sched-date, #err-sched-session').text('');
    }

    /* ---- Tambah Jadwal ---- */
    $('#btn-add-sched').on('click', function () {
        resetSchedModal();
        $('#modal-sched-title').text('Tambah Jadwal – {{ $tutor->name }}');
        $('#modal-sched-tutor').modal('show');
    });

    /* ---- Edit Jadwal ---- */
    $(document).on('click', '.btn-edit-tutor', function () {
        var id = $(this).data('id');
        resetSchedModal();
        $('#modal-sched-title').text('Edit Jadwal – {{ $tutor->name }}');

        $.get('/admin/schedules/' + id, function (data) {
            var savedStart = data.start_time.substring(0, 5);
            var savedEnd = data.end_time.substring(0, 5);

            $('#sched-id').val(data.id);
            $('#sched-student').val(data.student_id);
            $('#sched-subject').val(data.subject_id);
            $('#sched-room').val(data.room);
            $('#sched-date').val(data.class_date.substring(0, 10));

            var matchedOpt = $('#sched-session option').filter(function () {
                return $(this).data('start') === savedStart && $(this).data('end') === savedEnd;
            });
            if (matchedOpt.length) {
                $('#sched-session').val(matchedOpt.val());
                $('#sched-time-display').html('<i class="bi bi-clock me-1"></i>Jam: <strong>' + savedStart + ' – ' + savedEnd + '</strong>');
            } else {
                $('#sched-time-display').html('<i class="bi bi-clock me-1 text-warning"></i>Jam tersimpan: <strong>' + savedStart + ' – ' + savedEnd + '</strong> (pilih sesi untuk mengubah)');
            }
            $('#sched-start').val(savedStart);
            $('#sched-end').val(savedEnd);

            $('#modal-sched-tutor').modal('show');
        }).fail(showAjaxError);
    });

    /* ---- Simpan Jadwal (Tambah/Edit) ---- */
    $('#btn-save-sched').on('click', function () {
        var id = $('#sched-id').val();
        var url = id ? '/admin/schedules/' + id : '{{ route("admin.schedules.store") }}';
        var type = id ? 'PUT' : 'POST';

        $('#err-sched-student, #err-sched-subject, #err-sched-date, #err-sched-session').text('');
        $('#sched-student, #sched-subject, #sched-date, #sched-session').removeClass('is-invalid');

        $.ajax({
            url: url, type: type,
            data: {
                student_id: $('#sched-student').val(),
                tutor_id: TUTOR_ID,
                subject_id: $('#sched-subject').val(),
                room: $('#sched-room').val(),
                class_date: $('#sched-date').val(),
                start_time: $('#sched-start').val(),
                end_time: $('#sched-end').val(),
                _token: token
            },
            success: function (res) {
                $('#modal-sched-tutor').modal('hide');
                schedTable.ajax.reload(null, false);
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false });
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    var err = xhr.responseJSON.errors ?? {};
                    var msg = xhr.responseJSON.message ?? '';
                    if (err.student_id) { $('#sched-student').addClass('is-invalid'); $('#err-sched-student').text(err.student_id[0]); }
                    if (err.subject_id) { $('#sched-subject').addClass('is-invalid'); $('#err-sched-subject').text(err.subject_id[0]); }
                    if (err.tutor_id)   { $('#err-sched-subject').text(err.tutor_id[0]); } // bentrok jam tutor ini
                    if (err.class_date) { $('#sched-date').addClass('is-invalid'); $('#err-sched-date').text(err.class_date[0]); }
                    if (err.start_time || err.end_time) { $('#sched-session').addClass('is-invalid'); $('#err-sched-session').text('Pilih sesi pembelajaran terlebih dahulu.'); }
                    if (msg && !Object.keys(err).length) Swal.fire('Gagal', msg, 'error');
                } else {
                    showAjaxError(xhr);
                }
            }
        });
    });

    /* ---- Tandai Selesai ---- */
    $(document).on('click', '.btn-done-tutor', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Tandai Sesi Selesai?', text: 'Kuota siswa akan berkurang 1 sesi.',
            icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, Selesai', cancelButtonText: 'Batal'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url: '/admin/schedules/' + id + '/status', type: 'PUT',
                data: { status: 'done', _token: token },
                success: function (res) {
                    schedTable.ajax.reload(null, false);
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false });
                },
                error: showAjaxError
            });
        });
    });

    /* ---- Batalkan ---- */
    $(document).on('click', '.btn-cancel-tutor', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Batalkan Jadwal?', icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#6c757d', confirmButtonText: 'Ya, Batalkan', cancelButtonText: 'Tidak'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url: '/admin/schedules/' + id + '/status', type: 'PUT',
                data: { status: 'canceled', _token: token },
                success: function () { schedTable.ajax.reload(null, false); },
                error: showAjaxError
            });
        });
    });

    /* ---- Hapus ---- */
    $(document).on('click', '.btn-delete-tutor', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Hapus Jadwal?', text: 'Data jadwal akan dihapus permanen.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url: '/admin/schedules/' + id, type: 'DELETE',
                data: { _token: token },
                success: function () { schedTable.ajax.reload(null, false); },
                error: showAjaxError
            });
        });
    });

    /* ---- Buka Modal Evaluasi ---- */
    $(document).on('click', '.btn-eval-tutor', function () {
        var id = $(this).data('id');

        $.get('/admin/schedules/' + id, function (data) {
            var dateStr = new Date(data.class_date).toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            $('#eval-sched-id').val(data.id);
            $('#eval-info-box').html(
                '<b>Siswa:</b> ' + (data.student?.full_name ?? '-') +
                ' &nbsp;|&nbsp; <b>Mapel:</b> ' + (data.subject?.subject_name ?? '-') +
                ' &nbsp;|&nbsp; <b>Tgl:</b> ' + dateStr
            );

            var $syl = $('#eval-syllabus');
            $syl.find('option').not('[value=""]').not('[value="__other__"]').remove();
            var syllabi = (data.subject && data.subject.syllabi) ? data.subject.syllabi : [];
            syllabi.forEach(function (s) {
                var label = s.pokok_bahasan + (s.sub_pokok_bahasan ? ' — ' + s.sub_pokok_bahasan : '');
                $syl.find('option[value="__other__"]').before('<option value="' + s.id + '">' + label + '</option>');
            });
            $('#eval-syllabus-empty').toggle(syllabi.length === 0);

            $('input[name="eval_att"]').prop('checked', false);
            $('#eval-syllabus, #eval-posttest, #eval-pemahaman, #eval-analisa, #eval-hafalan, #eval-kepercayaan, #eval-notes').val('');
            $('#eval-materi-manual').val('').hide();

            if (data.evaluation) {
                $('input[name="eval_att"][value="' + data.evaluation.student_attendance + '"]').prop('checked', true);
                if (data.evaluation.syllabus_id) {
                    $('#eval-syllabus').val(String(data.evaluation.syllabus_id));
                } else if (data.evaluation.materi_manual) {
                    $('#eval-syllabus').val('__other__');
                    $('#eval-materi-manual').val(data.evaluation.materi_manual).show();
                }
                $('#eval-posttest').val(data.evaluation.post_test ?? '');
                $('#eval-pemahaman').val(data.evaluation.pemahaman ?? '');
                $('#eval-analisa').val(data.evaluation.kemampuan_analisa ?? '');
                $('#eval-hafalan').val(data.evaluation.kemampuan_hafalan ?? '');
                $('#eval-kepercayaan').val(data.evaluation.kepercayaan_diri ?? '');
                $('#eval-notes').val(data.evaluation.tutor_notes ?? '');
            }

            $('#modal-eval-tutor').modal('show');
        }).fail(showAjaxError);
    });

    $('#eval-syllabus').on('change', function () {
        $('#eval-materi-manual').toggle($(this).val() === '__other__');
    });

    /* ---- Simpan Evaluasi ---- */
    $('#btn-save-eval').on('click', function () {
        var att = $('input[name="eval_att"]:checked').val();
        if (!att) { Swal.fire('Perhatian', 'Kehadiran siswa harus dipilih.', 'warning'); return; }

        var sylVal = $('#eval-syllabus').val();
        var isOther = sylVal === '__other__';

        $.ajax({
            url: '{{ route("admin.evaluations.store") }}', type: 'POST',
            data: {
                schedule_id: $('#eval-sched-id').val(),
                syllabus_id: isOther ? null : (sylVal || null),
                materi_manual: isOther ? ($('#eval-materi-manual').val() || null) : null,
                student_attendance: att,
                post_test: $('#eval-posttest').val() || null,
                pemahaman: $('#eval-pemahaman').val() || null,
                kemampuan_analisa: $('#eval-analisa').val() || null,
                kemampuan_hafalan: $('#eval-hafalan').val() || null,
                kepercayaan_diri: $('#eval-kepercayaan').val() || null,
                tutor_notes: $('#eval-notes').val(),
                _token: token
            },
            success: function (res) {
                $('#modal-eval-tutor').modal('hide');
                schedTable.ajax.reload(null, false);
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false });
            },
            error: function (xhr) {
                Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan.', 'error');
            }
        });
    });

    /* ================================================================
       Pola Jadwal Mingguan (tambah/ubah/hapus/lepas kepemilikan)
    ================================================================ */
    function resetPolaModal() {
        $('#pola-item-id').val('');
        $('#pola-pattern, #pola-hari, #pola-session, #pola-subject, #pola-group').val('').removeClass('is-invalid');
        $('#err-pola-pattern, #err-pola-hari, #err-pola-session, #err-pola-subject, #err-pola-group').text('');
    }

    $('#btn-add-pola').on('click', function () {
        resetPolaModal();
        $('#modal-pola-title').text('Tambah Pola Jadwal – {{ $tutor->name }}');
        $('#pola-pattern').prop('disabled', false);
        $('#pola-pattern-hint').hide();
        $('#pola-add-note').show();
        $('#modal-pola-tutor').modal('show');
    });

    $(document).on('click', '.btn-edit-pola', function () {
        var $b = $(this);
        resetPolaModal();
        $('#modal-pola-title').text('Edit Pola Jadwal – {{ $tutor->name }}');
        $('#pola-item-id').val($b.data('id'));
        $('#pola-hari').val($b.data('hari'));
        $('#pola-session').val($b.data('session'));
        $('#pola-subject').val($b.data('subject'));
        $('#pola-group').val($b.data('group'));
        // Pola (induk) tidak ikut diedit — item hanya bisa pindah hari/sesi/mapel/group.
        $('#pola-pattern').prop('disabled', true);
        $('#pola-pattern-hint').show();
        $('#pola-add-note').hide();
        $('#modal-pola-tutor').modal('show');
    });

    $('#btn-save-pola').on('click', function () {
        var id = $('#pola-item-id').val();
        var isEdit = !!id;

        $('#pola-pattern, #pola-hari, #pola-session, #pola-subject, #pola-group').removeClass('is-invalid');
        $('#err-pola-pattern, #err-pola-hari, #err-pola-session, #err-pola-subject, #err-pola-group').text('');

        var data = {
            hari: $('#pola-hari').val(),
            session_id: $('#pola-session').val(),
            subject_id: $('#pola-subject').val(),
            student_group_id: $('#pola-group').val(),
            _token: token
        };

        var url, type;
        if (isEdit) {
            url = '/admin/schedule-pattern-items/' + id;
            type = 'PUT';
        } else {
            data.schedule_pattern_id = $('#pola-pattern').val();
            data.week_start = WEEK_START;
            url = '/admin/tutors/' + TUTOR_ID + '/pattern-items';
            type = 'POST';
        }

        $.ajax({
            url: url, type: type, data: data,
            success: function (res) {
                $('#modal-pola-tutor').modal('hide');
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2500, showConfirmButton: false })
                    .then(function () { location.reload(); });
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    var err = xhr.responseJSON.errors ?? {};
                    var msg = xhr.responseJSON.message ?? '';
                    if (err.schedule_pattern_id) { $('#pola-pattern').addClass('is-invalid'); $('#err-pola-pattern').text(err.schedule_pattern_id[0]); }
                    if (err.hari)               { $('#pola-hari').addClass('is-invalid'); $('#err-pola-hari').text(err.hari[0]); }
                    if (err.session_id)         { $('#pola-session').addClass('is-invalid'); $('#err-pola-session').text(err.session_id[0]); }
                    if (err.subject_id)         { $('#pola-subject').addClass('is-invalid'); $('#err-pola-subject').text(err.subject_id[0]); }
                    if (err.student_group_id)   { $('#pola-group').addClass('is-invalid'); $('#err-pola-group').text(err.student_group_id[0]); }
                    if (msg && !Object.keys(err).length) Swal.fire('Gagal', msg, 'error');
                } else {
                    showAjaxError(xhr);
                }
            }
        });
    });

    $(document).on('click', '.btn-assign-week-pola', function () {
        var itemId = $(this).data('item-id');
        var label = $(this).data('label');

        Swal.fire({
            title: 'Tetapkan ke Tutor Ini?',
            html: 'Pola <b>' + label + '</b> akan ditetapkan ke {{ $tutor->name }} untuk minggu yang sedang dilihat.<br><small class="text-muted">Jadwal mengajar minggu itu langsung dibuat.</small>',
            icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, Tetapkan', cancelButtonText: 'Batal'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url: '/admin/tutors/' + TUTOR_ID + '/pattern-items/' + itemId + '/assign-week',
                type: 'POST',
                data: { week_start: WEEK_START, _token: token },
                success: function (res) {
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2500, showConfirmButton: false })
                        .then(function () { location.reload(); });
                },
                error: showAjaxError
            });
        });
    });

    $(document).on('click', '.btn-delete-pola', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Hapus Baris Pola Ini?',
            html: 'Baris pola akan dihapus permanen untuk SEMUA tutor &amp; minggu berikutnya.<br><small class="text-muted">Jadwal yang sudah dibuat minggu-minggu sebelumnya tetap ada.</small>',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url: '/admin/schedule-pattern-items/' + id, type: 'DELETE',
                data: { _token: token },
                success: function (res) {
                    Swal.fire({ icon: 'success', title: 'Dihapus', text: res.message, timer: 2000, showConfirmButton: false })
                        .then(function () { location.reload(); });
                },
                error: showAjaxError
            });
        });
    });

    $(document).on('click', '.btn-release-pola', function () {
        var slotId = $(this).data('slot-id');
        var status = $(this).data('slot-status');
        var label = $(this).data('label');
        var url = status === 'pending_confirmation'
            ? '/admin/schedule-slots/' + slotId + '/release-tutor'
            : '/admin/schedule-slots/' + slotId + '/unassign';

        Swal.fire({
            title: 'Lepas dari Tutor Ini?',
            html: 'Pola <b>' + label + '</b> akan dilepas dari {{ $tutor->name }} dan kembali dibuka/dilelang ke tutor lain.<br><small class="text-muted">Jadwal minggu ini yang sudah terbentuk akan ditarik (kecuali sudah dievaluasi).</small>',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', confirmButtonText: 'Ya, Lepas', cancelButtonText: 'Batal'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url: url, type: 'PUT', data: { _token: token },
                success: function (res) {
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2500, showConfirmButton: false })
                        .then(function () { location.reload(); });
                },
                error: showAjaxError
            });
        });
    });
});
</script>
@endpush
