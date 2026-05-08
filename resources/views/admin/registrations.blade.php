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
            </div>
            <div class="table-responsive p-3">
                <table class="table table-hover mb-0" id="registrations-table">
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
                        {{-- Populated via DataTables --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Detail Modal --}}
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Pendaftaran — <span id="modal-title-name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modal-detail-content">
                <div class="row g-3">
                    {{-- Informasi Siswa --}}
                    <div class="col-12"><h6 class="fw-bold border-bottom pb-2">Informasi Siswa</h6></div>
                    <div class="col-md-4"><small class="text-muted d-block">Nama Lengkap</small><strong id="detail-full_name"></strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Nama Panggilan</small><strong id="detail-nickname"></strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Tanggal Lahir</small><strong id="detail-birth_date"></strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Agama</small><strong id="detail-religion"></strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Jenis Kelamin</small><strong id="detail-gender"></strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Kelas</small><strong id="detail-grade"></strong></div>
                    <div class="col-md-6"><small class="text-muted d-block">Asal Sekolah</small><strong id="detail-school_origin"></strong></div>

                    {{-- Informasi Orang Tua --}}
                    <div class="col-12 mt-3"><h6 class="fw-bold border-bottom pb-2">Informasi Orangtua / Wali</h6></div>
                    <div class="col-md-4"><small class="text-muted d-block">Nama Ayah</small><strong id="detail-father_name"></strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Nama Ibu</small><strong id="detail-mother_name"></strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Nama Wali</small><strong id="detail-guardian_name"></strong></div>
                    <div class="col-12"><small class="text-muted d-block">Alamat</small><strong id="detail-address"></strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Email</small><strong id="detail-email"></strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">No. Telp / HP</small><strong id="detail-phone"></strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">WhatsApp</small><strong id="detail-whatsapp"></strong></div>

                    {{-- Data Program --}}
                    <div class="col-12 mt-3"><h6 class="fw-bold border-bottom pb-2">Data Pilihan Program</h6></div>
                    <div class="col-md-4"><small class="text-muted d-block">Pilihan Kelas</small><strong id="detail-class_type"></strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Proses KBM</small><strong id="detail-kbm_process"></strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Paket</small><strong id="detail-package"></strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Program</small><strong id="detail-program"></strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Pilihan Hari</small><strong id="detail-selected_days"></strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Sesi Belajar</small><strong id="detail-study_session"></strong></div>
                    <div class="col-md-6"><small class="text-muted d-block">Kurikulum Sekolah</small><strong id="detail-school_curriculum"></strong></div>
                    <div class="col-md-6"><small class="text-muted d-block">Materi Pembelajaran</small><strong id="detail-learning_material"></strong></div>

                    {{-- Promo --}}
                    <div class="col-12 mt-3"><h6 class="fw-bold border-bottom pb-2">Informasi Pendaftaran & Promo</h6></div>
                    <div class="col-md-4"><small class="text-muted d-block">Kode Promo</small><strong id="detail-promo_code"></strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">Info Pendaftaran</small><strong id="detail-registration_info"></strong></div>
                    <div class="col-md-4"><small class="text-muted d-block">PIC Marketing</small><strong id="detail-marketing_pic"></strong></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(document).ready(function() {
    var table = $('#registrations-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.data.registrations') }}",
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'px-4' },
            { data: 'full_name', name: 'full_name' },
            { data: 'grade', name: 'grade', defaultContent: '-' },
            { data: 'program', name: 'program' },
            { data: 'kbm_process', name: 'kbm_process', defaultContent: '-' },
            { data: 'whatsapp', name: 'whatsapp', defaultContent: '-' },
            { data: 'school_origin', name: 'school_origin', defaultContent: '-' },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "Selanjutnya",
                previous: "Sebelumnya"
            }
        }
    });

    // Handle Detail Modal
    $('#registrations-table').on('click', 'button[data-bs-target^="#detailModal"]', function() {
        var id = $(this).data('bs-target').replace('#detailModal', '');
        $('#detailModal').modal('show');
        
        // Show loading or clear old data
        $('#modal-title-name').text('Memuat...');
        $('#modal-detail-content strong').text('-');

        $.get('/admin/registrations/' + id, function(data) {
            $('#modal-title-name').text(data.full_name);
            for (var key in data) {
                if ($('#detail-' + key).length) {
                    $('#detail-' + key).text(data[key] || '-');
                }
            }
        });
    });
});
</script>
@endpush
