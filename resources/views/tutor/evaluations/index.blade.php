@extends('tutor.layouts.app')

@section('title', 'Evaluasi Siswa - LIVO Tutor')

@section('content')
<div class="mb-4 d-flex justify-content-between align-items-end flex-wrap gap-2">
    <div>
        <h1 class="fs-3 mb-1">Evaluasi Siswa</h1>
        <p class="text-muted mb-0" id="eval-mode-desc">Daftar sesi yang evaluasinya belum diisi.</p>
    </div>
    <div class="btn-group" id="eval-mode-toggle" role="group">
        <button type="button" class="btn btn-primary" data-mode="pending"><i class="bi bi-hourglass-split me-1"></i>Belum Dievaluasi</button>
        <button type="button" class="btn btn-outline-primary" data-mode="done"><i class="bi bi-check2-circle me-1"></i>Sudah Dievaluasi</button>
    </div>
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
    var evalMode = 'pending';

    var table = $('#table-evaluations').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('tutor.evaluations.data') }}',
            data: function (d) { d.mode = evalMode; }
        },
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

    $('#eval-mode-toggle button').on('click', function () {
        evalMode = $(this).data('mode');
        $('#eval-mode-toggle button').removeClass('btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('btn-primary');
        $('#eval-mode-desc').text(evalMode === 'done'
            ? 'Daftar sesi yang sudah dievaluasi — klik Edit untuk memperbaiki evaluasi.'
            : 'Daftar sesi yang evaluasinya belum diisi.');
        table.ajax.reload();
    });
});
</script>
@endpush
