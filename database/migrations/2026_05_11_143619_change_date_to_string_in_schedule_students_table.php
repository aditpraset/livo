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
        Schema::table('schedule_students', function (Blueprint $table) {
            $table->string('date')->change()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedule_students', function (Blueprint $table) {
            $table->dropIndex(['date']);
            $table->date('date')->change();
        });
    }
};
