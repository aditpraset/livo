<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            // Sub pokok bahasan yang dievaluasi (dari silabus mata pelajaran terkait)
            $table->foreignId('syllabus_id')->nullable()->after('schedule_id')
                ->constrained('syllabi')->nullOnDelete();
            // Skala 1–100
            $table->unsignedTinyInteger('pre_test')->nullable()->after('score');
            $table->unsignedTinyInteger('post_test')->nullable()->after('pre_test');
            // Skala huruf: A+, A-, B+, B-, C+, C-, D
            $table->string('pemahaman', 3)->nullable()->after('post_test');
            $table->string('poin', 3)->nullable()->after('pemahaman');
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropForeign(['syllabus_id']);
            $table->dropColumn(['syllabus_id', 'pre_test', 'post_test', 'pemahaman', 'poin']);
        });
    }
};
