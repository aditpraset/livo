@extends('tutor.layouts.app')

@section('title', 'Modul Belajar - LIVO Tutor')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="fs-3 mb-1">Modul Belajar</h1>
        <p class="text-muted mb-0">Materi ajar yang disediakan admin, dikelompokkan per kelas.</p>
    </div>
</div>

@forelse($modules as $kelas => $items)
    <div class="card mb-3">
        <div class="card-header bg-white py-2 d-flex align-items-center gap-2">
            <h3 class="card-title mb-0">{{ $kelas }}</h3>
            <span class="text-muted small ms-2">{{ $items->count() }} modul</span>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table mb-0">
                <thead>
                    <tr>
                        <th>Nama Modul</th>
                        <th>Mata Pelajaran</th>
                        <th class="text-end" style="width:140px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $module)
                        <tr>
                            <td>{{ $module->nama_modul }}</td>
                            <td>{{ $module->subject->subject_name ?? '-' }}</td>
                            <td class="text-end">
                                <a href="{{ route('tutor.modules.download', $module) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-download me-1"></i> Unduh
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="card">
        <div class="card-body text-center text-muted py-5">Belum ada modul yang diupload admin.</div>
    </div>
@endforelse
@endsection
