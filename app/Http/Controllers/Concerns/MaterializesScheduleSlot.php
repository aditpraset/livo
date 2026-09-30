<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Schedule;
use App\Models\ScheduleSlot;
use Carbon\Carbon;

/**
 * Mengubah sebuah ScheduleSlot menjadi jadwal asli (Schedule) untuk setiap
 * anggota group siswa. Aturannya sengaja disamakan dengan
 * ScheduleController::generateByGroup() supaya perilakunya konsisten:
 *
 *  - Hanya siswa berstatus aktif.
 *  - Kuota: sisa = quota_sessions - jadwal yang belum dievaluasi & belum dibatalkan.
 *    Sisa <= 0 → siswa dilewati.
 *  - Anti-dobel: siswa sudah punya jadwal di tanggal + jam mulai yang sama
 *    (selain yang dibatalkan) → dilewati.
 *
 * Bedanya dengan generateByGroup: jadwal lahir berstatus `scheduled` (bukan
 * `done`), karena slot disiapkan sebelum minggunya berjalan.
 */
trait MaterializesScheduleSlot
{
    /** Selisih hari dari awal minggu (Senin). */
    protected const DAY_OFFSET = [
        'Senin' => 0, 'Selasa' => 1, 'Rabu' => 2, 'Kamis' => 3,
        'Jumat' => 4, 'Sabtu' => 5, 'Minggu' => 6,
    ];

    /** Tanggal kelas untuk sebuah hari dalam minggu yang diawali $weekStart. */
    protected function classDateFor(Carbon $weekStart, string $hari): Carbon
    {
        return $weekStart->copy()->startOfWeek(Carbon::MONDAY)
            ->addDays(self::DAY_OFFSET[$hari] ?? 0);
    }

    /**
     * Buat jadwal asli untuk seluruh anggota group pada slot ini.
     *
     * @return array{created:int, skipped:int, reasons:array<string,int>}
     */
    protected function materializeSlot(ScheduleSlot $slot): array
    {
        $created = 0;
        $skipped = 0;
        $reasons = ['kuota_habis' => 0, 'jadwal_sudah_ada' => 0];

        $group   = $slot->studentGroup;
        $session = $slot->session;
        $tutorId = $slot->effectiveTutorId();

        if (!$group || !$session || !$tutorId) {
            return ['created' => 0, 'skipped' => 0, 'reasons' => $reasons];
        }

        $classDate = $slot->class_date->toDateString();
        $startTime = substr($session->time_start, 0, 5);
        $endTime   = substr($session->time_end, 0, 5);

        foreach ($group->students()->where('status', 1)->get() as $student) {
            // Kuota yang belum "kepakai" = jadwal belum dievaluasi & belum dibatalkan.
            $pending = Schedule::where('student_id', $student->id)
                ->where('status_schedule', '!=', 'canceled')
                ->whereDoesntHave('evaluation')
                ->count();

            if (((int) ($student->quota_sessions ?? 0) - $pending) <= 0) {
                $skipped++;
                $reasons['kuota_habis']++;
                continue;
            }

            $exists = Schedule::where('student_id', $student->id)
                ->where('class_date', $classDate)
                ->where('start_time', $startTime)
                ->where('status_schedule', '!=', 'canceled')
                ->exists();

            if ($exists) {
                $skipped++;
                $reasons['jadwal_sudah_ada']++;
                continue;
            }

            Schedule::create([
                'student_id'       => $student->id,
                'tutor_id'         => $tutorId,
                'subject_id'       => $slot->subject_id,
                'schedule_slot_id' => $slot->id,
                'class_date'       => $classDate,
                'start_time'       => $startTime,
                'end_time'         => $endTime,
                'status_schedule'  => 'scheduled',
            ]);

            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped, 'reasons' => $reasons];
    }

    /**
     * Tarik kembali jadwal yang lahir dari slot ini (saat penetapan dibatalkan).
     * Jadwal yang SUDAH dievaluasi tidak dihapus — datanya berharga.
     *
     * @return array{deleted:int, kept:int}
     */
    protected function releaseSlotSchedules(ScheduleSlot $slot): array
    {
        $schedules = Schedule::where('schedule_slot_id', $slot->id)->get();

        $deleted = 0;
        $kept    = 0;

        foreach ($schedules as $schedule) {
            if ($schedule->evaluation()->exists()) {
                $kept++;
                continue;
            }

            $schedule->delete();
            $deleted++;
        }

        return ['deleted' => $deleted, 'kept' => $kept];
    }
}
