<?php

namespace App\Http\Controllers\Api\Tutor;

use App\Models\Evaluation;
use App\Models\Schedule;

class DashboardController extends BaseApiTutorController
{
    public function index()
    {
        $tutor = $this->tutor();
        $now = now();

        $base = Schedule::where('tutor_id', $tutor->id);

        $stats = [
            'total_sessions' => (clone $base)->where('status_schedule', 'done')->count(),
            'month_sessions' => (clone $base)->where('status_schedule', 'done')
                ->whereYear('class_date', $now->year)->whereMonth('class_date', $now->month)->count(),
            'upcoming_sessions' => (clone $base)->where('status_schedule', 'scheduled')
                ->whereDate('class_date', '>=', $now->toDateString())->count(),
            'total_students' => (clone $base)->distinct('student_id')->count('student_id'),
            'month_students' => (clone $base)->whereYear('class_date', $now->year)
                ->whereMonth('class_date', $now->month)->distinct('student_id')->count('student_id'),
            'pending_evaluations' => (clone $base)->where('status_schedule', 'done')
                ->whereDoesntHave('evaluation')->count(),
        ];

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
            ->latest()->limit(8)->get()
            ->map(fn (Evaluation $ev) => [
                'id' => $ev->id,
                'student_name' => $ev->schedule->student->full_name ?? null,
                'subject_name' => $ev->schedule->subject->subject_name ?? null,
                'class_date' => optional($ev->schedule->class_date)->toDateString(),
                'post_test' => $ev->post_test,
                'is_published' => $ev->is_published,
            ]);

        return response()->json([
            'tutor' => $tutor,
            'stats' => $stats,
            'review' => $review,
            'recent_evaluations' => $recentEvaluations,
        ]);
    }
}
