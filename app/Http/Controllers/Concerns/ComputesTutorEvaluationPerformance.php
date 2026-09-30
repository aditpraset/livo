<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Schedule;
use App\Models\Tutor;
use Carbon\Carbon;

/**
 * Skor kinerja tutor dalam mengisi evaluasi — dipakai untuk membantu admin
 * menetapkan tutor saat lebih dari satu yang melamar satu slot jadwal.
 *
 *  - Kecepatan : rata-rata jeda (jam) dari jam selesai kelas sampai evaluasi
 *                dibuat, dihitung dari seluruh sesi `done` yang sudah
 *                dievaluasi. Makin kecil makin baik.
 *  - Ketepatan : persentase sesi `done` yang BENAR-BENAR dievaluasi (sesi
 *                `done` tanpa evaluasi = terlambat/terlewat, lihat
 *                AdministrasiDashboardController::pendingEvaluationQuery()).
 *
 * Tutor tanpa riwayat sesi `done` sama sekali diberi skor netral (50) — belum
 * ada data untuk dinilai, bukan berarti buruk.
 */
trait ComputesTutorEvaluationPerformance
{
    /**
     * @return array{kecepatan_jam: ?float, ketepatan_persen: ?float, skor_kecepatan: float, skor_ketepatan: float}
     */
    protected function tutorEvaluationPerformance(Tutor $tutor): array
    {
        $selesai = Schedule::where('tutor_id', $tutor->id)
            ->where('status_schedule', 'done')
            ->with('evaluation')
            ->get(['id', 'tutor_id', 'class_date', 'end_time']);

        $totalSelesai = $selesai->count();
        $dievaluasi   = $selesai->filter(fn ($s) => $s->evaluation);

        $ketepatanPersen = $totalSelesai > 0
            ? round(($dievaluasi->count() / $totalSelesai) * 100, 1)
            : null;

        $delaysJam = $dievaluasi
            ->map(function ($s) {
                $selesaiPada = Carbon::parse($s->class_date->toDateString() . ' ' . $s->end_time);
                return $selesaiPada->diffInMinutes($s->evaluation->created_at, false) / 60;
            })
            // Nilai negatif = evaluasi tercatat sebelum jam selesai kelas (anomali data
            // lama/migrasi) — diabaikan supaya tidak mencemari rata-rata.
            ->filter(fn ($jam) => $jam >= 0);

        $kecepatanJam = $delaysJam->count() ? round($delaysJam->avg(), 1) : null;

        // Skor 0–100, meluruh terhadap jeda: 0 jam → 100, 24 jam → 50, 72 jam → 25, dst.
        $skorKecepatan = $kecepatanJam === null ? 50.0 : round(100 / (1 + $kecepatanJam / 24), 1);
        $skorKetepatan = $ketepatanPersen ?? 50.0;

        return [
            'kecepatan_jam'    => $kecepatanJam,
            'ketepatan_persen' => $ketepatanPersen,
            'skor_kecepatan'   => $skorKecepatan,
            'skor_ketepatan'   => $skorKetepatan,
        ];
    }
}
