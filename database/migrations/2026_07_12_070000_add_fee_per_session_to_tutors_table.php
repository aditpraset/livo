<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fee per sesi mengajar — dasar perhitungan rekapitulasi fee & slip gaji tutor.
     */
    public function up(): void
    {
        Schema::table('tutors', function (Blueprint $table) {
            $table->decimal('fee_per_session', 15, 2)->nullable()->after('no_rekening');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tutors', function (Blueprint $table) {
            $table->dropColumn('fee_per_session');
        });
    }
};
