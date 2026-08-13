@extends('siswa.layouts.app')

@section('title', 'Profil Saya - LIVO Siswa')

@section('content')
<div class="mb-4">
    <h1 class="fs-3 mb-1">Profil Saya</h1>
    <p class="text-muted mb-0">Data akademik dikelola oleh admin. Anda dapat memperbarui kontak, alamat, dan foto.</p>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <div id="photo-box">
                    @if($student->photo)
                        <img src="{{ asset('storage/' . $student->photo) }}" class="rounded-circle mb-3" style="width:120px;height:120px;object-fit:cover;">
                    @else
                        <span class="rounded-circle bg-secondary-subtle text-secondary d-inline-flex align-items-center justify-content-center mb-3" style="width:120px;height:120px;">
                            <i class="bi bi-person" style="font-size:3rem;"></i>
                        </span>
                    @endif
                </div>
                <h3 class="mb-1">{{ $student->full_name }}</h3>
                <p class="text-muted mb-2">{{ $student->email ?: '-' }}</p>
                @if($student->nis)
                    <span class="badge bg-secondary-subtle text-secondary">NIS {{ $student->nis }}</span>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-white"><h3 class="card-title fw-bold mb-0">Data Akademik</h3></div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-sm-4">Kelas / Tingkat</dt><dd class="col-sm-8">{{ $student->grade ?: '-' }}</dd>
                    <dt class="col-sm-4">Asal Sekolah</dt><dd class="col-sm-8">{{ $student->school_origin ?: '-' }}</dd>
                    <dt class="col-sm-4">Paket</dt><dd class="col-sm-8">{{ $student->package ?: '-' }}</dd>
                    <dt class="col-sm-4">Mata Pelajaran</dt><dd class="col-sm-8">{{ $student->program_label }}</dd>
                    <dt class="col-sm-4">Kurikulum</dt><dd class="col-sm-8">{{ $student->school_curriculum ?: '-' }}</dd>
                    <dt class="col-sm-4">Sisa Kuota Sesi</dt>
                    <dd class="col-sm-8"><span class="badge {{ ($student->quota_sessions ?? 0) > 2 ? 'bg-success' : 'bg-warning' }}">{{ $student->quota_sessions ?? 0 }}</span></dd>
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white"><h3 class="card-title fw-bold mb-0">Ubah Kontak</h3></div>
            <div class="card-body">
                <form action="{{ route('siswa.profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">No. Telp / HP</label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $student->phone) }}">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">No. WhatsApp</label>
                            <input type="text" name="whatsapp" class="form-control @error('whatsapp') is-invalid @enderror" value="{{ old('whatsapp', $student->whatsapp) }}">
                            @error('whatsapp') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">Isi Telp/HP atau WhatsApp.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Alamat</label>
                            <textarea name="address" rows="3" class="form-control @error('address') is-invalid @enderror">{{ old('address', $student->address) }}</textarea>
                            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
