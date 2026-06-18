<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            // Penanda bahwa evaluasi ini sudah memotong 1 kuota sesi siswa (anti dobel potong)
            $table->boolean('quota_consumed')->default(false)->after('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropColumn('quota_consumed');
        });
    }
};
