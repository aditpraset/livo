<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropColumn('hari');
        });

        Schema::table('class_schedules', function (Blueprint $table) {
            $table->string('hari', 20)->after('program_id'); // satu hari per jadwal
        });
    }

    public function down(): void
    {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropColumn('hari');
        });

        Schema::table('class_schedules', function (Blueprint $table) {
            $table->json('hari')->after('program_id');
        });
    }
};
