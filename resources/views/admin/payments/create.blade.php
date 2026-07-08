@extends('admin.layouts.app')

@section('title', 'Tambah Pembayaran - LIVO Admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="mb-4">
            <a href="{{ route('admin.payments.index') }}" class="btn btn-link link-secondary ps-0">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
            <h1 class="fs-3 mb-1 mt-2">Tambah Pembayaran Baru</h1>
            <p class="text-muted mb-0">Isi form di bawah ini untuk menambahkan data pembayaran.</p>
        </div>
    </div>
</div>

<form action="{{ route('admin.payments.store') }}" method="POST" id="form-payment">
    @csrf
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
                            <input type="text" class="form-control bg-light" value="{{ $noPayment }}" readonly>
                            <small class="text-muted">Nomor dibuat otomatis oleh sistem.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Siswa <span class="text-danger">*</span></label>
                            <select name="student_id" class="form-select @error('student_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Siswa --</option>
                                @foreach($students as $student)
                                    <option value="{{ $student->id }}" data-duration="{{ $student->duration ?? '' }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>
                                        {{ $student->full_name }} {{ $student->nis ? '(' . $student->nis . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('student_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategori Pembayaran <span class="text-danger">*</span></label>
                            <select name="category_payment" class="form-select @error('category_payment') is-invalid @enderror" required>
                                <option value="1" {{ old('category_payment') == '1' ? 'selected' : '' }}>Registrasi</option>
                                <option value="2" {{ old('category_payment') == '2' ? 'selected' : '' }}>SPP</option>
                                <option value="3" {{ old('category_payment') == '3' ? 'selected' : '' }}>Kegiatan</option>
                                <option value="4" {{ old('category_payment') == '4' ? 'selected' : '' }}>Registrasi dan SPP</option>
                            </select>
                            @error('category_payment') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">Kuota sesi hanya bertambah untuk kategori <strong>SPP</strong> &amp; <strong>Registrasi dan SPP</strong>.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jumlah (Rp) <span class="text-danger">*</span></label>
                            <input type="number" id="payment-amount" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', 200000) }}" required>
                            @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small id="amount-auto-hint" class="text-success d-none"><i class="bi bi-magic me-1"></i>Nominal terisi otomatis dari master harga.</small>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
                            <input type="text" name="description" class="form-control @error('description') is-invalid @enderror" value="{{ old('description') }}" placeholder="Contoh: Pembayaran Pendaftaran - Matematika" required>
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
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Pembayaran <span class="text-danger">*</span></label>
                            <input type="date" id="payment-date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror" value="{{ old('payment_date', date('Y-m-d')) }}" required>
                            @error('payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Expired</label>
                            <input type="date" name="expired_date" class="form-control @error('expired_date') is-invalid @enderror" value="{{ old('expired_date') }}">
                            <small class="text-muted">Diisi sesuai kebutuhan (opsional).</small>
                            @error('expired_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
                            <select name="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
                                <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="transfer" {{ old('payment_method') == 'transfer' ? 'selected' : '' }}>Transfer</option>
                            </select>
                            @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kuota Sesi</label>
                            <input type="number" name="quota" class="form-control @error('quota') is-invalid @enderror" value="{{ old('quota', 8) }}">
                            @error('quota') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Dari (Nama Pembayar) <span class="text-danger">*</span></label>
                            <input type="text" name="from" class="form-control @error('from') is-invalid @enderror" value="{{ old('from') }}" placeholder="Nama orang yang membayar" required>
                            @error('from') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Diterima Oleh <span class="text-danger">*</span></label>
                            <input type="text" name="receiver" class="form-control @error('receiver') is-invalid @enderror" value="{{ old('receiver', auth()->user()->name) }}" required>
                            @error('receiver') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center py-4">
                    <i class="bi bi-cash-stack text-success" style="font-size: 3rem;"></i>
                    <h4 class="mt-3 mb-2">Simpan Pembayaran</h4>
                    <p class="text-muted small">Pastikan semua data sudah benar sebelum menyimpan.</p>
                    <button type="submit" class="btn btn-success w-100 mt-2">
                        <i class="bi bi-check-lg me-1"></i> Simpan Pembayaran
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

@push('js')
<script>
(function () {
    // Harga master per siswa (null jika kombinasi paket/program/jenjang/durasi tidak cocok)
    var studentPrices = @json($studentPrices);
    var studentSelect = document.querySelector('select[name="student_id"]');
    var amountInput   = document.getElementById('payment-amount');
    var hint          = document.getElementById('amount-auto-hint');
    if (!studentSelect || !amountInput) return;

    studentSelect.addEventListener('change', function () {
        var price = studentPrices[this.value];
        if (price !== null && price !== undefined && price !== '') {
            amountInput.value = price;
            if (hint) hint.classList.remove('d-none');
        } else {
            if (hint) hint.classList.add('d-none');
        }
        recalcExpired();
    });

    /* ── Auto-hitung Tanggal Expired (kelipatan 30 hari sesuai durasi paket) ──
       bayar tgl 1–10 → mulai tgl 1 bulan ini; tgl 11+ → mulai tgl 1 bulan berikutnya */
    var paymentDate  = document.getElementById('payment-date');
    var expiredDate  = document.getElementById('expired-date');
    var expiredHint  = document.getElementById('expired-auto-hint');

    function pad(n) { return (n < 10 ? '0' : '') + n; }

    function recalcExpired() {
        if (!paymentDate || !expiredDate) return;
        var opt = studentSelect.options[studentSelect.selectedIndex];
        var months = opt ? parseInt(opt.getAttribute('data-duration'), 10) : NaN;
        if (!paymentDate.value || !months || months < 1) {
            if (expiredHint) expiredHint.classList.add('d-none');
            return;
        }
        var d = new Date(paymentDate.value + 'T00:00:00');
        // Tanggal mulai = tgl 1 bulan ini / bulan berikutnya
        var startMonth = d.getDate() <= 10 ? d.getMonth() : d.getMonth() + 1;
        var start = new Date(d.getFullYear(), startMonth, 1);
        // Tambah kelipatan 30 hari sesuai durasi
        start.setDate(start.getDate() + months * 30);
        expiredDate.value = start.getFullYear() + '-' + pad(start.getMonth() + 1) + '-' + pad(start.getDate());
        if (expiredHint) expiredHint.classList.remove('d-none');
    }

    if (paymentDate) paymentDate.addEventListener('change', recalcExpired);
    recalcExpired();
})();
</script>
@endpush
@endsection
