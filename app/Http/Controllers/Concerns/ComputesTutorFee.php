<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Schedule;
use App\Models\Tutor;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Perhitungan fee tutor per bulan.
 *
 * Satu "sesi" = satu slot mengajar (tanggal + jam + tutor), boleh diisi banyak
 * siswa sekaligus. Total fee = a + b + c + d, dengan:
 *   a) fee_per_student_private × jumlah SESI yang berisi minimal satu siswa
 *      paket Privat (package_id 5) — flat per sesi, BUKAN dikali jumlah siswa.
 *   b) fee_per_session         × jumlah SESI yang TIDAK berisi siswa Privat
 *      (semi-privat/fallback) — flat per sesi, alternatif dari (a).
 *   c) fee_per_student         × TOTAL kehadiran siswa "hadir" di seluruh sesi
 *      bulan itu, tanpa memandang paket — dikali per kepala di setiap sesi.
 *   d) fee_transport_per_day   × jumlah hari mengajar (hari yang punya minimal satu sesi)
 *
 * Ketentuan:
 *  - Sesi yang dihitung berstatus "done", dan hanya kehadiran "hadir" yang
 *    dihitung (izin/alfa tidak dihitung sama sekali, baik untuk a/b/c).
 *  - Sesi CAMPURAN (ada siswa Privat & non-Privat sekaligus) diklasifikasi
 *    sebagai sesi Privat (a) — non-Privat diabaikan untuk komponen (a/b) itu,
 *    tapi tetap ikut dihitung pada komponen (c).
 *  - Siswa dengan package_id kosong/tidak valid diperlakukan sebagai non-Privat
 *    (fallback ke sesi tipe b), supaya tidak ada sesi/siswa yang "hilang".
 */
trait ComputesTutorFee
{
    /** Paket Privat — penentu apakah suatu sesi dihitung sebagai sesi (a) atau (b). */
    protected int $feePackagePrivate = 5;

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

    /**
     * Hitung komponen fee dari kumpulan sesi "done" dalam satu bulan.
     *
     * Catatan penamaan kolom (skema DB tidak berubah, hanya makna nilainya):
     *  - private_count/fee_private → jumlah SESI Privat (a), bukan jumlah siswa.
     *  - session_count/fee_session → jumlah SESI non-Privat (b), bukan seluruh sesi.
     *  - regular_count/fee_regular → TOTAL siswa hadir di seluruh sesi (c), semua paket.
     *  - day_count/fee_transport   → hari mengajar (d), tidak berubah.
     */
    protected function tutorFeeBreakdown(Tutor $tutor, Collection $schedules): array
    {
        $slotKey = fn ($s) => $s->class_date->toDateString() . '|' . $s->start_time . '|' . $s->end_time;

        $hadir = $schedules->filter(fn ($s) => optional($s->evaluation)->student_attendance === 'hadir');

        // (a & b) Klasifikasi per SESI: sesi dihitung Privat bila ada minimal satu
        // siswa paket Privat di dalamnya (sesi campuran → tetap dihitung Privat).
        $sessions = $hadir->groupBy($slotKey);
        $privateSessionCount = $sessions->filter(
            fn ($s) => $s->contains(fn ($row) => (int) optional($row->student)->package_id === $this->feePackagePrivate)
        )->count();
        $nonPrivateSessionCount = $sessions->count() - $privateSessionCount;

        // (c) Total kehadiran "hadir", dikali per kepala di setiap sesi, tanpa memandang paket.
        $totalStudentCount = $hadir->count();

        // (d) Hari mengajar: tanggal berbeda yang punya minimal satu sesi.
        $dayCount = $schedules->map(fn ($s) => $s->class_date->toDateString())->unique()->count();

        $rPrivate   = (float) ($tutor->fee_per_student_private ?? 0);
        $rStudent   = (float) ($tutor->fee_per_student ?? 0);
        $rSession   = (float) ($tutor->fee_per_session ?? 0);
        $rTransport = (float) ($tutor->fee_transport_per_day ?? 0);

        $a = $privateSessionCount * $rPrivate;
        $b = $nonPrivateSessionCount * $rSession;
        $c = $totalStudentCount * $rStudent;
        $d = $dayCount * $rTransport;

        return [
            'private_count' => $privateSessionCount,
            'regular_count' => $totalStudentCount,
            'session_count' => $nonPrivateSessionCount,
            'day_count'     => $dayCount,
            'fee_private'   => $a,
            'fee_regular'   => $c,
            'fee_session'   => $b,
            'fee_transport' => $d,
            'total'         => $a + $b + $c + $d,
        ];
    }
}
