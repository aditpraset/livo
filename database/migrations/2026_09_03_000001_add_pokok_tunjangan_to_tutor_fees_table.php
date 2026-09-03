<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutor_fees', function (Blueprint $table) {
            // Khusus tutor kategori "tetap": gaji pokok + tunjangan bulan tsb (nilai dibekukan
            // dari data tutor saat generate), plus kelebihan sesi di atas maks_sesi_tunjangan_per_bulan
            // yang dibayar per sesi memakai tarif fee_per_session (sesi semi-privat).
            // Untuk tutor freelance, keempat kolom ini selalu 0 (fee mereka murni dari a+b+c+d).
            $table->decimal('fee_pokok', 12, 2)->default(0)->after('total');
            $table->decimal('fee_tunjangan', 12, 2)->default(0)->after('fee_pokok');
            $table->unsignedInteger('extra_session_count')->default(0)->after('fee_tunjangan');
            $table->decimal('fee_extra_session', 12, 2)->default(0)->after('extra_session_count');
        });
    }

    public function down(): void
    {
        Schema::table('tutor_fees', function (Blueprint $table) {
            $table->dropColumn(['fee_pokok', 'fee_tunjangan', 'extra_session_count', 'fee_extra_session']);
        });
    }
};
