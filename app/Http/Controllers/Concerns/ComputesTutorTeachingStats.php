<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Perhitungan statistik pengajaran tutor per bulan — dipakai bersama oleh
 * laporan web (PDF) dan endpoint API tutor.
 */
trait ComputesTutorTeachingStats
{
    /** Bulan dari query ?month=YYYY-MM (default bulan berjalan). */
    protected function resolveMonth(Request $request): Carbon
    {
        try {
            return $request->filled('month')
                ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
                : now()->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }

    /** Sesi selesai (beserta evaluasi) dalam satu bulan + statistiknya. */
    protected function teachingData(int $tutorId, Carbon $month): array
    {
        $schedules = Schedule::with(['student', 'subject', 'evaluation.syllabus'])
            ->where('tutor_id', $tutorId)
            ->where('status_schedule', 'done')
            ->whereYear('class_date', $month->year)
            ->whereMonth('class_date', $month->month)
            ->orderBy('class_date')->orderBy('start_time')
            ->get();

        $evaluated = $schedules->filter(fn ($s) => $s->evaluation);
        $postTests = $evaluated->filter(fn ($s) => $s->evaluation->post_test !== null)
            ->map(fn ($s) => $s->evaluation->post_test);

        $stats = [
            'done' => $schedules->count(),
            'students' => $schedules->pluck('student_id')->unique()->count(),
            'evaluated' => $evaluated->count(),
            'avg_post_test' => $postTests->count() ? round($postTests->avg(), 1) : null,
            'hadir' => $evaluated->filter(fn ($s) => $s->evaluation->student_attendance === 'hadir')->count(),
            'izin' => $evaluated->filter(fn ($s) => $s->evaluation->student_attendance === 'izin')->count(),
            'alfa' => $evaluated->filter(fn ($s) => $s->evaluation->student_attendance === 'alfa')->count(),
        ];

        return [$schedules, $stats];
    }
}
