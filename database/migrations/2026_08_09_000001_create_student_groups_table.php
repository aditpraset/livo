<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Master "grouping" siswa: preset Sesi + Hari bernama, dipakai admin saat
        // membuat jadwal per grouping supaya tidak perlu pilih ulang tiap kali.
        // Mata pelajaran & tutor SENGAJA tidak masuk sini — diisi saat pembuatan jadwal.
        Schema::create('student_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('session_id')->constrained('schedule_sessions')->cascadeOnDelete();
            $table->enum('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_groups');
    }
};
