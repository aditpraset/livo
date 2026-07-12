@extends('tutor.layouts.app')

@section('title', 'Evaluasi Siswa - LIVO Tutor')

@section('content')
<div class="mb-4">
    <h1 class="fs-3 mb-1">Evaluasi Siswa</h1>
    <p class="text-muted mb-0">Daftar sesi yang evaluasinya belum diisi.</p>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-vcenter" id="table-evaluations" style="width:100%">
                <thead>
                    <tr>
                        <th style="width:50px">No</th>
                        <th>Tanggal Sesi</th>
                        <th>Siswa</th>
                        <th>Mata Pelajaran</th>
                        <th>Kelas / Ruang</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
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
    $('#table-evaluations').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('tutor.evaluations.data') }}',
        order: [],
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'class_date' },
            { data: 'student_name', name: 'student.full_name' },
            { data: 'subject_name', name: 'subject.subject_name', orderable: false },
            { data: 'room' },
            { data: 'status_schedule' },
            { data: 'action', orderable: false, searchable: false, className: 'text-end' },
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json',
            emptyTable: 'Semua sesi sudah dievaluasi. Kerja bagus! 🎉'
        }
    });
});
</script>
@endpush
