<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // student_registrations
        Schema::table('student_registrations', function (Blueprint $table) {
            $table->foreignId('package_id')->nullable()->after('package')
                ->constrained('packages')->nullOnDelete();
            $table->foreignId('promo_id')->nullable()->after('promo_code')
                ->constrained('promos')->nullOnDelete();
            // Perluas kolom program menjadi TEXT agar muat JSON multi-mapel
            $table->text('program')->change();
        });

        // students
        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('package_id')->nullable()->after('package')
                ->constrained('packages')->nullOnDelete();
            $table->foreignId('promo_id')->nullable()->after('promo_code')
                ->constrained('promos')->nullOnDelete();
            $table->text('program')->change();
        });
    }

    public function down(): void
    {
        Schema::table('student_registrations', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropForeign(['promo_id']);
            $table->dropColumn(['package_id', 'promo_id']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropForeign(['promo_id']);
            $table->dropColumn(['package_id', 'promo_id']);
        });
    }
};
