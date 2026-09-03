<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutors', function (Blueprint $table) {
            // Freelance = dibayar per sesi/siswa (fee_per_session dkk, sudah ada).
            // Tetap = tutor tetap, bisa dapat gaji pokok + tunjangan bulanan di luar fee per sesi.
            $table->enum('kategori', ['freelance', 'tetap'])->default('freelance')->after('specialization');
            $table->decimal('gaji_pokok', 12, 2)->nullable()->after('kategori');
            $table->decimal('tunjangan_per_bulan', 12, 2)->nullable()->after('gaji_pokok');
            $table->unsignedInteger('maks_sesi_tunjangan_per_bulan')->nullable()
                ->after('tunjangan_per_bulan')
                ->comment('Batas jumlah sesi per bulan yang masih ditanggung tunjangan');
        });
    }

    public function down(): void
    {
        Schema::table('tutors', function (Blueprint $table) {
            $table->dropColumn(['kategori', 'gaji_pokok', 'tunjangan_per_bulan', 'maks_sesi_tunjangan_per_bulan']);
        });
    }
};
