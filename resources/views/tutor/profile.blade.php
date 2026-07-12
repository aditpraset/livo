@extends('tutor.layouts.app')

@section('title', 'Profil Saya - LIVO Tutor')

@section('content')
<div class="mb-4">
    <h1 class="fs-3 mb-1">Profil Saya</h1>
    <p class="text-muted mb-0">Data diri Anda sebagai tutor. Perubahan nama & spesialisasi dilakukan oleh admin.</p>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <div id="photo-box">
                    @if($tutor->photo)
                        <img src="{{ asset('storage/' . $tutor->photo) }}" class="rounded-circle mb-3" style="width:120px;height:120px;object-fit:cover;">
                    @else
                        <span class="rounded-circle bg-secondary-subtle text-secondary d-inline-flex align-items-center justify-content-center mb-3" style="width:120px;height:120px;">
                            <i class="bi bi-person" style="font-size:3rem;"></i>
                        </span>
                    @endif
                </div>
                <h3 class="mb-1">{{ $tutor->name }}</h3>
                <p class="text-muted mb-2">{{ $tutor->email ?: '-' }}</p>
                <div>
                    @foreach((array) $tutor->specialization as $spec)
                        <span class="badge bg-primary-subtle text-primary me-1">{{ $spec }}</span>
                    @endforeach
                </div>
                @if($tutor->fee_per_session)
                    <div class="mt-3 small text-muted">Fee per sesi</div>
                    <div class="fw-bold">Rp {{ number_format($tutor->fee_per_session, 0, ',', '.') }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white"><h3 class="card-title fw-bold mb-0">Ubah Data Kontak</h3></div>
            <div class="card-body">
                <form action="{{ route('tutor.profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control bg-light" value="{{ $tutor->name }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email (untuk login)</label>
                            <input type="text" class="form-control bg-light" value="{{ $tutor->email }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">No. HP <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $tutor->phone) }}" required>
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">No. Rekening</label>
                            <input type="text" name="no_rekening" class="form-control @error('no_rekening') is-invalid @enderror" value="{{ old('no_rekening', $tutor->no_rekening) }}" placeholder="Bank - No Rekening a.n. ...">
                            @error('no_rekening') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Foto Profil</label>
                            <input type="file" id="photo-input" name="photo" accept="image/*" class="form-control @error('photo') is-invalid @enderror">
                            @error('photo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">Maks 5 MB. Preview tampil di kiri, tersimpan setelah klik Simpan.</small>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
(function () {
    var input = document.getElementById('photo-input');
    var box = document.getElementById('photo-box');
    if (!input || !box) return;

    input.addEventListener('change', function () {
        var file = this.files && this.files[0];
        if (!file || !file.type.startsWith('image/')) return;
        if (file.size > 5 * 1024 * 1024) {
            Swal.fire('Ukuran terlalu besar', 'Maksimal 5 MB.', 'warning');
            this.value = '';
            return;
        }
        box.innerHTML = '';
        var img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.className = 'rounded-circle mb-3';
        img.style.cssText = 'width:120px;height:120px;object-fit:cover;';
        box.appendChild(img);
    });
})();
</script>
@endpush
