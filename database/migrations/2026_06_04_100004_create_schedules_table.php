<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('tutor_id')->constrained('tutors')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $table->date('class_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status_schedule', ['scheduled', 'done', 'canceled'])->default('scheduled');
            $table->timestamps();

            // Index anti-bentrok: membantu query cek konflik jadwal tutor/siswa
            $table->index(['class_date', 'status_schedule'], 'idx_schedule_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
