<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            // UNIQUE memastikan relasi 1:1 dengan schedules (satu sesi = satu evaluasi)
            $table->foreignId('schedule_id')->unique()->constrained('schedules')->cascadeOnDelete();
            $table->enum('student_attendance', ['hadir', 'izin', 'alfa']);
            $table->integer('score')->nullable(); // Nilai 0-100, null jika belum dinilai
            $table->text('tutor_notes')->nullable();
            $table->boolean('is_published')->default(false); // true = laporan dikirim ke orang tua
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
