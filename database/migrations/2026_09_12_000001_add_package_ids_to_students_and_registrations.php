<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Paket siswa jadi multi-pilih (1–3). Kolom baru `package_ids` (JSON) menyimpan
 * seluruh paket; `package_id` lama TETAP dipertahankan sebagai paket utama
 * (paket pertama) supaya perhitungan fee, filter jadwal, & pembayaran tetap jalan.
 *
 * Migrasi ini murni aditif: tidak menghapus kolom atau baris mana pun.
 * Data lama di-backfill: setiap siswa/pendaftaran dengan package_id → package_ids [package_id].
 */
return new class extends Migration
{
    private array $tables = ['students', 'student_registrations'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasColumn($table, 'package_ids')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->json('package_ids')->nullable()->after('package_id');
                });
            }
        }

        // Backfill dari package_id yang sudah ada (tanpa menyentuh baris yang sudah terisi).
        foreach ($this->tables as $table) {
            DB::table($table)
                ->whereNotNull('package_id')
                ->where(function ($q) {
                    $q->whereNull('package_ids')->orWhere('package_ids', '');
                })
                ->orderBy('id')
                ->chunkById(500, function ($rows) use ($table) {
                    foreach ($rows as $row) {
                        DB::table($table)->where('id', $row->id)->update([
                            'package_ids' => json_encode([(int) $row->package_id]),
                        ]);
                    }
                });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasColumn($table, 'package_ids')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('package_ids');
                });
            }
        }
    }
};
