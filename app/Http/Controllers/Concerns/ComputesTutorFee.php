<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ClassSchedule;
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
 * Tutor TETAP (kategori=tetap): tidak dibayar dari a/c/d.
 * Total fee = gaji_pokok + tunjangan_per_bulan + (kelebihan sesi × fee_per_session)
 *             + (sesi paket "sesi saja" × tarif paketnya sendiri), dengan:
 *   - Kelebihan sesi dihitung dari sesi diajar bulan itu (hanya kehadiran "hadir")
 *     dikurangi maks_sesi_tunjangan_per_bulan. Sesi paket "sesi saja"
 *     (PACKAGE_SESSION_ONLY, mis. TKA Visit) TIDAK ikut membebani kuota ini.
 *   - Jika maks_sesi_tunjangan_per_bulan belum diisi (null), dianggap tidak ada batas →
 *     tidak pernah ada fee tambahan.
 *   - Tarif kelebihan sesi memakai fee_per_session (fee per sesi semi-privat).
 *   - Paket "sesi saja" tetap dibayar penuh per sesi memakai tarif paketnya sendiri
 *     (mis. fee_tka_visit), karena beban mengajarnya di luar jadwal rutin.
 *
 * Ketentuan umum (berlaku untuk hitungan sesi/kehadiran, dipakai kedua kategori):
 *  - Sesi yang dihitung berstatus "done", dan hanya kehadiran "hadir" yang
 *    dihitung (izin/alfa tidak dihitung sama sekali).
 *  - Sesi CAMPURAN (berisi siswa dari beberapa paket sekaligus) diklasifikasi ke
 *    satu paket saja, memakai prioritas sesuai URUTAN PACKAGE_FEE_FIELD.
 *  - Siswa dengan package_id kosong/paket yang belum dipetakan memakai tarif
 *    fallback (fee_per_session), supaya tidak ada sesi/siswa yang "hilang".
 *  - Siswa yang memilih Privat (5) DAN Semi Privat (6) sekaligus: package_id
 *    utama yang tersimpan tidak mewakili aktivitas sebenarnya (selalu ikut nilai
 *    yang tersimpan saat pendaftaran, biasanya Privat). Tiap SESI-nya dipetakan
 *    dulu ke program JADWAL KELAS yang terdaftar untuk siswa tsb pada hari itu
 *    (class_schedule_ids → ClassSchedule.hari/package_id — lihat dualPackageHariMap());
 *    kalau harinya tidak terdaftar (mis. sesi pengganti), baru jatuh ke MAYORITAS
 *    jenis sesi bulan itu: sesi SENDIRIAN (tanpa siswa lain di slot yang sama)
 *    dihitung Privat, sesi RAMAI (2+ siswa) dihitung Semi Privat — lihat
 *    resolveDualPackageStudents(). Kalau keduanya tidak bisa menentukan, package_id
 *    yang tersimpan tetap dipakai.
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

    /** Paket Semi Privat — dipakai untuk resolusi siswa dual-paket (lihat resolveDualPackageStudents). */
    protected const PACKAGE_SEMI_PRIVATE = 6;

    /** Nama hari Indonesia per dayOfWeekIso Carbon (1=Senin .. 7=Minggu), dipakai untuk cocokkan ClassSchedule.hari. */
    protected const HARI_INDONESIA = [
        1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
    ];

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
                'student:id,package_id,package_ids,class_schedule_ids',
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
        $isSessionOnly = fn (int $packageId) => in_array($packageId, self::PACKAGE_SESSION_ONLY, true);

        $hadir = $schedules->filter(fn ($s) => optional($s->evaluation)->student_attendance === 'hadir');

        // (a & b) Klasifikasi per SESI ke satu paket. Sesi campuran memakai paket dengan
        // prioritas tertinggi sesuai urutan PACKAGE_FEE_FIELD (Privat menang lebih dulu).
        $sessions = $hadir->groupBy($slotKey);

        // Siswa dual-paket (Privat + Semi Privat sekaligus): tiap sesi dicocokkan dulu
        // ke jadwal kelas terdaftar siswa pada hari itu (paling akurat), baru jatuh ke
        // mayoritas sesi bulan ini bila harinya tidak terdaftar (mis. sesi pengganti).
        $paketHariTerjadwal = $this->dualPackageHariMap($sessions);
        $paketDualMayoritas = $this->resolveDualPackageStudents($sessions);

        $packageOf = function ($s) use ($paketHariTerjadwal, $paketDualMayoritas) {
            $hari = self::HARI_INDONESIA[(int) $s->class_date->dayOfWeekIso] ?? null;

            return $paketHariTerjadwal[$s->student_id][$hari]
                ?? $paketDualMayoritas[$s->student_id]
                ?? (int) (optional($s->student)->package_id ?? 0);
        };

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
        //
        // Paket "sesi saja" (PACKAGE_SESSION_ONLY, mis. TKA Visit) dibayar per sesi
        // dengan tarifnya sendiri untuk KEDUA kategori tutor — termasuk tutor tetap —
        // dan sesinya TIDAK membebani kuota tunjangan bulanan. Paket lain hanya
        // dibayar untuk freelance; bagi tutor tetap sudah tercakup gaji pokok.
        $a = 0.0;
        $b = 0.0;
        $breakdown = [];
        $quotaSessionCount = 0; // sesi yang diperhitungkan terhadap maks_sesi_tunjangan_per_bulan

        foreach ($sessionPackages->countBy()->sortKeys() as $packageId => $count) {
            $packageId   = (int) $packageId;
            $sessionOnly = $isSessionOnly($packageId);
            $dibayar     = !$isTetap || $sessionOnly;

            $rate     = $dibayar ? $this->packageSessionRate($tutor, $packageId) : 0.0;
            $subtotal = $count * $rate;

            if (!$sessionOnly) {
                $quotaSessionCount += $count;
            }

            if ($packageId === self::PACKAGE_PRIVATE) {
                $a += $subtotal;
            } else {
                $b += $subtotal;
            }

            $breakdown[] = [
                'package_id'   => $packageId,
                'label'        => $this->packageLabel($packageId),
                'count'        => $count,
                'rate'         => $rate,
                'subtotal'     => $subtotal,
                'session_only' => $sessionOnly,
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
            if ($maksSesi !== null && $quotaSessionCount > $maksSesi) {
                $extraSessionCount = $quotaSessionCount - $maksSesi;
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
            'session_breakdown'    => $breakdown ?: null,
            'total'         => $a + $b + $c + $d + $feePokok + $feeTunjangan + $feeExtraSession,
        ];
    }

    /**
     * Untuk siswa dual-paket (Privat + Semi Privat), peta HARI → package_id dari
     * jadwal kelas yang benar-benar terdaftar untuk siswa itu (class_schedule_ids
     * → ClassSchedule.hari/package_id). Ini sinyal paling akurat karena mencerminkan
     * program yang memang direncanakan per hari, bukan tebakan dari kehadiran.
     * Hari yang ClassSchedule-nya bukan Privat/Semi Privat tidak ikut dipetakan
     * (biar jatuh ke fallback mayoritas / package_id tersimpan).
     *
     * @param Collection<string, Collection<int, Schedule>> $sessions sesi (slotKey => baris kehadiran)
     * @return array<int, array<string, int>> student_id => [hari => package_id]
     */
    private function dualPackageHariMap(Collection $sessions): array
    {
        $students = [];
        foreach ($sessions as $rows) {
            foreach ($rows as $row) {
                $student = $row->student;
                if ($student) {
                    $students[$student->id] ??= $student;
                }
            }
        }

        $map = [];
        foreach ($students as $studentId => $student) {
            $ids = $student->package_id_list;
            if (!in_array(self::PACKAGE_PRIVATE, $ids, true) || !in_array(self::PACKAGE_SEMI_PRIVATE, $ids, true)) {
                continue; // bukan kasus dual Privat+Semi Privat
            }

            $scheduleIds = is_array($student->class_schedule_ids) ? array_filter($student->class_schedule_ids) : [];
            if (empty($scheduleIds)) {
                continue;
            }

            $hariPaket = ClassSchedule::whereIn('id', $scheduleIds)
                ->whereIn('package_id', [self::PACKAGE_PRIVATE, self::PACKAGE_SEMI_PRIVATE])
                ->get(['hari', 'package_id'])
                ->mapWithKeys(fn ($cs) => [$cs->hari => (int) $cs->package_id]);

            if ($hariPaket->isNotEmpty()) {
                $map[$studentId] = $hariPaket->all();
            }
        }

        return $map;
    }

    /**
     * Paket efektif untuk siswa yang memilih Privat DAN Semi Privat sekaligus,
     * ditentukan dari mayoritas jenis sesi yang benar-benar mereka jalani bulan
     * ini: sesi SENDIRIAN (tidak ada siswa lain di slot yang sama) dihitung
     * Privat, sesi RAMAI (2+ siswa dalam satu slot) dihitung Semi Privat. Seri
     * (jumlah sama) → tidak di-override, tetap pakai package_id yang tersimpan.
     * Siswa dengan satu paket saja / kombinasi paket lain tidak disentuh.
     *
     * Dipakai sebagai FALLBACK untuk sesi yang harinya tidak ada di jadwal kelas
     * terdaftar siswa (lihat dualPackageHariMap) — mis. sesi pengganti/tambahan.
     *
     * @param Collection<string, Collection<int, Schedule>> $sessions sesi (slotKey => baris kehadiran)
     * @return array<int, int> student_id => package_id efektif
     */
    private function resolveDualPackageStudents(Collection $sessions): array
    {
        $solo = [];
        $group = [];
        $students = [];

        foreach ($sessions as $rows) {
            $ramai = $rows->count() > 1;

            foreach ($rows as $row) {
                $student = $row->student;
                if (!$student) {
                    continue;
                }

                $students[$student->id] ??= $student;
                if ($ramai) {
                    $group[$student->id] = ($group[$student->id] ?? 0) + 1;
                } else {
                    $solo[$student->id] = ($solo[$student->id] ?? 0) + 1;
                }
            }
        }

        $effective = [];
        foreach ($students as $studentId => $student) {
            $ids = $student->package_id_list;
            if (!in_array(self::PACKAGE_PRIVATE, $ids, true) || !in_array(self::PACKAGE_SEMI_PRIVATE, $ids, true)) {
                continue; // bukan kasus dual Privat+Semi Privat — package_id tersimpan tetap dipakai
            }

            $soloN  = $solo[$studentId] ?? 0;
            $groupN = $group[$studentId] ?? 0;

            if ($soloN > $groupN) {
                $effective[$studentId] = self::PACKAGE_PRIVATE;
            } elseif ($groupN > $soloN) {
                $effective[$studentId] = self::PACKAGE_SEMI_PRIVATE;
            }
        }

        return $effective;
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
