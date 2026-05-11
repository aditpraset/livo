<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_registrations', function (Blueprint $table) {
            if (Schema::hasColumn('student_registrations', 'study_session')) {
                $table->dropColumn('study_session');
            }
            if (!Schema::hasColumn('student_registrations', 'schedule_session_id')) {
                $table->foreignId('schedule_session_id')->nullable()->after('selected_days')->constrained('schedule_sessions')->onDelete('set null');
            } else {
                // If it already exists (from failed attempt), null out data to allow type change
                DB::table('student_registrations')->update(['schedule_session_id' => null]);
                $table->unsignedBigInteger('schedule_session_id')->nullable()->change();
                // Check if foreign key exists is hard, so we just try to add it
                try {
                    $table->foreign('schedule_session_id')->references('id')->on('schedule_sessions')->onDelete('set null');
                } catch (\Exception $e) {}
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'study_session')) {
                $table->dropColumn('study_session');
            }
            if (!Schema::hasColumn('students', 'schedule_session_id')) {
                $table->foreignId('schedule_session_id')->nullable()->after('selected_days')->constrained('schedule_sessions')->onDelete('set null');
            } else {
                DB::table('students')->update(['schedule_session_id' => null]);
                $table->unsignedBigInteger('schedule_session_id')->nullable()->change();
                try {
                    $table->foreign('schedule_session_id')->references('id')->on('schedule_sessions')->onDelete('set null');
                } catch (\Exception $e) {}
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_registrations', function (Blueprint $table) {
            if (Schema::hasColumn('student_registrations', 'schedule_session_id')) {
                try {
                    $table->dropForeign(['schedule_session_id']);
                } catch (\Exception $e) {}
                $table->dropColumn('schedule_session_id');
            }
            if (!Schema::hasColumn('student_registrations', 'study_session')) {
                $table->string('study_session')->nullable()->after('selected_days');
            }
        });

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'schedule_session_id')) {
                try {
                    $table->dropForeign(['schedule_session_id']);
                } catch (\Exception $e) {}
                $table->dropColumn('schedule_session_id');
            }
            if (!Schema::hasColumn('students', 'study_session')) {
                $table->string('study_session')->nullable()->after('selected_days');
            }
        });
    }
};
