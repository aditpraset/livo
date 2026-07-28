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
        Schema::table('tutors', function (Blueprint $table) {
            $table->decimal('fee_per_student_private', 12, 2)->nullable()->after('fee_per_session');
            $table->decimal('fee_per_student', 12, 2)->nullable()->after('fee_per_student_private');
            $table->decimal('fee_transport_per_day', 12, 2)->nullable()->after('fee_per_student');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tutors', function (Blueprint $table) {
            $table->dropColumn(['fee_per_student_private', 'fee_per_student', 'fee_transport_per_day']);
        });
    }
};
