<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Kolom SDD untuk alur pembelian paket dengan verifikasi admin
            $table->foreignId('package_id')->nullable()->after('student_id')
                ->constrained('packages')->restrictOnDelete();
            $table->decimal('amount_paid', 12, 2)->nullable()->after('amount');
            $table->string('payment_proof', 255)->nullable()->after('amount_paid'); // path file bukti transfer
            $table->string('bank_sender', 100)->nullable()->after('payment_proof'); // nama bank + nama pengirim
            $table->enum('status_payment', ['pending', 'approved', 'rejected'])->default('pending')->after('bank_sender');
            $table->text('rejection_reason')->nullable()->after('status_payment'); // diisi admin saat menolak
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropColumn([
                'package_id',
                'amount_paid',
                'payment_proof',
                'bank_sender',
                'status_payment',
                'rejection_reason',
            ]);
        });
    }
};
