<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Penghubung akun user ke data master: tutor_id (master tutor) untuk role tutor,
     * student_id (master siswa) untuk role siswa. Role admin murni dari tabel users.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tutor_id')->nullable()->after('status')->constrained('tutors')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->after('tutor_id')->constrained('students')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tutor_id');
            $table->dropConstrainedForeignId('student_id');
        });
    }
};
