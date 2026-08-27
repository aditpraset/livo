<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hapus siswa jadi soft-delete: baris siswa tetap ada di DB (hanya ditandai
        // deleted_at) supaya jadwal/evaluasi/pembayaran yang terhubung (FK cascade)
        // tidak ikut terhapus dan riwayatnya tetap bisa diakses.
        Schema::table('students', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
