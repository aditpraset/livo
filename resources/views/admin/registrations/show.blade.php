@extends('admin.layouts.app')

@section('title', 'Detail Pendaftaran - LIVO Admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="mb-4">
            <a href="{{ route('admin.registrations') }}" class="btn btn-link link-secondary ps-0">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <div>
                    <h1 class="fs-3 mb-1">Detail Pendaftaran: {{ $registration->full_name }}</h1>
                    <p class="text-muted mb-0">Kode: {{ $registration->registration_code }} | Tanggal: {{ $registration->created_at->format('d M Y H:i') }}</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-payment">
                        <i class="bi bi-cash me-1"></i> Pembayaran
                    </button>
                    @if($registration->status === 'Lunas')
                    <a href="{{ route('admin.registrations.receipt', $registration->id) }}" target="_blank" class="btn btn-outline-primary">
                        <i class="bi bi-printer me-1"></i> Cetak Kwitansi
                    </a>
                    @endif
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-status">
                        <i class="bi bi-pencil me-1"></i> Ubah Status
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h3 class="card-title fw-bold">Informasi Siswa</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="small text-muted d-block">Nama Lengkap</label>
                        <span class="fw-semibold">{{ $registration->full_name }}</span>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-muted d-block">Nama Panggilan</label>
                        <span class="fw-semibold">{{ $registration->nickname ?? '-' }}</span>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-muted d-block">NIS</label>
                        <span class="fw-semibold">{{ $registration->nis ?? '-' }}</span>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-muted d-block">Tanggal Lahir</label>
                        <span class="fw-semibold">{{ $registration->birth_date ?? '-' }}</span>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-muted d-block">Agama</label>
                        <span class="fw-semibold">{{ $registration->religion ?? '-' }}</span>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-muted d-block">Jenis Kelamin</label>
                        <span class="fw-semibold">{{ $registration->gender ?? '-' }}</span>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-muted d-block">Kelas</label>
                        <span class="fw-semibold">{{ $registration->grade ?? '-' }}</span>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-muted d-block">Asal Sekolah</label>
                        <span class="fw-semibold">{{ $registration->school_origin ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-white">
                <h3 class="card-title fw-bold">Informasi Orangtua / Wali</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="small text-muted d-block">Nama Ayah</label>
                        <span class="fw-semibold">{{ $registration->father_name ?? '-' }}</span>
                    </div>
                    <div class="col-md-4">
                        <label class="small text-muted d-block">Nama Ibu</label>
                        <span class="fw-semibold">{{ $registration->mother_name ?? '-' }}</span>
                    </div>
                    <div class="col-md-4">
                        <label class="small text-muted d-block">Nama Wali</label>
                        <span class="fw-semibold">{{ $registration->guardian_name ?? '-' }}</span>
                    </div>
                    <div class="col-12">
                        <label class="small text-muted d-block">Alamat</label>
                        <span class="fw-semibold">{{ $registration->address ?? '-' }}</span>
                    </div>
                    <div class="col-md-4">
                        <label class="small text-muted d-block">Email</label>
                        <span class="fw-semibold">{{ $registration->email ?? '-' }}</span>
                    </div>
                    <div class="col-md-4">
                        <label class="small text-muted d-block">No. WhatsApp</label>
                        <span class="fw-semibold text-primary">{{ $registration->whatsapp ?? '-' }}</span>
                    </div>
                    <div class="col-md-4">
                        <label class="small text-muted d-block">No. Telepon</label>
                        <span class="fw-semibold">{{ $registration->phone ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-white">
                <h3 class="card-title fw-bold">Data Pilihan Program</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="small text-muted d-block">Pilihan Kelas</label>
                        <span class="fw-semibold">{{ $registration->class_type ?? '-' }}</span>
                    </div>
                    <div class="col-md-4">
                        <label class="small text-muted d-block">Proses KBM</label>
                        <span class="fw-semibold">{{ $registration->kbm_process ?? '-' }}</span>
                    </div>
                    <div class="col-md-4">
                        <label class="small text-muted d-block">Program Belajar</label>
                        <span class="fw-semibold">{{ $registration->programMaster->program_name ?? '-' }}</span>
                    </div>
                    <div class="col-md-4">
                        <label class="small text-muted d-block">Jenjang</label>
                        <span class="fw-semibold">{{ $registration->gradeMaster->grade_name ?? '-' }}</span>
                    </div>
                    <div class="col-md-4">
                        <label class="small text-muted d-block">Durasi</label>
                        <span class="fw-semibold">{{ $registration->duration ? $registration->duration . ' Bulan' : '-' }}</span>
                    </div>
                    <div class="col-md-4">
                        <label class="small text-muted d-block">Paket</label>
                        <span class="fw-semibold">{{ $registration->packageMaster->package_name ?? ($registration->package ?? '-') }}</span>
                    </div>
                    <div class="col-md-12">
                        <label class="small text-muted d-block">Mata Pelajaran</label>
                        @forelse($registration->program_list as $prog)
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $prog }}</span>
                        @empty
                            <span class="text-muted">-</span>
                        @endforelse
                    </div>
                    <div class="col-md-12">
                        <label class="small text-muted d-block">Jadwal</label>
                        @forelse($registration->selected_schedules as $cs)
                            <span class="badge bg-info-subtle text-info border border-info-subtle">
                                {{ $cs->hari }} &mdash; {{ $cs->session->name ?? '-' }}
                                @if($cs->session)
                                    ({{ \Illuminate\Support\Str::substr($cs->session->time_start, 0, 5) }} - {{ \Illuminate\Support\Str::substr($cs->session->time_end, 0, 5) }})
                                @endif
                            </span>
                        @empty
                            <span class="fw-semibold">{{ $registration->selected_days ?? '-' }}</span>
                        @endforelse
                    </div>
                    <div class="col-md-6">
                        <label class="small text-muted d-block">Kurikulum Sekolah</label>
                        <span class="fw-semibold">{{ $registration->school_curriculum ?? '-' }}</span>
                    </div>
                    <div class="col-md-6">
                        <label class="small text-muted d-block">Catatan Khusus</label>
                        <span class="fw-semibold">{{ $registration->learning_material ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h3 class="card-title fw-bold">Status Pendaftaran</h3>
            </div>
            <div class="card-body text-center py-4">
                @php
                    $statusClass = match ($registration->status) {
                        'Lunas' => 'bg-success text-white',
                        'Belum Lunas' => 'bg-warning text-white',
                        default => 'bg-secondary text-white'
                    };
                @endphp
                <div class="h1 fw-bold mb-1">
                    <span class="badge {{ $statusClass }} px-4 py-2">{{ $registration->status }}</span>
                </div>
                <p class="text-muted">Status saat ini</p>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-white">
                <h3 class="card-title fw-bold">Informasi Tambahan</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="small text-muted d-block">Tahu Livo Dari</label>
                    <span class="fw-semibold">{{ $registration->registration_info ?? '-' }}</span>
                </div>
                <div class="mb-3">
                    <label class="small text-muted d-block">PIC Marketing</label>
                    <span class="fw-semibold">{{ $registration->marketing_pic ?? '-' }}</span>
                </div>
                <div>
                    <label class="small text-muted d-block">Kode Promo</label>
                    <span class="badge bg-info-subtle text-info fw-bold">{{ $registration->promo_code ?? 'TIDAK ADA' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Update Status --}}
<div class="modal modal-blur fade" id="modal-status" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <form action="{{ route('admin.registrations.update-status', $registration->id) }}" method="POST" class="modal-content">
            @csrf
            @method('PATCH')
            <div class="modal-header">
                <h5 class="modal-title">Ubah Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div class="mb-3">
                    <label class="form-label">Pilih Status Baru</label>
                    <select name="status" class="form-select" required>
                        <option value="Baru" {{ $registration->status == 'Baru' ? 'selected' : '' }}>Baru</option>
                        <option value="Belum Lunas" {{ $registration->status == 'Belum Lunas' ? 'selected' : '' }}>Belum Lunas</option>
                        <option value="Lunas" {{ $registration->status == 'Lunas' ? 'selected' : '' }}>Lunas</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Pembayaran --}}
<div class="modal modal-blur fade" id="modal-payment" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('admin.registrations.store-payment', $registration->id) }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Form Pembayaran Pendaftaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">No Pembayaran</label>
                        <input type="text" class="form-control bg-light text-muted" name="no_payment_preview" value="{{ 'LVR-'.date('ymd').sprintf('%04d', \App\Models\Payment::whereDate('created_at', today())->count() + 1) }}" readonly>
                        <small class="text-muted">Nomor ini dibuat otomatis oleh sistem.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kategori Pembayaran</label>
                        <select name="category_payment" id="reg-pay-category" class="form-select" required>
                            <option value="1" selected>Registrasi</option>
                            <option value="4">Registrasi dan SPP</option>
                        </select>
                        <small class="text-muted">Kuota sesi hanya bertambah untuk <strong>Registrasi dan SPP</strong>.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Pembayaran</label>
                        <input type="date" class="form-control" name="payment_date" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Expired</label>
                        <input type="date" class="form-control" name="expired_date" value="{{ date('Y-m-d', strtotime('+1 month')) }}">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Deskripsi</label>
                        <input type="text" class="form-control" name="description" value="Pembayaran Pendaftaran - {{ $registration->package ?: $registration->program_label }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jumlah (Rp)</label>
                        <input type="number" class="form-control" name="amount" value="{{ $autoAmount ?? 200000 }}" required>
                        @if(!is_null($autoAmount))
                            <small class="text-success"><i class="bi bi-magic me-1"></i>Nominal terisi otomatis dari master harga.</small>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Metode Pembayaran</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash">Cash</option>
                            <option value="transfer">Transfer</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Dari (Nama Pembayar)</label>
                        <input type="text" class="form-control" name="from" value="{{ $registration->father_name ?? $registration->mother_name ?? $registration->guardian_name ?? $registration->full_name }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Diterima Oleh</label>
                        <input type="text" class="form-control" name="receiver" value="{{ auth()->user()->name }}" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Kuota Sesi</label>
                        <input type="number" id="reg-pay-quota" class="form-control" name="quota" value="8">
                        <small id="reg-pay-quota-hint" class="text-muted d-none">Kategori "Registrasi" tidak menambah kuota sesi.</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success">Simpan Pembayaran</button>
            </div>
        </form>
    </div>
</div>

@push('js')
<script>
(function () {
    var cat  = document.getElementById('reg-pay-category');
    var qty  = document.getElementById('reg-pay-quota');
    var hint = document.getElementById('reg-pay-quota-hint');
    if (!cat || !qty) return;
    function sync() {
        var addsQuota = (cat.value === '4'); // hanya Registrasi dan SPP yang menambah kuota
        qty.disabled = !addsQuota;
        if (hint) hint.classList.toggle('d-none', addsQuota);
    }
    cat.addEventListener('change', sync);
    sync();
})();
</script>
@endpush
@endsection
