<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutors', function (Blueprint $table) {
            // Tarif tambahan untuk kombinasi paket/program/jenjang tertentu, di luar
            // fee_per_session, fee_per_student_private, fee_per_student, fee_transport_per_day
            // yang sudah ada. Sama seperti tarif lain, nullable & tidak otomatis dipakai
            // di ComputesTutorFee — sementara hanya data master untuk referensi/perhitungan manual.
            $table->decimal('fee_private_khusus', 12, 2)->nullable()->after('fee_transport_per_day');
            $table->decimal('fee_tka_regular', 12, 2)->nullable()->after('fee_private_khusus');
            $table->decimal('fee_private_sma', 12, 2)->nullable()->after('fee_tka_regular');
            $table->decimal('fee_tka_visit', 12, 2)->nullable()->after('fee_private_sma');
        });
    }

    public function down(): void
    {
        Schema::table('tutors', function (Blueprint $table) {
            $table->dropColumn(['fee_private_khusus', 'fee_tka_regular', 'fee_private_sma', 'fee_tka_visit']);
        });
    }
};
