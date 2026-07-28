<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Schedule;
use App\Models\Tutor;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Perhitungan fee tutor per bulan.
 *
 * Total fee = a + b + c + d, dengan:
 *   a) fee_per_student_private × jumlah kehadiran siswa paket Privat (package_id 5)
 *   b) fee_per_student         × jumlah kehadiran siswa paket Semi-Privat (package_id 6)
 *   c) fee_per_session         × jumlah sesi (dikelompokkan per slot tanggal + jam)
 *   d) fee_transport_per_day   × jumlah hari mengajar (hari yang punya minimal satu sesi)
 *
 * Ketentuan:
 *  - Sesi yang dihitung berstatus "done".
 *  - "Jumlah siswa diajar" (a & b) dihitung PER KEHADIRAN: tiap siswa pada tiap
 *    sesi yang berstatus kehadiran "hadir" dihitung satu. Izin/alfa tidak dihitung.
 */
trait ComputesTutorFee
{
    /** Paket Privat (pakai fee_per_student_private) & Semi-Privat (pakai fee_per_student). */
    protected int $feePackagePrivate = 5;
    protected int $feePackageRegular = 6;

    /** Rincian fee 12 bulan dalam satu tahun (satu kali query). */
    protected function tutorFeeByMonth(Tutor $tutor, int $year): Collection
    {
        $schedules = $this->feeScheduleQuery($tutor)
            ->whereYear('class_date', $year)
            ->get(['id', 'student_id', 'class_date', 'start_time', 'end_time']);

        $byMonth = $schedules->groupBy(fn ($s) => (int) $s->class_date->format('n'));

        return collect(range(1, 12))->mapWithKeys(fn ($m) => [
            $m => $this->tutorFeeBreakdown($tutor, $byMonth->get($m, collect())),
        ]);
    }

    /** Rincian fee untuk satu bulan tertentu. */
    protected function tutorFeeForMonth(Tutor $tutor, Carbon $month): array
    {
        $schedules = $this->feeScheduleQuery($tutor)
            ->whereYear('class_date', $month->year)
            ->whereMonth('class_date', $month->month)
            ->get(['id', 'student_id', 'class_date', 'start_time', 'end_time']);

        return $this->tutorFeeBreakdown($tutor, $schedules);
    }

    private function feeScheduleQuery(Tutor $tutor)
    {
        return Schedule::with([
                'student:id,package_id',
                'evaluation:id,schedule_id,student_attendance',
            ])
            ->where('tutor_id', $tutor->id)
            ->where('status_schedule', 'done');
    }

    /** Hitung komponen fee dari kumpulan sesi "done" dalam satu bulan. */
    protected function tutorFeeBreakdown(Tutor $tutor, Collection $schedules): array
    {
        $slotKey = fn ($s) => $s->class_date->toDateString() . '|' . $s->start_time . '|' . $s->end_time;

        // (a & b) Kehadiran siswa "hadir", dikelompokkan per paket.
        $hadir = $schedules->filter(fn ($s) => optional($s->evaluation)->student_attendance === 'hadir');
        $privateCount = $hadir->filter(fn ($s) => (int) optional($s->student)->package_id === $this->feePackagePrivate)->count();
        $regularCount = $hadir->filter(fn ($s) => (int) optional($s->student)->package_id === $this->feePackageRegular)->count();

        // (c) Sesi unik: satu slot (tanggal + jam) meski berisi banyak siswa.
        $sessionCount = $schedules->unique($slotKey)->count();

        // (d) Hari mengajar: tanggal berbeda yang punya minimal satu sesi.
        $dayCount = $schedules->map(fn ($s) => $s->class_date->toDateString())->unique()->count();

        $rPrivate   = (float) ($tutor->fee_per_student_private ?? 0);
        $rStudent   = (float) ($tutor->fee_per_student ?? 0);
        $rSession   = (float) ($tutor->fee_per_session ?? 0);
        $rTransport = (float) ($tutor->fee_transport_per_day ?? 0);

        $a = $privateCount * $rPrivate;
        $b = $regularCount * $rStudent;
        $c = $sessionCount * $rSession;
        $d = $dayCount * $rTransport;

        return [
            'private_count' => $privateCount,
            'regular_count' => $regularCount,
            'session_count' => $sessionCount,
            'day_count'     => $dayCount,
            'fee_private'   => $a,
            'fee_regular'   => $b,
            'fee_session'   => $c,
            'fee_transport' => $d,
            'total'         => $a + $b + $c + $d,
        ];
    }
}
