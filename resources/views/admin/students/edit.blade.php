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

<form action="{{ route('admin.students.update', $student->id) }}" method="POST">
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
@endsection
