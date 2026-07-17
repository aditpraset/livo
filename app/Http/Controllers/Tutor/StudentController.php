<?php

namespace App\Http\Controllers\Tutor;

use App\Models\Schedule;
use App\Models\Student;
use Yajra\DataTables\Facades\DataTables;

class StudentController extends BaseTutorController
{
    /** Halaman daftar siswa aktif. */
    public function index()
    {
        $tutor = $this->tutor();
        return view('tutor.students.index', compact('tutor'));
    }

    /** Data server-side daftar siswa aktif (kolom disamakan dengan daftar siswa admin). */
    public function data()
    {
        $query = Student::where('status', 1)->latest();

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('full_name', function ($student) {
                return '<div class="fw-semibold">' . e($student->full_name) . '</div>
                        <small class="text-muted">' . e($student->nickname ?? '') . '</small>';
            })
            ->editColumn('status', function ($student) {
                $map = [
                    1 => ['bg-success', 'Aktif'],
                    2 => ['bg-danger', 'Non-Aktif'],
                    3 => ['bg-warning text-dark', 'Cuti'],
                ];
                [$badgeClass, $statusText] = $map[$student->status] ?? ['bg-secondary', '-'];
                return '<span class="badge ' . $badgeClass . '">' . $statusText . '</span>';
            })
            ->editColumn('program', function ($student) {
                $names = json_decode($student->program ?? '', true) ?? [];
                if (empty($names)) return '<span class="text-muted">-</span>';
                return implode('', array_map(
                    fn($n) => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">' . e($n) . '</span>',
                    $names
                ));
            })
            ->addColumn('action', function ($student) {
                return '<a href="' . route('tutor.students.show', $student->id) . '" class="btn btn-sm btn-outline-primary" title="History Evaluasi">
                            <i class="bi bi-clipboard2-data me-1"></i> History Evaluasi
                        </a>';
            })
            ->rawColumns(['full_name', 'status', 'program', 'action'])
            ->make(true);
    }

    /** Halaman history evaluasi siswa (menggantikan halaman detail siswa). */
    public function history(Student $student)
    {
        $tutor = $this->tutor();

        $evaluated = Schedule::with(['evaluation'])
            ->where('student_id', $student->id)
            ->whereHas('evaluation')
            ->get();

        $postTests = $evaluated->filter(fn ($s) => $s->evaluation->post_test !== null)
            ->map(fn ($s) => $s->evaluation->post_test);

        $stats = [
            'evaluated' => $evaluated->count(),
            'avg_post_test' => $postTests->count() ? round($postTests->avg(), 1) : null,
            'hadir' => $evaluated->filter(fn ($s) => $s->evaluation->student_attendance === 'hadir')->count(),
            'izin' => $evaluated->filter(fn ($s) => $s->evaluation->student_attendance === 'izin')->count(),
            'alfa' => $evaluated->filter(fn ($s) => $s->evaluation->student_attendance === 'alfa')->count(),
        ];

        return view('tutor.students.history', compact('tutor', 'student', 'stats'));
    }

    /** Data server-side history evaluasi siswa: silabus/materi per mapel lengkap dengan nilai. */
    public function dataHistory(Student $student)
    {
        $query = Schedule::with(['subject', 'tutor', 'evaluation.syllabus'])
            ->where('student_id', $student->id)
            ->whereHas('evaluation')
            ->orderBy('class_date', 'desc')->orderBy('start_time', 'desc');

        $numBadge = function ($v) {
            if ($v === null || $v === '') return '<span class="text-muted">—</span>';
            $cls = $v >= 85 ? 'bg-success' : ($v >= 70 ? 'bg-primary' : ($v >= 60 ? 'bg-warning text-dark' : 'bg-danger'));
            return '<span class="badge ' . $cls . '">' . (int) $v . '</span>';
        };

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
            ->addColumn('tutor_name', fn ($s) => e($s->tutor->name ?? '-'))
            ->addColumn('attendance', function ($s) {
                $att = $s->evaluation->student_attendance ?? null;
                if (!$att) return '<span class="text-muted">—</span>';
                $badge = match ($att) {
                    'hadir' => 'bg-success', 'izin' => 'bg-warning text-dark', default => 'bg-danger',
                };
                return '<span class="badge ' . $badge . '">' . ucfirst($att) . '</span>';
            })
            ->addColumn('post_test', fn ($s) => $numBadge($s->evaluation->post_test ?? null))
            ->addColumn('analisa', fn ($s) => $numBadge($s->evaluation->kemampuan_analisa ?? null))
            ->addColumn('hafalan', fn ($s) => $numBadge($s->evaluation->kemampuan_hafalan ?? null))
            ->addColumn('kepercayaan', fn ($s) => $numBadge($s->evaluation->kepercayaan_diri ?? null))
            ->addColumn('notes', function ($s) {
                $n = $s->evaluation->tutor_notes ?? null;
                return $n ? '<small>' . e(\Illuminate\Support\Str::limit($n, 60)) . '</small>' : '<span class="text-muted">—</span>';
            })
            ->rawColumns(['class_date', 'materi', 'attendance', 'post_test', 'analisa', 'hafalan', 'kepercayaan', 'notes'])
            ->make(true);
    }
}
