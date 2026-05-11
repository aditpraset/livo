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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->string('no_payment')->unique();
            $table->date('payment_date');
            $table->date('expired_date')->nullable();
            $table->integer('category_payment'); // 1 = registrasi, 2 = spp, 3 = kegiatan
            $table->string('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('payment_method'); // cash, transfer
            $table->string('from')->nullable();
            $table->string('receiver')->nullable();
            $table->integer('quota')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
