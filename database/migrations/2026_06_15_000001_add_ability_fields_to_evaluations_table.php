<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            // Skala huruf (A+ … D) seperti pemahaman/poin
            $table->string('kemampuan_analisa', 3)->nullable()->after('poin');
            $table->string('kemampuan_hafalan', 3)->nullable()->after('kemampuan_analisa');
            $table->string('kepercayaan_diri', 3)->nullable()->after('kemampuan_hafalan');
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropColumn(['kemampuan_analisa', 'kemampuan_hafalan', 'kepercayaan_diri']);
        });
    }
};
