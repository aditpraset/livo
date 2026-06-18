<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutors', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('name');
            $table->string('email')->nullable()->after('phone');
            $table->string('no_rekening', 50)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('tutors', function (Blueprint $table) {
            $table->dropColumn(['photo', 'email', 'no_rekening']);
        });
    }
};
