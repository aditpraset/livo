<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Sisa kuota sesi aktif; bertambah saat payment approved, berkurang saat sesi 'done'
            $table->integer('quota_sessions')->default(0)->after('whatsapp');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('quota_sessions');
        });
    }
};