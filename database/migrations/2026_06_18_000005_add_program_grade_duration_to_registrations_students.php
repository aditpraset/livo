<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['student_registrations', 'students'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('program_id')->nullable()->after('package_id')
                    ->constrained('programs')->nullOnDelete();
                $table->foreignId('grade_id')->nullable()->after('program_id')
                    ->constrained('grades')->nullOnDelete();
                $table->integer('duration')->nullable()->after('grade_id'); // lama paket dalam bulan
            });
        }
    }

    public function down(): void
    {
        foreach (['student_registrations', 'students'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['program_id']);
                $table->dropForeign(['grade_id']);
                $table->dropColumn(['program_id', 'grade_id', 'duration']);
            });
        }
    }
};
