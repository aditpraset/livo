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
                            <i class="bi bi-calendar-event me-1"></i> Jadwal Siswa
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

                <!-- Tab 2: Jadwal Siswa -->
                <div class="tab-pane fade" id="schedule-content" role="tabpanel">
                    <div class="card-body p-0">
                        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light-subtle">
                            <h6 class="mb-0 fw-bold">Daftar Jadwal Belajar</h6>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-schedule" onclick="addSchedule()">
                                <i class="bi bi-plus-lg me-1"></i> Tambah Jadwal
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="px-4 py-3">Hari</th>
                                        <th class="py-3">Sesi</th>
                                        <th class="py-3">Jam</th>
                                        <th class="py-3">Catatan</th>
                                        <th class="px-4 py-3 text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($student->scheduleStudents as $schedule)
                                    <tr>
                                        <td class="px-4 fw-bold text-primary">{{ $schedule->date }}</td>
                                        <td>{{ $schedule->scheduleSession->name ?? '-' }}</td>
                                        <td>
                                            @if($schedule->scheduleSession)
                                                <span class="badge bg-light text-dark border">
                                                    {{ date('H:i', strtotime($schedule->scheduleSession->time_start)) }} - {{ date('H:i', strtotime($schedule->scheduleSession->time_end)) }}
                                                </span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="text-muted small">{{ $schedule->notes ?? '-' }}</td>
                                        <td class="px-4 text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-warning" onclick="editSchedule({{ $schedule->id }})">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <form action="{{ route('admin.schedules.destroy', $schedule->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jadwal ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                                            Belum ada jadwal yang diatur untuk siswa ini.
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

{{-- Modal Schedule --}}
<div class="modal fade" id="modal-schedule" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="" method="POST" id="form-schedule" class="modal-content">
            @csrf
            <div id="method-field"></div>
            <div class="modal-header">
                <h5 class="modal-title" id="modal-schedule-title">Tambah Jadwal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Hari <span class="text-danger">*</span></label>
                        <select name="date" class="form-select" id="field-date" required>
                            <option value="">-- Pilih Hari --</option>
                            @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $day)
                                <option value="{{ $day }}">{{ $day }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Sesi Belajar <span class="text-danger">*</span></label>
                        <select name="schedule_session_id" class="form-select" id="field-session" required>
                            <option value="">-- Pilih Sesi --</option>
                            @foreach($scheduleSessions as $session)
                                <option value="{{ $session->id }}">{{ $session->name }} ({{ date('H:i', strtotime($session->time_start)) }} - {{ date('H:i', strtotime($session->time_end)) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" id="field-notes" rows="2" placeholder="Contoh: Pertemuan ke-1"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Jadwal</button>
            </div>
        </form>
    </div>
</div>

@push('js')
<script>
    function addSchedule() {
        $('#modal-schedule-title').text('Tambah Jadwal');
        $('#form-schedule').attr('action', "{{ route('admin.students.schedules.store', $student->id) }}");
        $('#method-field').html('');
        $('#field-date').val('');
        $('#field-session').val('');
        $('#field-notes').val('');
    }

    function editSchedule(id) {
        $('#modal-schedule-title').text('Edit Jadwal');
        $('#form-schedule').attr('action', "/admin/schedules/" + id);
        $('#method-field').html('@method("PUT")');
        
        // Fetch data
        $.get("/admin/schedules/" + id, function(data) {
            $('#field-date').val(data.date);
            $('#field-session').val(data.schedule_session_id);
            $('#field-notes').val(data.notes);
            $('#modal-schedule').modal('show');
        });
    }
</script>
@endpush
