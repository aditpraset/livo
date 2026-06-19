<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hapus pre_test & poin, dan ubah nilai huruf menjadi angka 1–100.
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropColumn([
                'pre_test', 'poin',
                'pemahaman', 'kemampuan_analisa', 'kemampuan_hafalan', 'kepercayaan_diri',
            ]);
        });

        Schema::table('evaluations', function (Blueprint $table) {
            $table->unsignedTinyInteger('pemahaman')->nullable()->after('post_test');
            $table->unsignedTinyInteger('kemampuan_analisa')->nullable()->after('pemahaman');
            $table->unsignedTinyInteger('kemampuan_hafalan')->nullable()->after('kemampuan_analisa');
            $table->unsignedTinyInteger('kepercayaan_diri')->nullable()->after('kemampuan_hafalan');
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropColumn(['pemahaman', 'kemampuan_analisa', 'kemampuan_hafalan', 'kepercayaan_diri']);
        });

        Schema::table('evaluations', function (Blueprint $table) {
            $table->unsignedTinyInteger('pre_test')->nullable()->after('score');
            $table->string('pemahaman', 3)->nullable()->after('post_test');
            $table->string('poin', 3)->nullable()->after('pemahaman');
            $table->string('kemampuan_analisa', 3)->nullable()->after('poin');
            $table->string('kemampuan_hafalan', 3)->nullable()->after('kemampuan_analisa');
            $table->string('kepercayaan_diri', 3)->nullable()->after('kemampuan_hafalan');
        });
    }
};
