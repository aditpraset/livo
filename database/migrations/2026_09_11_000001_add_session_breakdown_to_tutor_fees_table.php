<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutor_fees', function (Blueprint $table) {
            // Rincian sesi per paket kelas siswa: [{package_id, label, count, rate, subtotal}].
            // Sejak tarif per sesi mengikuti paket siswa (Privat / Semi Privat / TKA Reguler /
            // Privat Khusus / Privat SMA), kolom fee_private + fee_session hanya menyimpan
            // totalnya — rincian tarif per paket disimpan di sini supaya bisa diaudit admin.
            // Null untuk tutor kategori "tetap" (tidak dibayar per sesi).
            $table->json('session_breakdown')->nullable()->after('fee_extra_session');
        });
    }

    public function down(): void
    {
        Schema::table('tutor_fees', function (Blueprint $table) {
            $table->dropColumn('session_breakdown');
        });
    }
};
