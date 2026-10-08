<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul belajar (materi) yang diupload admin per mata pelajaran + kelas,
 * bisa diunduh tutor. File disimpan di disk `public` (storage/app/public/modules).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('modules')) {
            return;
        }

        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->string('kelas', 50);
            $table->string('nama_modul');
            $table->string('file');
            $table->string('file_original_name')->nullable();
            $table->timestamps();

            $table->index(['kelas']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
