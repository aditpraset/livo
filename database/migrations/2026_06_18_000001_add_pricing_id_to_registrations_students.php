<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_registrations', function (Blueprint $table) {
            $table->foreignId('pricing_id')->nullable()->after('package_id')
                ->constrained('pricings')->nullOnDelete();
        });

        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('pricing_id')->nullable()->after('package_id')
                ->constrained('pricings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_registrations', function (Blueprint $table) {
            $table->dropForeign(['pricing_id']);
            $table->dropColumn('pricing_id');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['pricing_id']);
            $table->dropColumn('pricing_id');
        });
    }
};
