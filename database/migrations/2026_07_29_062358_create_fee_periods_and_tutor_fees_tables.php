<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu baris per bulan yang sudah di-generate oleh admin.
        Schema::create('fee_periods', function (Blueprint $table) {
            $table->id();
            $table->date('month')->unique(); // selalu tanggal 1 bulan tsb (Y-m-01)
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamp('generated_at')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Rincian fee per tutor untuk satu periode (breakdown a+b+c+d, hasil generate).
        Schema::create('tutor_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tutor_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('private_count')->default(0);
            $table->unsignedInteger('regular_count')->default(0);
            $table->unsignedInteger('session_count')->default(0);
            $table->unsignedInteger('day_count')->default(0);
            $table->decimal('fee_private', 12, 2)->default(0);
            $table->decimal('fee_regular', 12, 2)->default(0);
            $table->decimal('fee_session', 12, 2)->default(0);
            $table->decimal('fee_transport', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['fee_period_id', 'tutor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_fees');
        Schema::dropIfExists('fee_periods');
    }
};
