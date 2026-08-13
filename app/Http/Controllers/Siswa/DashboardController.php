<?php

namespace App\Http\Controllers\Siswa;

use App\Models\Payment;
use App\Models\Schedule;

class DashboardController extends BaseSiswaController
{
    public function index()
    {
        $student = $this->student();
        $today = now()->toDateString();

        $base = Schedule::where('student_id', $student->id);

        $stats = [
            'quota_sessions' => (int) ($student->quota_sessions ?? 0),
            'done_sessions' => (clone $base)->where('status_schedule', 'done')->count(),
            'upcoming_sessions' => (clone $base)->where('status_schedule', 'scheduled')
                ->whereDate('class_date', '>=', $today)->count(),
        ];

        // Nilai & kehadiran hanya dari evaluasi yang SUDAH DITERBITKAN admin —
        // evaluasi draft tidak boleh terlihat oleh siswa.
        $evaluations = $this->publishedEvaluationSchedules($student->id);
        $postTests = $evaluations->filter(fn ($s) => $s->evaluation->post_test !== null)
            ->map(fn ($s) => $s->evaluation->post_test);

        $stats += [
            'avg_post_test' => $postTests->count() ? round($postTests->avg(), 1) : null,
            'hadir' => $evaluations->filter(fn ($s) => $s->evaluation->student_attendance === 'hadir')->count(),
            'izin' => $evaluations->filter(fn ($s) => $s->evaluation->student_attendance === 'izin')->count(),
            'alfa' => $evaluations->filter(fn ($s) => $s->evaluation->student_attendance === 'alfa')->count(),
        ];

        // Masa aktif belajar dari pembayaran terakhir yang punya tanggal expired
        $lastPayment = Payment::where('student_id', $student->id)
            ->whereNotNull('expired_date')
            ->orderByDesc('expired_date')
            ->first();

        $upcoming = Schedule::with(['tutor', 'subject'])
            ->where('student_id', $student->id)
            ->where('status_schedule', 'scheduled')
            ->whereDate('class_date', '>=', $today)
            ->orderBy('class_date')->orderBy('start_time')
            ->limit(5)->get();

        $recentEvaluations = $evaluations->sortByDesc(fn ($s) => $s->class_date->toDateString())->take(5);

        return view('siswa.dashboard', compact('student', 'stats', 'lastPayment', 'upcoming', 'recentEvaluations'));
    }
}
