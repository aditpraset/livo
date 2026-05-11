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
        Schema::table('student_registrations', function (Blueprint $table) {
            $table->string('registration_code')->nullable()->unique()->after('id');
            $table->string('nis')->nullable()->after('registration_code');
            $table->string('status')->default('Baru')->after('nis'); // Baru, Belum Lunas, Lunas
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_registrations', function (Blueprint $table) {
            $table->dropColumn(['registration_code', 'nis', 'status']);
        });
    }
};
