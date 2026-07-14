@extends('admin.layouts.app')

@section('title', 'Pengingat SPP - LIVO Admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1 class="fs-3 mb-1">Pengingat Pembayaran SPP</h1>
                <p class="text-muted mb-0">Siswa aktif yang masa aktif SPP-nya sudah lewat atau akan habis dalam 7 hari.</p>
            </div>
            <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Pembayaran
            </a>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table id="reminders-table" class="table table-hover align-middle w-100">
                <thead>
                    <tr>
                        <th>NIS</th>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Program</th>
                        <th>Expired SPP</th>
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
    $('#reminders-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.data.payments-reminders') }}",
        columns: [
            { data: 'nis' },
            { data: 'full_name' },
            { data: 'grade' },
            { data: 'package' },
            { data: 'expired_date' },
            { data: 'sisa' },
            { data: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        order: []
    });
});
</script>
@endpush
