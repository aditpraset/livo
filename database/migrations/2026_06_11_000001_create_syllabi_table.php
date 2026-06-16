<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syllabi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('pokok_bahasan');
            $table->text('sub_pokok_bahasan')->nullable();
            $table->string('jenis_kurikulum', 100);
            $table->string('kelas', 50);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syllabi');
    }
};
