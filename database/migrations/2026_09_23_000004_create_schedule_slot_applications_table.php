<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lamaran tutor atas sebuah slot. Boleh lebih dari satu tutor per slot;
 * admin yang akhirnya menetapkan satu (accepted), sisanya rejected.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('schedule_slot_applications')) {
            return;
        }

        Schema::create('schedule_slot_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_slot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tutor_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'accepted', 'rejected', 'withdrawn'])->default('pending');
            $table->string('note', 255)->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->unique(['schedule_slot_id', 'tutor_id'], 'slot_tutor_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_slot_applications');
    }
};
