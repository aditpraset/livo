@extends('admin.layouts.app')

@section('title', 'Admin Dashboard')

@section('page-header')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Overview</div>
                <h1 class="page-title">Dashboard</h1>
            </div>
            <!-- Page title actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <span class="d-none d-sm-inline">
                        <a href="#" class="btn btn-white"> New view </a>
                    </span>
                    <a href="#" class="btn btn-primary d-none d-sm-inline-block" data-bs-toggle="modal" data-bs-target="#modal-report">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" /></svg>
                        Create new report
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="container-xl">
    <!-- Stats Cards -->
    <div class="row g-3 mb-3">
        <div class="col-lg-3 col-sm-6 col-12">
            <div class="card p-3 bg-primary-subtle border-0 rounded-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md bg-primary text-white rounded-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-users" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" /><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /><path d="M21 21v-2a4 4 0 0 0 -3 -3.85" /></svg>
                    </div>
                    <div>
                        <div class="subheader text-primary mb-1">Total Pendaftaran</div>
                        <div class="h3 fw-bold mb-0">{{ $totalRegistrations ?? 0 }}</div>
                        <div class="small text-muted">Siswa terdaftar</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6 col-12">
            <div class="card p-3 bg-success-subtle border-0 rounded-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md bg-success text-white rounded-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-calculator" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 3m0 2a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2z" /><path d="M8 7m0 1a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1v1a1 1 0 0 1 -1 1h-6a1 1 0 0 1 -1 -1z" /><path d="M8 14l0 .01" /><path d="M12 14l0 .01" /><path d="M16 14l0 .01" /><path d="M8 17l0 .01" /><path d="M12 17l0 .01" /><path d="M16 17l0 .01" /></svg>
                    </div>
                    <div>
                        <div class="subheader text-success mb-1">Program Matematika</div>
                        <div class="h3 fw-bold mb-0">{{ $mathCount ?? 0 }}</div>
                        <div class="small text-muted">Siswa aktif</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6 col-12">
            <div class="card p-3 bg-info-subtle border-0 rounded-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md bg-info text-white rounded-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-language" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 5h7" /><path d="M9 3v2c0 4.418 -2.239 8 -5 8" /><path d="M5 9c0 2.144 2.952 3.908 6.7 4" /><path d="M12 20l4 -9l4 9" /><path d="M19.1 18h-6.2" /></svg>
                    </div>
                    <div>
                        <div class="subheader text-info mb-1">Program B. Inggris</div>
                        <div class="h3 fw-bold mb-0">{{ $englishCount ?? 0 }}</div>
                        <div class="small text-muted">Siswa aktif</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6 col-12">
            <div class="card p-3 bg-warning-subtle border-0 rounded-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md bg-warning text-white rounded-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-calendar" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z" /><path d="M16 3v4" /><path d="M8 3v4" /><path d="M4 11h16" /><path d="M11 15h1" /><path d="M12 15v3" /></svg>
                    </div>
                    <div>
                        <div class="subheader text-warning mb-1">Pendaftaran Bulan Ini</div>
                        <div class="h3 fw-bold mb-0">{{ $monthlyRegistrations ?? 0 }}</div>
                        <div class="small text-muted">Bulan {{ date('F Y') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Registrations -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center px-4 py-3 border-bottom-0">
                    <h3 class="card-title fw-bold">Pendaftaran Terbaru</h3>
                    <a href="{{ route('admin.registrations') }}" class="btn btn-sm btn-outline-primary rounded-2">
                        Lihat Semua <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-arrow-right ms-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l14 0" /><path d="M13 18l6 -6" /><path d="M13 6l6 6" /></svg>
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter table-mobile-md card-table">
                        <thead>
                            <tr>
                                <th class="ps-4">Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Program</th>
                                <th>No. WhatsApp</th>
                                <th class="pe-4">Tanggal Daftar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentRegistrations ?? [] as $reg)
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-sm me-3 rounded-circle bg-primary-subtle text-primary fw-bold">{{ substr($reg->full_name, 0, 1) }}</span>
                                        <div class="flex-fill">
                                            <div class="fw-bold text-dark">{{ $reg->full_name }}</div>
                                            <div class="text-muted small">{{ $reg->nickname ?? '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-secondary fw-medium">{{ $reg->class_type ?? '-' }}</span>
                                </td>
                                <td>
                                    @php
                                        $badgeClass = match($reg->program) {
                                            'Matematika' => 'bg-success-subtle text-success',
                                            'B. Inggris' => 'bg-info-subtle text-info',
                                            default => 'bg-primary-subtle text-primary'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }} border-0 px-2 py-1">{{ $reg->program ?? '-' }}</span>
                                </td>
                                <td>
                                    <div class="text-dark">{{ $reg->whatsapp ?? $reg->phone ?? '-' }}</div>
                                </td>
                                <td class="pe-4">
                                    <div class="text-muted small">{{ $reg->created_at->format('d M Y') }}</div>
                                    <div class="text-muted extra-small" style="font-size: 0.7rem;">{{ $reg->created_at->format('H:i') }}</div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-muted">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-database-off mb-2 text-muted-light" width="40" height="40" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12.973 8.952c.009 .016 .018 .032 .027 .048c.01 -.016 .018 -.032 .027 -.048" /><path d="M9.44 9.441c.796 .354 1.636 .559 2.56 .559c4.418 0 8 -1.79 8 -4s-3.582 -4 -8 -4c-2.31 0 -4.376 .489 -5.673 1.259m-1.327 2.684c.005 .019 .012 .037 .021 .056c-.008 -.018 -.016 -.037 -.021 -.056" /><path d="M4 6v6c0 2.21 3.582 4 8 4c.901 0 1.748 -.075 2.52 -.213m3.35 -1.13c1.332 -.8 2.13 -1.74 2.13 -2.657v-6" /><path d="M4 12v6c0 2.21 3.582 4 8 4c3.41 0 6.273 -1.066 7.426 -2.574m.574 -3.426v-6" /><path d="M3 3l18 18" /></svg>
                                        <p class="mb-0">Belum ada pendaftaran yang masuk.</p>
                                    </div>
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
@endsection
