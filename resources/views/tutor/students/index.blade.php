@extends('tutor.layouts.app')

@section('title', 'Data Siswa Aktif - LIVO Tutor')

@section('content')
<div class="mb-4">
    <h1 class="fs-3 mb-1">Data Siswa Aktif</h1>
    <p class="text-muted mb-0">Daftar seluruh siswa aktif LIVO. Klik History Evaluasi untuk melihat riwayat evaluasi siswa.</p>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover" id="students-table" style="width:100%">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Kode Reg</th>
                        <th>NIS</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Program</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#students-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('tutor.students.data-list') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'registration_code', name: 'registration_code' },
            { data: 'nis', name: 'nis', defaultContent: '-' },
            { data: 'full_name', name: 'full_name' },
            { data: 'grade', name: 'grade', defaultContent: '-' },
            { data: 'program', name: 'program' },
            { data: 'status', name: 'status' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            paginate: { first: "Pertama", last: "Terakhir", next: "Selanjutnya", previous: "Sebelumnya" },
            emptyTable: 'Belum ada siswa aktif.'
        }
    });
});
</script>
@endpush
