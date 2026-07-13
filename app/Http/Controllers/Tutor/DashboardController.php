<?php

namespace App\Http\Controllers\Tutor;

use App\Models\Evaluation;
use App\Models\Schedule;
use Illuminate\Support\Facades\DB;

class DashboardController extends BaseTutorController
{
    public function index()
    {
        $tutor = $this->tutor();
        $now = now();

        $base = Schedule::where('tutor_id', $tutor->id);

        // Satu "sesi" = satu slot mengajar (tanggal + jam), bukan per siswa.
        // Beberapa siswa pada slot yang sama dihitung sebagai satu sesi.
        $sessionGroup = DB::raw('DISTINCT class_date, start_time, end_time');

        $stats = [
            'total_sessions' => (clone $base)->where('status_schedule', 'done')->count($sessionGroup),
            'month_sessions' => (clone $base)->where('status_schedule', 'done')
                ->whereYear('class_date', $now->year)->whereMonth('class_date', $now->month)->count($sessionGroup),
            'upcoming_sessions' => (clone $base)->where('status_schedule', 'scheduled')
                ->whereDate('class_date', '>=', $now->toDateString())->count($sessionGroup),
            'total_students' => (clone $base)->distinct('student_id')->count('student_id'),
            'month_students' => (clone $base)->whereYear('class_date', $now->year)
                ->whereMonth('class_date', $now->month)->distinct('student_id')->count('student_id'),
            'pending_evaluations' => (clone $base)->where('status_schedule', 'done')
                ->whereDoesntHave('evaluation')->count(),
        ];

        // Review hasil penilaian dari seluruh evaluasi sesi tutor ini
        $evaluations = Evaluation::whereHas('schedule', fn ($q) => $q->where('tutor_id', $tutor->id))->get();

        $avg = fn (string $field) => ($v = $evaluations->whereNotNull($field)->avg($field)) ? round($v, 1) : null;

        $review = [
            'evaluated' => $evaluations->count(),
            'published' => $evaluations->where('is_published', true)->count(),
            'avg_post_test' => $avg('post_test'),
            'avg_pemahaman' => $avg('pemahaman'),
            'avg_analisa' => $avg('kemampuan_analisa'),
            'avg_hafalan' => $avg('kemampuan_hafalan'),
            'avg_kepercayaan' => $avg('kepercayaan_diri'),
            'hadir' => $evaluations->where('student_attendance', 'hadir')->count(),
            'izin' => $evaluations->where('student_attendance', 'izin')->count(),
            'alfa' => $evaluations->where('student_attendance', 'alfa')->count(),
        ];

        $recentEvaluations = Evaluation::with(['schedule.student', 'schedule.subject', 'syllabus'])
            ->whereHas('schedule', fn ($q) => $q->where('tutor_id', $tutor->id))
            ->latest()->limit(8)->get();

        return view('tutor.dashboard', compact('tutor', 'stats', 'review', 'recentEvaluations'));
    }
}
