@extends('admin.layouts.app')

@section('title', 'Data Siswa - LIVO Admin')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="mb-4">
            <h1 class="fs-3 mb-1">Data Siswa LIVO</h1>
            <p class="text-muted">Kelola semua data siswa yang aktif dan non-aktif.</p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white px-4 py-3">
                <h4 class="mb-0 h5">Daftar Siswa</h4>
            </div>
            <div class="table-responsive p-3">
                <table class="table table-hover mb-0" id="students-table">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4">#</th>
                            <th>Kode Reg</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Program</th>
                            <th>Status</th>
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
    var table = $('#students-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.data.students') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'px-4' },
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
            paginate: { first: "Pertama", last: "Terakhir", next: "Selanjutnya", previous: "Sebelumnya" }
        }
    });

    // Delete with SweetAlert2
    $('#students-table').on('click', '.btn-delete', function() {
        var id = $(this).data('id');
        var name = $(this).data('name');

        Swal.fire({
            title: 'Hapus Data Siswa?',
            html: 'Data siswa <strong>' + name + '</strong> akan dihapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/admin/students/' + id,
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
