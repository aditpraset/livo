<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\Schedule;
use App\Models\Student;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    public function studentReport(Student $student)
    {
        $schedules = Schedule::with(['subject', 'tutor', 'evaluation'])
            ->where('student_id', $student->id)
            ->where('status_schedule', 'done')
            ->orderBy('class_date', 'desc')
            ->get();

        $evaluated = $schedules->filter(fn($s) => $s->evaluation);
        $scores    = $evaluated->filter(fn($s) => $s->evaluation->score !== null)->map(fn($s) => $s->evaluation->score);

        $stats = [
            'total'     => $schedules->count(),
            'evaluated' => $evaluated->count(),
            'avg_score' => $scores->count() ? round($scores->avg(), 1) : null,
            'hadir'     => $evaluated->filter(fn($s) => $s->evaluation->student_attendance === 'hadir')->count(),
            'izin'      => $evaluated->filter(fn($s) => $s->evaluation->student_attendance === 'izin')->count(),
            'alfa'      => $evaluated->filter(fn($s) => $s->evaluation->student_attendance === 'alfa')->count(),
        ];

        return view('admin.evaluations.student', compact('student', 'schedules', 'stats'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'schedule_id'        => 'required|exists:schedules,id',
            'student_attendance' => 'required|in:hadir,izin,alfa',
            'score'              => 'nullable|integer|min:0|max:100',
            'tutor_notes'        => 'nullable|string|max:1000',
        ]);

        $evaluation = Evaluation::updateOrCreate(
            ['schedule_id' => $validated['schedule_id']],
            $validated
        );

        return response()->json(['success' => true, 'message' => 'Evaluasi berhasil disimpan.', 'data' => $evaluation]);
    }

    public function update(Request $request, Evaluation $evaluation)
    {
        $validated = $request->validate([
            'student_attendance' => 'required|in:hadir,izin,alfa',
            'score'              => 'nullable|integer|min:0|max:100',
            'tutor_notes'        => 'nullable|string|max:1000',
        ]);

        $evaluation->update($validated);
        return response()->json(['success' => true, 'message' => 'Evaluasi berhasil diperbarui.']);
    }

    public function publish(Evaluation $evaluation)
    {
        $evaluation->update(['is_published' => !$evaluation->is_published]);
        $label = $evaluation->is_published ? 'diterbitkan ke orang tua' : 'disembunyikan';
        return response()->json(['success' => true, 'message' => "Laporan evaluasi berhasil $label.", 'is_published' => $evaluation->is_published]);
    }
}
