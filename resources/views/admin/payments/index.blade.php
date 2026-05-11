@extends('admin.layouts.app')

@section('title', 'Data Pembayaran - LIVO Admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="fs-3 mb-1">Data Pembayaran</h1>
                <p class="text-muted mb-0">Kelola semua data pembayaran siswa LIVO.</p>
            </div>
            <a href="{{ route('admin.payments.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Tambah Pembayaran
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white px-4 py-3">
                <h4 class="mb-0 h5">Semua Pembayaran</h4>
            </div>
            <div class="table-responsive p-3">
                <table class="table table-hover mb-0" id="payments-table">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4">#</th>
                            <th>No Pembayaran</th>
                            <th>Siswa</th>
                            <th>Kategori</th>
                            <th>Jumlah</th>
                            <th>Metode</th>
                            <th>Tanggal</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(document).ready(function() {
    var table = $('#payments-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.data.payments') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'px-4' },
            { data: 'no_payment', name: 'no_payment' },
            { data: 'student_id', name: 'student_id' },
            { data: 'category_payment', name: 'category_payment' },
            { data: 'amount', name: 'amount' },
            { data: 'payment_method', name: 'payment_method' },
            { data: 'payment_date', name: 'payment_date' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            paginate: { first: "Pertama", last: "Terakhir", next: "Selanjutnya", previous: "Sebelumnya" }
        }
    });

    // Delete with SweetAlert2
    $('#payments-table').on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');

        Swal.fire({
            title: 'Hapus Pembayaran?',
            html: 'Data pembayaran <strong>' + name + '</strong> akan dihapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e53e3e',
            cancelButtonColor: '#718096',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/admin/payments/' + id,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(res) {
                        Swal.fire({ title: 'Terhapus!', text: res.message, icon: 'success', timer: 2000, showConfirmButton: false });
                        table.ajax.reload();
                    },
                    error: function() {
                        Swal.fire({ title: 'Gagal!', text: 'Terjadi kesalahan saat menghapus.', icon: 'error' });
                    }
                });
            }
        });
    });
});
</script>
@endpush
