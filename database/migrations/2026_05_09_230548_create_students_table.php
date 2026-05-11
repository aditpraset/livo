<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('registration_code')->nullable()->unique();
            $table->string('nis')->nullable()->unique();
            $table->integer('status')->default(1); // 1 = aktif, 2 = non aktif
            
            // Informasi Siswa
            $table->date('registration_date')->nullable();
            $table->string('full_name');
            $table->string('nickname')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('religion')->nullable();
            $table->string('gender')->nullable();
            $table->string('grade')->nullable();
            $table->string('school_origin')->nullable();

            // Informasi Orangtua/Wali Murid
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('guardian_name')->nullable();
            $table->text('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();

            // Data Pilihan Program
            $table->string('class_type')->nullable(); // Pilihan Kelas
            $table->string('kbm_process')->nullable(); // Pilihan Proses KBM
            $table->string('package')->nullable(); // Paket
            $table->string('program')->nullable(); // Program
            $table->string('selected_days')->nullable(); // Pilihan Hari
            $table->string('study_session')->nullable(); // Sesi Belajar
            $table->string('school_curriculum')->nullable(); // Kurikulum Sekolah
            $table->string('learning_material')->nullable(); // Materi Pembelajaran

            // Informasi pendaftaran & Promo
            $table->string('promo_code')->nullable();
            $table->string('registration_info')->nullable();
            $table->string('marketing_pic')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
