<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Evaluation;
use App\Models\Schedule;

/**
 * Logika bersama penyimpanan evaluasi (dipakai admin & area tutor):
 * normalisasi materi (silabus vs manual) dan sinkronisasi kuota sesi siswa.
 */
trait ManagesEvaluations
{
    /** Silabus & materi manual saling eksklusif. */
    protected function normalizeMateri(array $data): array
    {
        if (!empty($data['syllabus_id'])) {
            $data['materi_manual'] = null;
        } elseif (array_key_exists('materi_manual', $data)) {
            $data['syllabus_id'] = null;
            $data['materi_manual'] = $data['materi_manual'] ?: null;
        }
        return $data;
    }

    /**
     * Sinkronkan kuota sesi siswa terhadap evaluasi:
     * kuota berkurang 1 saat siswa "hadir" atau "alfa" dan dievaluasi, dan
     * dikembalikan jika status kehadiran diubah menjadi "izin". Penanda
     * quota_consumed mencegah pemotongan dobel saat evaluasi disimpan berulang.
     *
     * Kuota BOLEH menjadi minus (utang sesi) — siswa bisa saja sudah dijadwalkan
     * & diajar melebihi kuota yang dibayar. Minusnya otomatis terbayar begitu
     * kuota ditambah lewat pembayaran berikutnya (lihat Admin\PaymentController /
     * Admin\AdminController, yang cuma increment() biasa — jalan sendiri untuk
     * nilai negatif).
     */
    protected function syncQuota(Evaluation $evaluation): void
    {
        $student = Schedule::find($evaluation->schedule_id)?->student;
        if (!$student) {
            return;
        }

        // Kuota terpotong untuk kehadiran "hadir" maupun "alfa"; "izin" tidak memotong.
        $consumes = in_array($evaluation->student_attendance, ['hadir', 'alfa'], true);

        if ($consumes && !$evaluation->quota_consumed) {
            $student->decrement('quota_sessions');
            $evaluation->update(['quota_consumed' => true]);
        } elseif (!$consumes && $evaluation->quota_consumed) {
            $student->increment('quota_sessions');
            $evaluation->update(['quota_consumed' => false]);
        }
    }
}
