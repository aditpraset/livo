@extends('admin.layouts.app')

@section('title', 'Detail Siswa - LIVO Admin')
@push('styles')
<style>
    .nav-tabs .nav-link {
        color: #6c757d;
        background: none;
        border: none;
        border-bottom: 2px solid transparent;
        transition: all 0.3s ease;
    }
    .nav-tabs .nav-link.active {
        color: var(--bs-primary);
        border-bottom: 2px solid var(--bs-primary);
        background: rgba(var(--bs-primary-rgb), 0.05);
    }
    .nav-tabs .nav-link:hover:not(.active) {
        border-bottom: 2px solid #dee2e6;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="mb-4">
            <a href="{{ route('admin.students.index') }}" class="btn btn-link link-secondary ps-0">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <div>
                    <h1 class="fs-3 mb-1">Profil Siswa: {{ $student->full_name }}</h1>
                    <p class="text-muted mb-0">Terdaftar sejak {{ $student->created_at->format('d M Y') }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.students.edit', $student->id) }}" class="btn btn-outline-warning">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body text-center py-4">
                <div class="avatar-circle mx-auto mb-3 bg-primary text-white d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; border-radius: 50%; font-size: 2.5rem; font-weight: 800;">
                    {{ substr($student->full_name, 0, 1) }}
                </div>
                <h4 class="fw-bold mb-1">{{ $student->full_name }}</h4>
                <p class="text-muted small mb-3">ID: {{ $student->registration_code }}</p>
                <div class="d-flex justify-content-center gap-2">
                    @php
                        $badgeClass = $student->status == 1 ? 'bg-success' : 'bg-danger';
                        $statusText = $student->status == 1 ? 'Aktif' : 'Non-Aktif';
                    @endphp
                    <span class="badge {{ $badgeClass }}">{{ $statusText }}</span>
                    <span class="badge bg-primary-subtle text-primary">{{ $student->program ?? '-' }}</span>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0 py-1">Kontak Siswa</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <div class="list-group-item px-4 py-3">
                        <div class="small text-muted mb-1">WhatsApp</div>
                        <div class="fw-semibold">{{ $student->whatsapp ?? '-' }}</div>
                    </div>
                    <div class="list-group-item px-4 py-3">
                        <div class="small text-muted mb-1">Email</div>
                        <div class="fw-semibold">{{ $student->email ?? '-' }}</div>
                    </div>
                    <div class="list-group-item px-4 py-3">
                        <div class="small text-muted mb-1">No. HP (Alternative)</div>
                        <div class="fw-semibold">{{ $student->phone ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white border-0 p-0">
                <ul class="nav nav-tabs nav-justified border-0" id="studentTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active py-3 fw-bold border-0" id="info-tab" data-bs-toggle="tab" data-bs-target="#info-content" type="button" role="tab">
                            <i class="bi bi-info-circle me-1"></i> Data Informasi
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-3 fw-bold border-0" id="schedule-tab" data-bs-toggle="tab" data-bs-target="#schedule-content" type="button" role="tab">
                            <i class="bi bi-calendar-event me-1"></i> Jadwal Belajar
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-3 fw-bold border-0" id="eval-tab" data-bs-toggle="tab" data-bs-target="#eval-content" type="button" role="tab">
                            <i class="bi bi-clipboard2-check me-1"></i> Evaluasi
                        </button>
                    </li>
                </ul>
            </div>
            <div class="tab-content" id="studentTabContent">
                <!-- Tab 1: Data Informasi -->
                <div class="tab-pane fade show active" id="info-content" role="tabpanel">
                    <div class="card-body px-4 py-4">
                        <div class="row g-4">
                            <div class="col-sm-6">
                                <label class="small text-muted d-block">NIS</label>
                                <span class="fw-semibold text-dark">{{ $student->nis ?? '-' }}</span>
                            </div>
                            <div class="col-sm-6">
                                <label class="small text-muted d-block">Nama Panggilan</label>
                                <span class="fw-semibold text-dark">{{ $student->nickname ?? '-' }}</span>
                            </div>
                            <div class="col-sm-6">
                                <label class="small text-muted d-block">Tanggal Lahir</label>
                                <span class="fw-semibold text-dark">{{ $student->birth_date ? \Carbon\Carbon::parse($student->birth_date)->format('d M Y') : '-' }}</span>
                            </div>
                            <div class="col-sm-6">
                                <label class="small text-muted d-block">Jenis Kelamin</label>
                                <span class="fw-semibold text-dark">{{ $student->gender == 'L' ? 'Laki-laki' : ($student->gender == 'P' ? 'Perempuan' : '-') }}</span>
                            </div>
                            <div class="col-sm-6">
                                <label class="small text-muted d-block">Kelas / Tingkat</label>
                                <span class="fw-semibold text-dark">{{ $student->grade ?? '-' }}</span>
                            </div>
                            <div class="col-sm-6">
                                <label class="small text-muted d-block">Asal Sekolah</label>
                                <span class="fw-semibold text-dark">{{ $student->school_origin ?? '-' }}</span>
                            </div>
                            <div class="col-sm-6">
                                <label class="small text-muted d-block">Pilihan Program</label>
                                <span class="fw-semibold text-dark">{{ $student->program ?? '-' }}</span>
                            </div>
                            <div class="col-sm-6">
                                <label class="small text-muted d-block">Sesi Belajar</label>
                                <span class="fw-semibold text-dark">{{ $student->scheduleSession->name ?? '-' }}</span>
                            </div>
                            <div class="col-12">
                                <label class="small text-muted d-block">Alamat Lengkap</label>
                                <span class="fw-semibold text-dark">{{ $student->address ?? '-' }}</span>
                            </div>
                        </div>

                        <hr class="my-4 opacity-50">

                        <h6 class="fw-bold mb-3">Informasi Orang Tua / Wali</h6>
                        <div class="row g-4">
                            <div class="col-sm-4">
                                <label class="small text-muted d-block">Nama Ayah</label>
                                <span class="fw-semibold text-dark">{{ $student->father_name ?? '-' }}</span>
                            </div>
                            <div class="col-sm-4">
                                <label class="small text-muted d-block">Nama Ibu</label>
                                <span class="fw-semibold text-dark">{{ $student->mother_name ?? '-' }}</span>
                            </div>
                            <div class="col-sm-4">
                                <label class="small text-muted d-block">Nama Wali</label>
                                <span class="fw-semibold text-dark">{{ $student->guardian_name ?? '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Jadwal Belajar (schedules baru) -->
                <div class="tab-pane fade" id="schedule-content" role="tabpanel">
                    <div class="card-body p-0">
                        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light-subtle">
                            <div>
                                <h6 class="mb-0 fw-bold">Daftar Jadwal Belajar</h6>
                                <small class="text-muted">Kuota sisa: <span class="fw-bold text-primary">{{ $student->quota_sessions ?? 0 }}</span> sesi</small>
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" id="btn-add-sched">
                                <i class="bi bi-plus-lg me-1"></i> Tambah Jadwal
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="student-schedules-table">
                                <thead class="table-light">
                                    <tr>
                                        <th class="px-4 py-3">Tanggal</th>
                                        <th class="py-3">Mata Pelajaran</th>
                                        <th class="py-3">Tutor</th>
                                        <th class="py-3">Jam</th>
                                        <th class="py-3 text-center">Status</th>
                                        <th class="py-3 text-center">Evaluasi</th>
                                        <th class="px-4 py-3 text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($schedules as $sched)
                                    <tr>
                                        <td class="px-4">
                                            <div class="fw-semibold">{{ \Carbon\Carbon::parse($sched->class_date)->translatedFormat('d M Y') }}</div>
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($sched->class_date)->translatedFormat('l') }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                {{ $sched->subject->subject_name ?? '-' }}
                                            </span>
                                        </td>
                                        <td>{{ $sched->tutor->name ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                {{ substr($sched->start_time, 0, 5) }} – {{ substr($sched->end_time, 0, 5) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $statusBadge = match($sched->status_schedule) {
                                                    'scheduled' => 'bg-primary',
                                                    'done'      => 'bg-success',
                                                    'canceled'  => 'bg-secondary',
                                                };
                                                $statusLabel = match($sched->status_schedule) {
                                                    'scheduled' => 'Dijadwalkan',
                                                    'done'      => 'Selesai',
                                                    'canceled'  => 'Dibatalkan',
                                                };
                                            @endphp
                                            <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($sched->status_schedule === 'done')
                                                @if($sched->evaluation)
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                        <i class="bi bi-check-circle me-1"></i>Sudah
                                                    </span>
                                                @else
                                                    <button class="btn btn-sm btn-outline-info btn-eval-student"
                                                        data-id="{{ $sched->id }}"
                                                        data-date="{{ \Carbon\Carbon::parse($sched->class_date)->translatedFormat('d M Y') }}"
                                                        data-subject="{{ $sched->subject->subject_name ?? '-' }}"
                                                        data-tutor="{{ $sched->tutor->name ?? '-' }}">
                                                        <i class="bi bi-clipboard2-check me-1"></i>Isi
                                                    </button>
                                                @endif
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 text-end">
                                            <div class="btn-group btn-group-sm">
                                                @if($sched->status_schedule === 'scheduled')
                                                    <button class="btn btn-outline-success btn-done-student" data-id="{{ $sched->id }}" title="Tandai Selesai">
                                                        <i class="bi bi-check-lg"></i>
                                                    </button>
                                                    <button class="btn btn-outline-secondary btn-cancel-student" data-id="{{ $sched->id }}" title="Batalkan">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                @endif
                                                @if($sched->status_schedule === 'done' && $sched->evaluation)
                                                    <button class="btn btn-outline-info btn-eval-student"
                                                        data-id="{{ $sched->id }}"
                                                        data-date="{{ \Carbon\Carbon::parse($sched->class_date)->translatedFormat('d M Y') }}"
                                                        data-subject="{{ $sched->subject->subject_name ?? '-' }}"
                                                        data-tutor="{{ $sched->tutor->name ?? '-' }}"
                                                        data-attendance="{{ $sched->evaluation->student_attendance }}"
                                                        data-score="{{ $sched->evaluation->score ?? '' }}"
                                                        data-notes="{{ $sched->evaluation->tutor_notes ?? '' }}"
                                                        title="Edit Evaluasi">
                                                        <i class="bi bi-clipboard2-check"></i>
                                                    </button>
                                                @endif
                                                <button class="btn btn-outline-danger btn-delete-student" data-id="{{ $sched->id }}" title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                                            Belum ada jadwal untuk siswa ini.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Evaluasi -->
                <div class="tab-pane fade" id="eval-content" role="tabpanel">
                    @php
                        $doneSchedules = $student->schedules->where('status_schedule', 'done');
                        $evalDone      = $doneSchedules->filter(fn($s) => $s->evaluation);
                        $scores        = $evalDone->filter(fn($s) => $s->evaluation->score !== null)->map(fn($s) => $s->evaluation->score);
                        $avgScore      = $scores->count() ? round($scores->avg(), 1) : null;
                    @endphp
                    <div class="card-body">
                        {{-- Mini stats --}}
                        <div class="row g-3 mb-4">
                            <div class="col-4">
                                <div class="text-center p-3 rounded bg-primary bg-opacity-10">
                                    <div class="fs-3 fw-bold text-primary">{{ $doneSchedules->count() }}</div>
                                    <div class="small text-muted">Sesi Selesai</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-center p-3 rounded bg-success bg-opacity-10">
                                    <div class="fs-3 fw-bold text-success">{{ $evalDone->count() }}</div>
                                    <div class="small text-muted">Sudah Dievaluasi</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-center p-3 rounded bg-info bg-opacity-10">
                                    <div class="fs-3 fw-bold text-info">{{ $avgScore ?? '—' }}</div>
                                    <div class="small text-muted">Rata-rata Nilai</div>
                                </div>
                            </div>
                        </div>
                        <div class="text-center">
                            <a href="{{ route('admin.evaluations.student', $student->id) }}" class="btn btn-outline-primary">
                                <i class="bi bi-clipboard2-data me-1"></i> Lihat Laporan Evaluasi Lengkap
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0 py-1">Riwayat Pembayaran</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4">No. Bayar</th>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th class="text-end px-4">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($student->payments()->latest()->limit(5)->get() as $payment)
                            <tr>
                                <td class="px-4">
                                    <a href="{{ route('admin.payments.show', $payment->id) }}" class="fw-bold">{{ $payment->no_payment }}</a>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') }}</td>
                                <td>
                                    @php
                                        $label = match($payment->category_payment) {
                                            1 => 'Registrasi',
                                            2 => 'SPP',
                                            3 => 'Kegiatan',
                                            default => '-'
                                        };
                                    @endphp
                                    {{ $label }}
                                </td>
                                <td class="text-end px-4 fw-bold">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">Belum ada riwayat pembayaran.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($student->payments()->count() > 5)
            <div class="card-footer bg-white text-center">
                <a href="{{ route('admin.payments.index') }}?student_id={{ $student->id }}" class="btn btn-sm btn-link text-decoration-none">Lihat Semua Riwayat</a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

{{-- Modal: Tambah / Edit Jadwal (siswa sudah terpilih) --}}
<div class="modal fade" id="modal-sched-student" tabindex="-1" aria-hidden="true">
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
                        <label class="form-label fw-semibold">Tutor <span class="text-danger">*</span></label>
                        <select id="sched-tutor" class="form-select">
                            <option value="">-- Pilih Tutor --</option>
                            @foreach($tutors as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Mata Pelajaran <span class="text-danger">*</span></label>
                        <select id="sched-subject" class="form-select">
                            <option value="">-- Pilih Mapel --</option>
                            @foreach($subjects as $sub)
                                <option value="{{ $sub->id }}">{{ $sub->subject_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" id="sched-date" class="form-control">
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
<div class="modal fade" id="modal-eval-student" tabindex="-1" aria-hidden="true">
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
                    <label class="form-label fw-semibold">Nilai (0–100)</label>
                    <input type="number" id="eval-score" class="form-control" min="0" max="100" placeholder="Kosongkan jika tidak ada kuis">
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

@push('js')
<script>
$(function () {
    var STUDENT_ID = {{ $student->id }};

    /* ---- Auto-fill jam dari sesi ---- */
    $('#sched-session').on('change', function () {
        var opt   = $(this).find('option:selected');
        var start = opt.data('start') || '';
        var end   = opt.data('end')   || '';
        $('#sched-start').val(start);
        $('#sched-end').val(end);
        if (start && end) {
            $('#sched-time-display').html('<i class="bi bi-clock me-1"></i>Jam: <strong>' + start + ' – ' + end + '</strong>');
        } else {
            $('#sched-time-display').text('');
        }
    });

    /* ---- Tambah Jadwal ---- */
    $('#btn-add-sched').on('click', function () {
        $('#sched-id').val('');
        $('#sched-tutor, #sched-subject').val('');
        $('#sched-date, #sched-start, #sched-end').val('');
        $('#sched-time-display').text('');
        $('#modal-sched-title').text('Tambah Jadwal – {{ $student->full_name }}');

        // Default ke sesi yang dipilih siswa saat pendaftaran
        var defaultSession = {{ $student->schedule_session_id ?? 'null' }};
        if (defaultSession) {
            $('#sched-session').val(defaultSession).trigger('change');
        } else {
            $('#sched-session').val('');
        }

        $('#modal-sched-student').modal('show');
    });

    /* ---- Simpan Jadwal ---- */
    $('#btn-save-sched').on('click', function () {
        var id   = $('#sched-id').val();
        var url  = id ? '/admin/schedules/' + id : '{{ route("admin.schedules.store") }}';
        var type = id ? 'PUT' : 'POST';

        $.ajax({
            url: url, type: type,
            data: {
                student_id: STUDENT_ID,
                tutor_id:   $('#sched-tutor').val(),
                subject_id: $('#sched-subject').val(),
                class_date: $('#sched-date').val(),
                start_time: $('#sched-start').val(),
                end_time:   $('#sched-end').val(),
                _token:     '{{ csrf_token() }}'
            },
            success: function (res) {
                $('#modal-sched-student').modal('hide');
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false })
                    .then(function () { location.reload(); });
            },
            error: function (xhr) {
                var msg = xhr.responseJSON?.message ?? 'Terjadi kesalahan.';
                Swal.fire('Gagal', msg, 'error');
            }
        });
    });

    /* ---- Tandai Selesai ---- */
    $(document).on('click', '.btn-done-student', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Tandai Sesi Selesai?',
            text: 'Kuota siswa akan berkurang 1 sesi.',
            icon: 'question', showCancelButton: true,
            confirmButtonText: 'Ya, Selesai', cancelButtonText: 'Batal'
        }).then(function (r) {
            if (r.isConfirmed) {
                $.ajax({
                    url: '/admin/schedules/' + id + '/status', type: 'PUT',
                    data: { status: 'done', _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false })
                            .then(function () { location.reload(); });
                    }
                });
            }
        });
    });

    /* ---- Batalkan ---- */
    $(document).on('click', '.btn-cancel-student', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Batalkan Jadwal?', icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#6c757d', confirmButtonText: 'Ya, Batalkan', cancelButtonText: 'Tidak'
        }).then(function (r) {
            if (r.isConfirmed) {
                $.ajax({
                    url: '/admin/schedules/' + id + '/status', type: 'PUT',
                    data: { status: 'canceled', _token: '{{ csrf_token() }}' },
                    success: function () { location.reload(); }
                });
            }
        });
    });

    /* ---- Hapus ---- */
    $(document).on('click', '.btn-delete-student', function () {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Hapus Jadwal?', text: 'Data jadwal akan dihapus permanen.',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus', cancelButtonText: 'Batal'
        }).then(function (r) {
            if (r.isConfirmed) {
                $.ajax({
                    url: '/admin/schedules/' + id, type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function () { location.reload(); }
                });
            }
        });
    });

    /* ---- Buka Modal Evaluasi ---- */
    $(document).on('click', '.btn-eval-student', function () {
        var btn  = $(this);
        var id   = btn.data('id');
        var date = btn.data('date') || '';
        var subj = btn.data('subject') || '';
        var tutr = btn.data('tutor') || '';

        $('#eval-sched-id').val(id);
        $('#eval-info-box').html('<b>Mapel:</b> ' + subj + ' &nbsp;|&nbsp; <b>Tutor:</b> ' + tutr + ' &nbsp;|&nbsp; <b>Tgl:</b> ' + date);

        // Reset
        $('input[name="eval_att"]').prop('checked', false);
        $('#eval-score').val('');
        $('#eval-notes').val('');

        // Pre-fill jika sudah ada evaluasi
        var att   = btn.data('attendance');
        var score = btn.data('score');
        var notes = btn.data('notes');
        if (att)   $('input[name="eval_att"][value="' + att + '"]').prop('checked', true);
        if (score) $('#eval-score').val(score);
        if (notes) $('#eval-notes').val(notes);

        $('#modal-eval-student').modal('show');
    });

    /* ---- Simpan Evaluasi ---- */
    $('#btn-save-eval').on('click', function () {
        var att = $('input[name="eval_att"]:checked').val();
        if (!att) { Swal.fire('Perhatian', 'Kehadiran siswa harus dipilih.', 'warning'); return; }

        $.ajax({
            url: '{{ route("admin.evaluations.store") }}', type: 'POST',
            data: {
                schedule_id:        $('#eval-sched-id').val(),
                student_attendance: att,
                score:              $('#eval-score').val() || null,
                tutor_notes:        $('#eval-notes').val(),
                _token:             '{{ csrf_token() }}'
            },
            success: function (res) {
                $('#modal-eval-student').modal('hide');
                Swal.fire({ icon: 'success', title: 'Berhasil', text: res.message, timer: 2000, showConfirmButton: false })
                    .then(function () { location.reload(); });
            },
            error: function (xhr) {
                Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Terjadi kesalahan.', 'error');
            }
        });
    });
});
</script>
@endpush
