<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Status baru `vacant`: tutor rutin mengambil IZIN untuk satu minggu saja,
 * tanpa melepas kepemilikan pola. `assigned_tutor_id` tetap menunjuk tutor
 * rutin (dipakai sebagai "petahana" saat generate minggu berikutnya);
 * `filled_by_tutor_id` diisi bila admin menetapkan tutor pengganti khusus
 * untuk minggu itu saja.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('schedule_slots', 'filled_by_tutor_id')) {
            Schema::table('schedule_slots', function (Blueprint $table) {
                $table->foreignId('filled_by_tutor_id')->nullable()->after('assigned_tutor_id')
                    ->constrained('tutors')->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('schedule_slots', 'izin_at')) {
            Schema::table('schedule_slots', function (Blueprint $table) {
                $table->timestamp('izin_at')->nullable()->after('confirmed_at');
            });
        }

        // SQLite (dipakai tes RefreshDatabase) tidak punya ENUM sungguhan — kolom
        // status di sana cuma TEXT, jadi tidak perlu (dan tidak bisa) di-ALTER.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            $column = DB::selectOne("SHOW COLUMNS FROM schedule_slots WHERE Field = 'status'");
            if ($column && !str_contains($column->Type, "'vacant'")) {
                DB::statement("ALTER TABLE schedule_slots MODIFY status ENUM('open','pending_confirmation','assigned','vacant','closed') DEFAULT 'open'");
            }
        }
    }

    public function down(): void
    {
        DB::table('schedule_slots')->where('status', 'vacant')->update(['status' => 'open']);

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            $column = DB::selectOne("SHOW COLUMNS FROM schedule_slots WHERE Field = 'status'");
            if ($column && str_contains($column->Type, "'vacant'")) {
                DB::statement("ALTER TABLE schedule_slots MODIFY status ENUM('open','pending_confirmation','assigned','closed') DEFAULT 'open'");
            }
        }

        if (Schema::hasColumn('schedule_slots', 'izin_at')) {
            Schema::table('schedule_slots', function (Blueprint $table) {
                $table->dropColumn('izin_at');
            });
        }

        if (Schema::hasColumn('schedule_slots', 'filled_by_tutor_id')) {
            Schema::table('schedule_slots', function (Blueprint $table) {
                $table->dropConstrainedForeignId('filled_by_tutor_id');
            });
        }
    }
};
