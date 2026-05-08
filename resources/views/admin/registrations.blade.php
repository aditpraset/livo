@extends('admin.layouts.app')

@section('title', 'Data Pendaftaran - LIVO Admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="mb-6">
            <h1 class="fs-3 mb-1">Data Pendaftaran Siswa</h1>
            <p>Kelola semua data pendaftaran siswa LIVO.</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center px-4 py-3">
                <h4 class="mb-0 h5">Semua Pendaftaran</h4>
                <span class="badge bg-primary">{{ $registrations->total() }} data</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4">#</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Program</th>
                            <th>Proses KBM</th>
                            <th>No. WhatsApp</th>
                            <th>Asal Sekolah</th>
                            <th>Tanggal Daftar</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($registrations as $i => $reg)
                        <tr>
                            <td class="px-4">{{ $registrations->firstItem() + $i }}</td>
                            <td>
                                <div class="fw-semibold">{{ $reg->full_name }}</div>
                                <small class="text-muted">{{ $reg->nickname ?? '' }}</small>
                            </td>
                            <td>{{ $reg->class_type ?? '-' }}</td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary">{{ $reg->program ?? '-' }}</span>
                            </td>
                            <td>{{ $reg->kbm_process ?? '-' }}</td>
                            <td>{{ $reg->whatsapp ?? $reg->phone ?? '-' }}</td>
                            <td>{{ $reg->school_origin ?? '-' }}</td>
                            <td>
                                <small>{{ $reg->created_at->format('d M Y') }}</small>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#detailModal{{ $reg->id }}">
                                    <i class="ti ti-eye"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">Belum ada data pendaftaran.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($registrations->hasPages())
            <div class="card-footer bg-white px-4 py-3">
                {{ $registrations->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Detail Modals --}}
@foreach($registrations as $reg)
<div class="modal fade" id="detailModal{{ $reg->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Pendaftaran — {{ $reg->full_name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    {{-- Informasi Siswa --}}
                    <div class="col-12"><h6 class="fw-bold border-bottom pb-2">Informasi Siswa</h6></div>
                    <div class="col-md-4"><small class="text-muted d-block">Nama Lengkap</small><strong>{{ $reg->full_name }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Nama Panggilan</small><strong>{{ $reg->nickname ?? '-' }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Tanggal Lahir</small><strong>{{ $reg->birth_date ?? '-' }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Agama</small><strong>{{ $reg->religion ?? '-' }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Jenis Kelamin</small><strong>{{ $reg->gender ?? '-' }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Kelas</small><strong>{{ $reg->grade ?? '-' }}</strong></div>
                    <div class="col-md-6"><small class="text-muted d-block">Asal Sekolah</small><strong>{{ $reg->school_origin ?? '-' }}</strong></div>

                    {{-- Informasi Orang Tua --}}
                    <div class="col-12 mt-3"><h6 class="fw-bold border-bottom pb-2">Informasi Orangtua / Wali</h6></div>
                    <div class="col-md-4"><small class="text-muted d-block">Nama Ayah</small><strong>{{ $reg->father_name ?? '-' }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Nama Ibu</small><strong>{{ $reg->mother_name ?? '-' }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Nama Wali</small><strong>{{ $reg->guardian_name ?? '-' }}</strong></div>
                    <div class="col-12"><small class="text-muted d-block">Alamat</small><strong>{{ $reg->address ?? '-' }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Email</small><strong>{{ $reg->email ?? '-' }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">No. Telp / HP</small><strong>{{ $reg->phone ?? '-' }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">WhatsApp</small><strong>{{ $reg->whatsapp ?? '-' }}</strong></div>

                    {{-- Data Program --}}
                    <div class="col-12 mt-3"><h6 class="fw-bold border-bottom pb-2">Data Pilihan Program</h6></div>
                    <div class="col-md-4"><small class="text-muted d-block">Pilihan Kelas</small><strong>{{ $reg->class_type ?? '-' }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Proses KBM</small><strong>{{ $reg->kbm_process ?? '-' }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Paket</small><strong>{{ $reg->package ?? '-' }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Program</small><strong>{{ $reg->program ?? '-' }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Pilihan Hari</small><strong>{{ $reg->selected_days ?? '-' }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Sesi Belajar</small><strong>{{ $reg->study_session ?? '-' }}</strong></div>
                    <div class="col-md-6"><small class="text-muted d-block">Kurikulum Sekolah</small><strong>{{ $reg->school_curriculum ?? '-' }}</strong></div>
                    <div class="col-md-6"><small class="text-muted d-block">Materi Pembelajaran</small><strong>{{ $reg->learning_material ?? '-' }}</strong></div>

                    {{-- Promo --}}
                    <div class="col-12 mt-3"><h6 class="fw-bold border-bottom pb-2">Informasi Pendaftaran & Promo</h6></div>
                    <div class="col-md-4"><small class="text-muted d-block">Kode Promo</small><strong>{{ $reg->promo_code ?? '-' }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Info Pendaftaran</small><strong>{{ $reg->registration_info ?? '-' }}</strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">PIC Marketing</small><strong>{{ $reg->marketing_pic ?? '-' }}</strong></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endforeach
@endsection
