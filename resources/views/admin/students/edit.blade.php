@extends('admin.layouts.app')

@section('title', 'Edit Siswa - LIVO Admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="mb-4">
            <a href="{{ route('admin.students.index') }}" class="btn btn-link link-secondary ps-0">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
            <h1 class="fs-3 mb-1 mt-2">Edit Data Siswa</h1>
            <p class="text-muted mb-0">Perbarui informasi profil siswa di bawah ini.</p>
        </div>
    </div>
</div>

<form action="{{ route('admin.students.update', $student->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="row g-4">
        <div class="col-md-8">
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Informasi Pribadi</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror" value="{{ old('full_name', $student->full_name) }}" required>
                            @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Panggilan</label>
                            <input type="text" name="nickname" class="form-control @error('nickname') is-invalid @enderror" value="{{ old('nickname', $student->nickname) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">NIS</label>
                            <input type="text" name="nis" class="form-control @error('nis') is-invalid @enderror" value="{{ old('nis', $student->nis) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="1" {{ old('status', $student->status) == 1 ? 'selected' : '' }}>Aktif</option>
                                <option value="2" {{ old('status', $student->status) == 2 ? 'selected' : '' }}>Non-Aktif</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Program</label>
                            <input type="text" name="program" class="form-control @error('program') is-invalid @enderror" value="{{ old('program', $student->program) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kelas / Tingkat</label>
                            <input type="text" name="grade" class="form-control @error('grade') is-invalid @enderror" value="{{ old('grade', $student->grade) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sekolah</label>
                            <input type="text" name="school" class="form-control @error('school') is-invalid @enderror" value="{{ old('school', $student->school) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Kontak & Alamat</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">WhatsApp</label>
                            <input type="text" name="whatsapp" class="form-control @error('whatsapp') is-invalid @enderror" value="{{ old('whatsapp', $student->whatsapp) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $student->email) }}">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Alamat</label>
                            <textarea name="address" class="form-control @error('address') is-invalid @enderror" rows="3">{{ old('address', $student->address) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Foto Siswa</h5>
                </div>
                <div class="card-body text-center">
                    <img id="photo-preview"
                        src="{{ $student->photo ? asset('storage/' . $student->photo) : '' }}"
                        alt="Foto Siswa"
                        class="rounded mb-3 {{ $student->photo ? '' : 'd-none' }}"
                        style="width: 140px; height: 140px; object-fit: cover;">
                    @unless($student->photo)
                        <div id="photo-placeholder" class="rounded bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 140px; height: 140px;">
                            <i class="bi bi-image text-muted" style="font-size: 2.5rem;"></i>
                        </div>
                    @endunless
                    <input type="file" name="photo" id="photo-input"
                        class="form-control @error('photo') is-invalid @enderror" accept="image/*">
                    <small class="text-muted d-block mt-1">Semua tipe foto, maksimal 5 MB.</small>
                    @error('photo') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="card border-0 shadow-sm sticky-top" style="top: 2rem;">
                <div class="card-body py-4 text-center">
                    <i class="bi bi-person-check text-success" style="font-size: 3rem;"></i>
                    <h4 class="mt-3 mb-2">Simpan Perubahan</h4>
                    <p class="text-muted small">Klik tombol di bawah ini untuk memperbarui profil siswa.</p>
                    <button type="submit" class="btn btn-primary w-100 mt-2">
                        <i class="bi bi-check2-circle me-1"></i> Update Data Siswa
                    </button>
                    <a href="{{ route('admin.students.index') }}" class="btn btn-link link-secondary w-100 mt-2">Batal</a>
                </div>
            </div>
        </div>
    </div>
</form>

@push('js')
<script>
document.getElementById('photo-input')?.addEventListener('change', function (e) {
    var file = e.target.files[0];
    if (!file) return;
    var img = document.getElementById('photo-preview');
    var ph  = document.getElementById('photo-placeholder');
    img.src = URL.createObjectURL(file);
    img.classList.remove('d-none');
    if (ph) ph.classList.add('d-none');
});
</script>
@endpush
@endsection
