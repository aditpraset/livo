<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_registrations', function (Blueprint $table) {
            // Referensi master jadwal yang dipilih (bisa lebih dari satu sesuai durasi program)
            $table->json('class_schedule_ids')->nullable()->after('schedule_session_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->json('class_schedule_ids')->nullable()->after('schedule_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('student_registrations', function (Blueprint $table) {
            $table->dropColumn('class_schedule_ids');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('class_schedule_ids');
        });
    }
};
