<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu baris pola mingguan: hari + sesi + mata pelajaran + group siswa.
 * Hari & sesi biasanya mengikuti StudentGroup, tapi disimpan eksplisit agar
 * satu group tetap bisa dijadwalkan di hari/sesi lain bila perlu.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('schedule_pattern_items')) {
            return;
        }

        Schema::create('schedule_pattern_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_pattern_id')->constrained()->cascadeOnDelete();
            $table->enum('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu']);
            $table->foreignId('session_id')->constrained('schedule_sessions')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('student_group_id')->constrained('student_groups')->cascadeOnDelete();
            $table->timestamps();

            // Satu group tidak boleh dipesan dua kali pada hari + sesi yang sama dalam satu pola.
            $table->unique(['schedule_pattern_id', 'hari', 'session_id', 'student_group_id'], 'pattern_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_pattern_items');
    }
};
