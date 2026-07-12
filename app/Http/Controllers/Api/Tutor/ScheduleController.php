<?php

namespace App\Http\Controllers\Api\Tutor;

use App\Models\Schedule;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ScheduleController extends BaseApiTutorController
{
    /** Jadwal satu minggu, dikelompokkan per hari (kelas/ruang & sesi/jam). */
    public function week(Request $request)
    {
        $tutor = $this->tutor();

        $anchor = $request->filled('week') ? Carbon::parse($request->week) : now();
        $start = $anchor->copy()->startOfWeek(Carbon::MONDAY);
        $end = $anchor->copy()->endOfWeek(Carbon::SUNDAY);

        $schedules = Schedule::with(['student', 'subject', 'evaluation'])
            ->where('tutor_id', $tutor->id)
            ->whereDate('class_date', '>=', $start->toDateString())
            ->whereDate('class_date', '<=', $end->toDateString())
            ->orderBy('class_date')->orderBy('start_time')
            ->get();

        $byDay = $schedules->groupBy(fn ($s) => $s->class_date->toDateString())
            ->map(fn ($day) => $day->map(fn (Schedule $s) => $this->scheduleArray($s))->values());

        $days = collect(range(0, 6))->map(fn ($i) => $start->copy()->addDays($i)->toDateString());

        return response()->json([
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'prev_week' => $start->copy()->subWeek()->toDateString(),
            'next_week' => $start->copy()->addWeek()->toDateString(),
            'total' => $schedules->count(),
            'days' => $days,
            'schedules_by_day' => $days->mapWithKeys(fn ($d) => [$d => $byDay->get($d, collect())->values()]),
        ]);
    }

    /** Detail siswa — hanya siswa yang pernah/akan diajar tutor ini. */
    public function studentDetail(Student $student)
    {
        $tutor = $this->tutor();
        $this->ensureOwnStudent($tutor->id, $student->id);

        $schedules = Schedule::with(['subject', 'evaluation.syllabus'])
            ->where('tutor_id', $tutor->id)
            ->where('student_id', $student->id)
            ->get();

        $evaluated = $schedules->filter(fn ($s) => $s->evaluation);
        $postTests = $evaluated->filter(fn ($s) => $s->evaluation->post_test !== null)
            ->map(fn ($s) => $s->evaluation->post_test);

        $stats = [
            'total' => $schedules->count(),
            'done' => $schedules->where('status_schedule', 'done')->count(),
            'evaluated' => $evaluated->count(),
            'avg_post_test' => $postTests->count() ? round($postTests->avg(), 1) : null,
            'hadir' => $evaluated->filter(fn ($s) => $s->evaluation->student_attendance === 'hadir')->count(),
            'izin' => $evaluated->filter(fn ($s) => $s->evaluation->student_attendance === 'izin')->count(),
            'alfa' => $evaluated->filter(fn ($s) => $s->evaluation->student_attendance === 'alfa')->count(),
        ];

        return response()->json([
            'student' => $student,
            'stats' => $stats,
        ]);
    }

    /** Riwayat sesi siswa bersama tutor ini, dipaginasi. */
    public function studentHistory(Request $request, Student $student)
    {
        $tutor = $this->tutor();
        $this->ensureOwnStudent($tutor->id, $student->id);

        $schedules = Schedule::with(['subject', 'evaluation.syllabus'])
            ->where('tutor_id', $tutor->id)
            ->where('student_id', $student->id)
            ->orderBy('class_date', 'desc')->orderBy('start_time', 'desc')
            ->paginate($request->integer('per_page', 15));

        $schedules->getCollection()->transform(fn (Schedule $s) => $this->scheduleArray($s));

        return response()->json($schedules);
    }

    private function ensureOwnStudent(int $tutorId, int $studentId): void
    {
        $hasRelation = Schedule::where('tutor_id', $tutorId)->where('student_id', $studentId)->exists();
        abort_unless($hasRelation, 403, 'Siswa ini tidak terdaftar pada jadwal Anda.');
    }

    private function scheduleArray(Schedule $s): array
    {
        return [
            'id' => $s->id,
            'class_date' => $s->class_date->toDateString(),
            'start_time' => substr($s->start_time, 0, 5),
            'end_time' => substr($s->end_time, 0, 5),
            'room' => $s->room,
            'status_schedule' => $s->status_schedule,
            'student' => $s->relationLoaded('student') ? [
                'id' => $s->student->id ?? null,
                'full_name' => $s->student->full_name ?? null,
                'grade' => $s->student->grade ?? null,
            ] : null,
            'subject' => $s->relationLoaded('subject') ? [
                'id' => $s->subject->id ?? null,
                'subject_name' => $s->subject->subject_name ?? null,
            ] : null,
            'evaluation' => $s->evaluation ? [
                'id' => $s->evaluation->id,
                'materi' => $s->evaluation->materi_display,
                'student_attendance' => $s->evaluation->student_attendance,
                'post_test' => $s->evaluation->post_test,
                'is_published' => $s->evaluation->is_published,
            ] : null,
        ];
    }
}
