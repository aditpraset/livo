<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Melekat pada sesi (bukan evaluasi), supaya bisa diisi tutor kapan
        // saja — termasuk sebelum sesi dievaluasi (attendance/nilai belum diisi).
        Schema::table('schedules', function (Blueprint $table) {
            $table->enum('student_feedback', ['buruk', 'kurang_baik', 'cukup_baik', 'baik', 'sangat_baik'])
                ->nullable()->after('room');
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('student_feedback');
        });
    }
};
