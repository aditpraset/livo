<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baris pola yang sudah "disiapkan" untuk satu minggu tertentu.
 *
 * Dua asal-usul:
 *  - open                 → belum pernah ada tutor; dilelang, tutor melamar,
 *                           ADMIN yang menetapkan satu dari pelamar.
 *  - pending_confirmation → tutor dibawa dari minggu sebelumnya (pola sudah
 *                           terbentuk); TUTOR yang mengonfirmasi. Bila dilepas,
 *                           slot kembali menjadi `open` dan masuk pool lelang.
 *
 * Sesi/mapel/group DISALIN (bukan sekadar FK ke pola) supaya perubahan pola di
 * kemudian hari tidak mengubah slot minggu-minggu yang sudah lewat.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('schedule_slots')) {
            return;
        }

        Schema::create('schedule_slots', function (Blueprint $table) {
            $table->id();
            // nullOnDelete: riwayat slot tetap utuh walau baris pola dihapus.
            $table->foreignId('schedule_pattern_item_id')->nullable()->constrained()->nullOnDelete();

            $table->date('week_start');  // selalu hari Senin
            $table->date('class_date');  // week_start + offset hari
            $table->enum('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu']);

            // Salinan beku dari pola saat slot dibuat.
            $table->foreignId('session_id')->nullable()->constrained('schedule_sessions')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('student_group_id')->nullable()->constrained('student_groups')->nullOnDelete();

            $table->enum('status', ['open', 'pending_confirmation', 'assigned', 'closed'])->default('open');

            $table->foreignId('assigned_tutor_id')->nullable()->constrained('tutors')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable(); // diisi bila lewat konfirmasi tutor
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();

            // Rantai carry-over: menunjuk slot minggu sebelumnya yang tutornya dibawa ke sini.
            $table->foreignId('carried_from_slot_id')->nullable()
                ->constrained('schedule_slots')->nullOnDelete();

            $table->timestamps();

            // Satu baris pola hanya boleh punya satu slot per minggu (trigger idempoten).
            $table->unique(['schedule_pattern_item_id', 'week_start'], 'slot_item_week_unique');
            $table->index(['week_start', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_slots');
    }
};
