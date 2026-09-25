<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Insentif per tutor per bulan — diisi MANUAL oleh admin di halaman Fee Tutor,
 * bukan dihitung sistem. Berlaku untuk tutor freelance maupun tetap.
 *
 * Karena manual, nilainya dipertahankan saat admin menekan "Generate / Hitung Ulang"
 * (lihat TutorFeeController::generate()).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tutor_fees', 'fee_insentif')) {
            return;
        }

        Schema::table('tutor_fees', function (Blueprint $table) {
            $table->decimal('fee_insentif', 12, 2)->default(0)->after('fee_extra_session');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('tutor_fees', 'fee_insentif')) {
            return;
        }

        Schema::table('tutor_fees', function (Blueprint $table) {
            $table->dropColumn('fee_insentif');
        });
    }
};
