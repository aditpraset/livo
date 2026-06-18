<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->foreignId('grade_id')->constrained('grades')->cascadeOnDelete();
            $table->integer('duration'); // durasi sesi dalam bulan
            $table->decimal('price', 12, 2);
            $table->timestamps();

            // Satu harga untuk tiap kombinasi paket + program + jenjang + durasi
            $table->unique(['package_id', 'program_id', 'grade_id', 'duration'], 'uniq_pricing_combo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricings');
    }
};
