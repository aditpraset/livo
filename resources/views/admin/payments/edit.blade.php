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
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Pembayaran <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control @error('payment_date') is-invalid @enderror" value="{{ old('payment_date', $payment->payment_date) }}" required>
                            @error('payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Expired</label>
                            <input type="date" name="expired_date" class="form-control @error('expired_date') is-invalid @enderror" value="{{ old('expired_date', $payment->expired_date) }}">
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
