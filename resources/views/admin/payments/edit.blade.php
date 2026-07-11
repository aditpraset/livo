@extends('admin.layouts.app')

@section('title', 'Edit Pembayaran - LIVO Admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="mb-4">
            <a href="{{ route('admin.payments.index') }}" class="btn btn-link link-secondary ps-0">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
            <h1 class="fs-3 mb-1 mt-2">Edit Pembayaran: {{ $payment->no_payment }}</h1>
            <p class="text-muted mb-0">Perbarui data pembayaran di bawah ini.</p>
        </div>
    </div>
</div>

<form action="{{ route('admin.payments.update', $payment->id) }}" method="POST" id="form-payment">
    @csrf
    @method('PUT')
    <div class="row g-4">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h3 class="card-title fw-bold">Informasi Pembayaran</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">No Pembayaran</label>
                            <input type="text" class="form-control bg-light" value="{{ $payment->no_payment }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Siswa <span class="text-danger">*</span></label>
                            <select name="student_id" class="form-select @error('student_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Siswa --</option>
                                @foreach($students as $student)
                                    <option value="{{ $student->id }}" {{ old('student_id', $payment->student_id) == $student->id ? 'selected' : '' }}>
                                        {{ $student->full_name }} {{ $student->nis ? '(' . $student->nis . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('student_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategori Pembayaran <span class="text-danger">*</span></label>
                            <select name="category_payment" class="form-select @error('category_payment') is-invalid @enderror" required>
                                <option value="1" {{ old('category_payment', $payment->category_payment) == 1 ? 'selected' : '' }}>Registrasi</option>
                                <option value="2" {{ old('category_payment', $payment->category_payment) == 2 ? 'selected' : '' }}>SPP</option>
                                <option value="3" {{ old('category_payment', $payment->category_payment) == 3 ? 'selected' : '' }}>Kegiatan</option>
                                <option value="4" {{ old('category_payment', $payment->category_payment) == 4 ? 'selected' : '' }}>Registrasi dan SPP</option>
                            </select>
                            @error('category_payment') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jumlah (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $payment->amount) }}" required>
                            @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
                            <input type="text" name="description" class="form-control @error('description') is-invalid @enderror" value="{{ old('description', $payment->description) }}" required>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h3 class="card-title fw-bold">Detail Pembayaran</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Pembayaran <span class="text-danger">*</span></label>
                            <input type="date" id="payment-date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror" value="{{ old('payment_date', \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d')) }}" required>
                            @error('payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Aktif Pembelajaran <span class="text-danger">*</span></label>
                            <input type="date" id="active-date" name="active_date" class="form-control @error('active_date') is-invalid @enderror" value="{{ old('active_date', $payment->active_date ? \Carbon\Carbon::parse($payment->active_date)->format('Y-m-d') : \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d')) }}" required>
                            @error('active_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Periode <span class="text-danger">*</span></label>
                            <select name="period" id="period" class="form-select @error('period') is-invalid @enderror" required>
                                <option value="1" {{ old('period', $payment->period ?? 1) == 1 ? 'selected' : '' }}>1 Bulan (30 hari)</option>
                                <option value="2" {{ old('period', $payment->period) == 2 ? 'selected' : '' }}>2 Bulan (60 hari)</option>
                                <option value="3" {{ old('period', $payment->period) == 3 ? 'selected' : '' }}>3 Bulan (90 hari)</option>
                            </select>
                            @error('period') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Masa Aktif (Hari)</label>
                            <input type="number" id="masa-aktif" name="masa_aktif" min="0" class="form-control" value="{{ old('masa_aktif', $payment->masa_aktif) }}" placeholder="cth: 30">
                            <small class="text-muted">Default dari tanggal aktif s/d expired, bisa diubah manual.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Expired</label>
                            <input type="date" id="expired-date" name="expired_date" class="form-control @error('expired_date') is-invalid @enderror" value="{{ old('expired_date', $payment->expired_date ? \Carbon\Carbon::parse($payment->expired_date)->format('Y-m-d') : '') }}">
                            <small class="text-muted d-block">Otomatis mengikuti periode, bisa diubah.</small>
                            <small id="expired-warning" class="text-warning d-none"><i class="bi bi-exclamation-triangle-fill me-1"></i>Tanggal expired tidak berada di akhir bulan.</small>
                            @error('expired_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
                                <option value="cash" {{ old('payment_method', $payment->payment_method) == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="transfer" {{ old('payment_method', $payment->payment_method) == 'transfer' ? 'selected' : '' }}>Transfer</option>
                            </select>
                            @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kuota Sesi</label>
                            <input type="number" name="quota" class="form-control @error('quota') is-invalid @enderror" value="{{ old('quota', $payment->quota) }}">
                            @error('quota') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Dari (Nama Pembayar) <span class="text-danger">*</span></label>
                            <input type="text" name="from" class="form-control @error('from') is-invalid @enderror" value="{{ old('from', $payment->from) }}" required>
                            @error('from') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Diterima Oleh <span class="text-danger">*</span></label>
                            <input type="text" name="receiver" class="form-control @error('receiver') is-invalid @enderror" value="{{ old('receiver', $payment->receiver) }}" required>
                            @error('receiver') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center py-4">
                    <i class="bi bi-pencil-square text-warning" style="font-size: 3rem;"></i>
                    <h4 class="mt-3 mb-2">Update Pembayaran</h4>
                    <p class="text-muted small">Pastikan semua data sudah benar sebelum menyimpan.</p>
                    <button type="submit" class="btn btn-warning w-100 mt-2">
                        <i class="bi bi-check-lg me-1"></i> Update Pembayaran
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('js')
<script>
(function () {
    /* ── Auto-hitung Tanggal Expired dari Tanggal Aktif Pembelajaran + Periode ──
       1 bulan = +30 hari; periode selain 1 bulan → expired ditetapkan di akhir bulan hasil penambahan */
    var activeDate   = document.getElementById('active-date');
    var periodSelect = document.getElementById('period');
    var expiredDate  = document.getElementById('expired-date');
    var masaAktif    = document.getElementById('masa-aktif');
    var expiredWarn  = document.getElementById('expired-warning');
    if (!activeDate || !periodSelect || !expiredDate || !masaAktif) return;

    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function fmt(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }

    // Warning bila tanggal expired bukan akhir bulan
    function checkEndOfMonth() {
        if (!expiredWarn) return;
        if (!expiredDate.value) { expiredWarn.classList.add('d-none'); return; }
        var e = new Date(expiredDate.value + 'T00:00:00');
        var lastDay = new Date(e.getFullYear(), e.getMonth() + 1, 0).getDate();
        expiredWarn.classList.toggle('d-none', e.getDate() === lastDay);
    }

    // Hitung selisih hari expired - tanggal aktif → isi Masa Aktif (default, bisa diubah manual)
    function syncMasaFromExpired() {
        if (!activeDate.value || !expiredDate.value) { masaAktif.value = ''; return; }
        var a = new Date(activeDate.value + 'T00:00:00');
        var e = new Date(expiredDate.value + 'T00:00:00');
        var days = Math.round((e - a) / 86400000);
        masaAktif.value = days >= 0 ? days : '';
    }

    function recalcExpired() {
        var months = parseInt(periodSelect.value, 10);
        if (!activeDate.value || isNaN(months) || months < 1) return;
        var e = new Date(activeDate.value + 'T00:00:00');
        e.setDate(e.getDate() + months * 30);
        if (months !== 1) {
            e = new Date(e.getFullYear(), e.getMonth() + 1, 0); // akhir bulan
        }
        expiredDate.value = fmt(e);
        syncMasaFromExpired();
        checkEndOfMonth();
    }

    activeDate.addEventListener('change', recalcExpired);
    periodSelect.addEventListener('change', recalcExpired);
    expiredDate.addEventListener('change', function () { syncMasaFromExpired(); checkEndOfMonth(); });

    checkEndOfMonth(); // cek nilai awal tanpa menimpa data tersimpan
})();
</script>
@endpush
