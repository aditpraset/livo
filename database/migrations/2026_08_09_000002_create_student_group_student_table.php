<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Anggota eksplisit tiap grouping siswa — begitu group dipilih saat
        // Buat Jadwal per Grouping, siswa yang dijadwalkan adalah siswa di sini
        // (bukan lagi dicari otomatis lewat program/hari-sesi siswa).
        Schema::create('student_group_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['student_group_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_group_student');
    }
};
