<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Package;
use App\Models\Schedule;
use App\Models\Tutor;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Perhitungan fee tutor per bulan.
 *
 * Tutor FREELANCE (kategori=freelance): dibayar murni dari aktivitas mengajar.
 * Satu "sesi" = satu slot mengajar (tanggal + jam + tutor), boleh diisi banyak
 * siswa sekaligus. Total fee = a + b + c + d, dengan:
 *   a) tarif sesi × jumlah SESI paket Privat (package_id 5) — flat per sesi,
 *      BUKAN dikali jumlah siswa.
 *   b) tarif sesi × jumlah SESI paket lainnya (Semi Privat, TKA Reguler, Privat
 *      Khusus, Privat SMA, dst) — flat per sesi, alternatif dari (a).
 *   c) fee_per_student         × TOTAL kehadiran siswa "hadir" di seluruh sesi
 *      bulan itu — KECUALI siswa paket "sesi saja" (lihat PACKAGE_SESSION_ONLY).
 *   d) fee_transport_per_day   × jumlah hari mengajar — hari yang HANYA berisi
 *      siswa paket "sesi saja" tidak dihitung.
 *
 * TARIF PER SESI mengikuti PAKET KELAS siswa di sesi tsb (lihat PACKAGE_FEE_FIELD),
 * masing-masing mengambil kolom tarif yang berbeda di data tutor. Rincian jumlah sesi
 * & tarif per paket disimpan di kolom `session_breakdown` supaya bisa diaudit admin.
 *
 * Tutor TETAP (kategori=tetap): tidak dibayar dari a/b/c/d (semua bernilai 0).
 * Total fee = gaji_pokok + tunjangan_per_bulan + (kelebihan sesi × fee_per_session), dengan:
 *   - Kelebihan sesi dihitung dari TOTAL sesi diajar bulan itu (semua paket,
 *     hanya kehadiran "hadir") dikurangi maks_sesi_tunjangan_per_bulan.
 *   - Jika maks_sesi_tunjangan_per_bulan belum diisi (null), dianggap tidak ada batas →
 *     tidak pernah ada fee tambahan.
 *   - Tarif kelebihan sesi memakai fee_per_session (fee per sesi semi-privat).
 *
 * Ketentuan umum (berlaku untuk hitungan sesi/kehadiran, dipakai kedua kategori):
 *  - Sesi yang dihitung berstatus "done", dan hanya kehadiran "hadir" yang
 *    dihitung (izin/alfa tidak dihitung sama sekali).
 *  - Sesi CAMPURAN (berisi siswa dari beberapa paket sekaligus) diklasifikasi ke
 *    satu paket saja, memakai prioritas sesuai URUTAN PACKAGE_FEE_FIELD.
 *  - Siswa dengan package_id kosong/paket yang belum dipetakan memakai tarif
 *    fallback (fee_per_session), supaya tidak ada sesi/siswa yang "hilang".
 */
trait ComputesTutorFee
{
    /**
     * Peta paket kelas siswa (package_id) → kolom tarif per sesi di data tutor.
     * URUTAN array = prioritas klasifikasi sesi campuran (paket paling atas menang).
     */
    protected const PACKAGE_FEE_FIELD = [
        5  => 'fee_per_student_private', // Kelas Privat
        7  => 'fee_tka_visit',          // Kelas TKA Visit — per sesi saja, lihat PACKAGE_SESSION_ONLY
        9  => 'fee_private_khusus',      // Kelas Private Khusus
        10 => 'fee_private_sma',         // Privat SMA
        8  => 'fee_tka_regular',         // TKA Reguler
        6  => 'fee_per_session',         // Kelas Semi Privat
    ];

    /** Tarif untuk siswa tanpa paket / paket yang belum dipetakan di PACKAGE_FEE_FIELD. */
    protected const PACKAGE_FEE_FALLBACK_FIELD = 'fee_per_session';

    /**
     * Paket yang dibayar PER SESI SAJA: kehadiran siswanya tidak menambah komponen (c)
     * fee per siswa, dan harinya tidak menambah komponen (d) fee transport.
     */
    protected const PACKAGE_SESSION_ONLY = [7];

    /** Paket Privat — penentu apakah suatu sesi masuk komponen (a) atau (b). */
    protected const PACKAGE_PRIVATE = 5;

    /** Cache nama paket (id => nama) untuk label rincian sesi. */
    private ?Collection $feePackageNames = null;

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
     *  - private_count/fee_private → jumlah SESI paket Privat (a), bukan jumlah siswa.
     *  - session_count/fee_session → jumlah SESI paket lainnya (b) + TOTAL fee-nya;
     *    tarif tiap sesi mengikuti paket siswa masing-masing, jadi fee_session adalah
     *    penjumlahan beberapa tarif — rinciannya ada di `session_breakdown`.
     *  - regular_count/fee_regular → siswa hadir yang berhak fee per siswa (c);
     *    siswa paket "sesi saja" (PACKAGE_SESSION_ONLY) tidak ikut dihitung.
     *  - day_count/fee_transport   → hari mengajar (d); hari yang hanya berisi siswa
     *    paket "sesi saja" tidak dihitung.
     * Semua hitungan di atas tetap dihitung apa adanya untuk SEMUA kategori tutor
     * (murni statistik aktivitas mengajar), tapi hanya freelance yang dibayar dari fee a/b/c/d.
     */
    protected function tutorFeeBreakdown(Tutor $tutor, Collection $schedules): array
    {
        $slotKey = fn ($s) => $s->class_date->toDateString() . '|' . $s->start_time . '|' . $s->end_time;
        $packageOf = fn ($s) => (int) (optional($s->student)->package_id ?? 0);
        $isSessionOnly = fn (int $packageId) => in_array($packageId, self::PACKAGE_SESSION_ONLY, true);

        $hadir = $schedules->filter(fn ($s) => optional($s->evaluation)->student_attendance === 'hadir');

        // (a & b) Klasifikasi per SESI ke satu paket. Sesi campuran memakai paket dengan
        // prioritas tertinggi sesuai urutan PACKAGE_FEE_FIELD (Privat menang lebih dulu).
        $sessions = $hadir->groupBy($slotKey);
        $sessionPackages = $sessions->map(fn ($rows) => $this->resolveSessionPackage($rows->map($packageOf)));

        $totalSessionCount   = $sessions->count();
        $privateSessionCount = $sessionPackages->filter(fn ($p) => $p === self::PACKAGE_PRIVATE)->count();
        $nonPrivateSessionCount = $totalSessionCount - $privateSessionCount;

        // (c) Kehadiran "hadir" yang berhak fee per siswa — siswa paket "sesi saja" dilewati.
        $totalStudentCount = $hadir->reject(fn ($s) => $isSessionOnly($packageOf($s)))->count();

        // (d) Hari mengajar: tanggal berbeda yang punya minimal satu sesi berbayar transport
        // (hari yang isinya hanya siswa paket "sesi saja" tidak menghasilkan transport).
        $dayCount = $schedules->reject(fn ($s) => $isSessionOnly($packageOf($s)))
            ->map(fn ($s) => $s->class_date->toDateString())->unique()->count();

        $rStudent   = (float) ($tutor->fee_per_student ?? 0);
        $rSession   = (float) ($tutor->fee_per_session ?? 0);
        $rTransport = (float) ($tutor->fee_transport_per_day ?? 0);

        $isTetap = $tutor->kategori === 'tetap';

        // (a & b) Fee sesi: tiap paket memakai kolom tarif tutor yang berbeda.
        $a = 0.0;
        $b = 0.0;
        $breakdown = [];
        foreach ($sessionPackages->countBy()->sortKeys() as $packageId => $count) {
            $packageId = (int) $packageId;
            $rate      = $this->packageSessionRate($tutor, $packageId);
            $subtotal  = $isTetap ? 0.0 : $count * $rate;

            if ($packageId === self::PACKAGE_PRIVATE) {
                $a += $subtotal;
            } else {
                $b += $subtotal;
            }

            $breakdown[] = [
                'package_id' => $packageId,
                'label'      => $this->packageLabel($packageId),
                'count'      => $count,
                'rate'       => $isTetap ? 0.0 : $rate,
                'subtotal'   => $subtotal,
            ];
        }

        // c/d hanya berlaku (dibayar) untuk tutor freelance.
        $c = $isTetap ? 0.0 : $totalStudentCount * $rStudent;
        $d = $isTetap ? 0.0 : $dayCount * $rTransport;

        // Gaji pokok + tunjangan + kelebihan sesi hanya berlaku untuk tutor tetap.
        $feePokok = 0.0;
        $feeTunjangan = 0.0;
        $extraSessionCount = 0;
        $feeExtraSession = 0.0;

        if ($isTetap) {
            $feePokok = (float) ($tutor->gaji_pokok ?? 0);
            $feeTunjangan = (float) ($tutor->tunjangan_per_bulan ?? 0);

            $maksSesi = $tutor->maks_sesi_tunjangan_per_bulan;
            if ($maksSesi !== null && $totalSessionCount > $maksSesi) {
                $extraSessionCount = $totalSessionCount - $maksSesi;
                $feeExtraSession = $extraSessionCount * $rSession;
            }
        }

        return [
            'private_count' => $privateSessionCount,
            'regular_count' => $totalStudentCount,
            'session_count' => $nonPrivateSessionCount,
            'day_count'     => $dayCount,
            'fee_private'   => $a,
            'fee_regular'   => $c,
            'fee_session'   => $b,
            'fee_transport' => $d,
            'fee_pokok'            => $feePokok,
            'fee_tunjangan'        => $feeTunjangan,
            'extra_session_count'  => $extraSessionCount,
            'fee_extra_session'    => $feeExtraSession,
            // Rincian sesi per paket (audit tarif a+b). Tutor tetap tidak dibayar per sesi.
            'session_breakdown'    => $isTetap ? null : ($breakdown ?: null),
            'total'         => $a + $b + $c + $d + $feePokok + $feeTunjangan + $feeExtraSession,
        ];
    }

    /**
     * Tentukan satu paket yang mewakili sebuah sesi dari paket seluruh siswa di dalamnya.
     * Sesi campuran memakai paket dengan prioritas tertinggi sesuai urutan PACKAGE_FEE_FIELD;
     * paket yang belum dipetakan dipakai apa adanya (nanti jatuh ke tarif fallback).
     */
    private function resolveSessionPackage(Collection $packageIds): int
    {
        foreach (array_keys(self::PACKAGE_FEE_FIELD) as $packageId) {
            if ($packageIds->contains($packageId)) {
                return $packageId;
            }
        }

        return (int) ($packageIds->first() ?? 0);
    }

    /** Tarif per sesi untuk suatu paket, diambil dari kolom tarif tutor yang sesuai. */
    private function packageSessionRate(Tutor $tutor, int $packageId): float
    {
        $field = self::PACKAGE_FEE_FIELD[$packageId] ?? self::PACKAGE_FEE_FALLBACK_FIELD;

        return (float) ($tutor->{$field} ?? 0);
    }

    /** Nama paket untuk label rincian sesi (dibekukan saat generate). */
    private function packageLabel(int $packageId): string
    {
        if ($packageId === 0) {
            return 'Tanpa Paket';
        }

        $this->feePackageNames ??= Package::pluck('package_name', 'id');

        return $this->feePackageNames[$packageId] ?? ('Paket #' . $packageId);
    }
}
