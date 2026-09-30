<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penelusuran balik: jadwal asli yang lahir dari sebuah slot. Dipakai untuk
 * mencegah generate dobel dan untuk membersihkan jadwal bila penetapan dibatalkan.
 * Aditif — kolom lama tidak disentuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('schedules', 'schedule_slot_id')) {
            return;
        }

        Schema::table('schedules', function (Blueprint $table) {
            $table->foreignId('schedule_slot_id')->nullable()->after('tutor_id')
                ->constrained('schedule_slots')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('schedules', 'schedule_slot_id')) {
            return;
        }

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('schedule_slot_id');
        });
    }
};
