<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pola jadwal mingguan: template hari + sesi + mapel + group siswa yang dipakai
 * berulang tiap minggu. Satu pola berisi banyak baris (schedule_pattern_items).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('schedule_patterns')) {
            return;
        }

        Schema::create('schedule_patterns', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_patterns');
    }
};
