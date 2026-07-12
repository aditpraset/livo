<?php

namespace App\Http\Controllers\Tutor;

use App\Models\Schedule;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ScheduleController extends BaseTutorController
{
    /** Jadwal per minggu, dikelompokkan per hari (kelas/ruang & sesi/jam). */
    public function week(Request $request)
    {
        $tutor = $this->tutor();

        $anchor = $request->filled('week')
            ? Carbon::parse($request->week)
            : now();
        $start = $anchor->copy()->startOfWeek(Carbon::MONDAY);
        $end = $anchor->copy()->endOfWeek(Carbon::SUNDAY);

        $schedules = Schedule::with(['student', 'subject', 'evaluation'])
            ->where('tutor_id', $tutor->id)
            ->whereDate('class_date', '>=', $start->toDateString())
            ->whereDate('class_date', '<=', $end->toDateString())
            ->orderBy('class_date')->orderBy('start_time')
            ->get();

        // Kelompokkan per tanggal (Y-m-d) agar mudah dirender per hari Senin–Minggu
        $byDay = $schedules->groupBy(fn ($s) => $s->class_date->toDateString());

        $days = collect(range(0, 6))->map(fn ($i) => $start->copy()->addDays($i));

        return view('tutor.schedules.week', [
            'tutor' => $tutor,
            'days' => $days,
            'byDay' => $byDay,
            'start' => $start,
            'end' => $end,
            'prevWeek' => $start->copy()->subWeek()->toDateString(),
            'nextWeek' => $start->copy()->addWeek()->toDateString(),
            'totalWeek' => $schedules->count(),
        ]);
    }

    /** Detail siswa — hanya siswa yang pernah/akan diajar tutor ini. */
    public function studentDetail(Student $student)
    {
        $tutor = $this->tutor();

        $hasRelation = Schedule::where('tutor_id', $tutor->id)
            ->where('student_id', $student->id)->exists();
        abort_unless($hasRelation, 403, 'Siswa ini tidak terdaftar pada jadwal Anda.');

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

        return view('tutor.schedules.student', compact('tutor', 'student', 'stats'));
    }

    /** Data server-side untuk tabel riwayat sesi siswa bersama tutor ini. */
    public function dataStudentHistory(Student $student)
    {
        $tutor = $this->tutor();

        $hasRelation = Schedule::where('tutor_id', $tutor->id)
            ->where('student_id', $student->id)->exists();
        abort_unless($hasRelation, 403);

        $query = Schedule::with(['subject', 'evaluation.syllabus'])
            ->where('tutor_id', $tutor->id)
            ->where('student_id', $student->id)
            ->orderBy('class_date', 'desc')->orderBy('start_time', 'desc');

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('class_date', fn ($s) => $s->class_date->translatedFormat('d M Y')
                . '<br><small class="text-muted">' . substr($s->start_time, 0, 5) . '–' . substr($s->end_time, 0, 5) . '</small>')
            ->addColumn('subject_name', fn ($s) => e($s->subject->subject_name ?? '-'))
            ->addColumn('materi', function ($s) {
                $m = $s->evaluation?->materi_display;
                if (!$m) return '<span class="text-muted">—</span>';
                return '<div class="small fw-semibold">' . e($m['pokok']) . '</div>'
                    . ($m['sub'] ? '<small class="text-muted">' . e($m['sub']) . '</small>' : '');
            })
            ->addColumn('attendance', function ($s) {
                $att = $s->evaluation->student_attendance ?? null;
                if (!$att) return '<span class="text-muted">—</span>';
                $badge = match ($att) {
                    'hadir' => 'bg-success', 'izin' => 'bg-warning', default => 'bg-danger',
                };
                return '<span class="badge ' . $badge . '">' . ucfirst($att) . '</span>';
            })
            ->addColumn('post_test', function ($s) {
                $pt = $s->evaluation->post_test ?? null;
                if ($pt === null) return '<span class="text-muted">—</span>';
                $badge = $pt >= 85 ? 'bg-success' : ($pt >= 70 ? 'bg-primary' : 'bg-warning');
                return '<span class="badge ' . $badge . '">' . (int) $pt . '</span>';
            })
            ->editColumn('status_schedule', fn ($s) => match ($s->status_schedule) {
                'done' => '<span class="badge bg-success">Selesai</span>',
                'canceled' => '<span class="badge bg-danger">Batal</span>',
                default => '<span class="badge bg-info">Terjadwal</span>',
            })
            ->rawColumns(['class_date', 'materi', 'attendance', 'post_test', 'status_schedule'])
            ->make(true);
    }
}
