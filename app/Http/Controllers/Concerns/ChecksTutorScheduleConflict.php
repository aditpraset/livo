<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Schedule;
use App\Models\ScheduleSlot;

/**
 * Cek bentrok waktu tutor: satu tutor tidak boleh punya dua komitmen mengajar
 * (baik `Schedule` maupun `ScheduleSlot` berstatus assigned/pending_confirmation)
 * yang jamnya tumpang tindih di tanggal yang sama. Dipakai di seluruh alur
 * penjadwalan: Tambah/Edit Jadwal manual, Buat per Grouping, Jadwal dari Pola
 * (admin menetapkan tutor, maupun tutor melamar/mengonfirmasi slot sendiri).
 *
 * `ScheduleSlot` berstatus `vacant` SENGAJA tidak dihitung bentrok — tutor
 * rutin yang sedang izin minggu itu memang kosong, boleh diisi komitmen lain.
 */
trait ChecksTutorScheduleConflict
{
    /**
     * Mengembalikan deskripsi singkat komitmen yang bentrok (untuk pesan error
     * ke pengguna), atau null bila tutor benar-benar kosong di rentang waktu tsb.
     */
    protected function findTutorScheduleConflict(
        int $tutorId,
        string $date,
        string $startTime,
        string $endTime,
        array $excludeScheduleIds = [],
        array $excludeSlotIds = []
    ): ?string {
        $start = substr($startTime, 0, 5);
        $end = substr($endTime, 0, 5);

        // Dibandingkan di PHP (bukan whereRaw LEFH/SUBSTR) supaya tetap jalan di semua
        // driver DB (mis. SQLite di test, tanpa fungsi LEFT() ala MySQL).
        $conflictSchedule = Schedule::with(['student', 'subject'])
            ->where('tutor_id', $tutorId)
            ->whereDate('class_date', $date)
            ->where('status_schedule', '!=', 'canceled')
            ->when($excludeScheduleIds, fn ($q) => $q->whereNotIn('id', $excludeScheduleIds))
            ->get()
            ->first(function ($schedule) use ($start, $end) {
                $schedStart = substr($schedule->start_time, 0, 5);
                $schedEnd = substr($schedule->end_time, 0, 5);

                return $schedStart < $end && $start < $schedEnd;
            });

        if ($conflictSchedule) {
            return sprintf(
                '%s pukul %s–%s (%s)',
                $conflictSchedule->student->full_name ?? 'siswa lain',
                substr($conflictSchedule->start_time, 0, 5),
                substr($conflictSchedule->end_time, 0, 5),
                $conflictSchedule->subject->subject_name ?? '-'
            );
        }

        $conflictSlot = ScheduleSlot::with(['session', 'studentGroup', 'subject'])
            ->whereDate('class_date', $date)
            ->whereIn('status', [ScheduleSlot::STATUS_ASSIGNED, ScheduleSlot::STATUS_PENDING_CONFIRMATION])
            ->where(fn ($q) => $q->where('assigned_tutor_id', $tutorId)->orWhere('filled_by_tutor_id', $tutorId))
            ->when($excludeSlotIds, fn ($q) => $q->whereNotIn('id', $excludeSlotIds))
            ->get()
            ->first(function ($slot) use ($start, $end) {
                if (!$slot->session) {
                    return false;
                }
                $slotStart = substr($slot->session->time_start, 0, 5);
                $slotEnd = substr($slot->session->time_end, 0, 5);

                return $slotStart < $end && $start < $slotEnd;
            });

        if ($conflictSlot) {
            return sprintf(
                '%s pukul %s–%s (%s)',
                $conflictSlot->studentGroup->name ?? 'group lain',
                substr($conflictSlot->session->time_start, 0, 5),
                substr($conflictSlot->session->time_end, 0, 5),
                $conflictSlot->subject->subject_name ?? '-'
            );
        }

        return null;
    }
}
