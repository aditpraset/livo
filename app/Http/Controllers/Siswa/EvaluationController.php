<?php

namespace App\Http\Controllers\Siswa;

use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class EvaluationController extends BaseSiswaController
{
    /** Nilai & evaluasi siswa — hanya yang sudah diterbitkan admin. */
    public function index()
    {
        $student = $this->student();

        $evaluated = $this->publishedEvaluationSchedules($student->id);
        $postTests = $evaluated->filter(fn ($s) => $s->evaluation->post_test !== null)
            ->map(fn ($s) => $s->evaluation->post_test);

        $avg = function (string $field) use ($evaluated) {
            $values = $evaluated->map(fn ($s) => $s->evaluation->{$field})->filter(fn ($v) => $v !== null);
            return $values->count() ? round($values->avg(), 1) : null;
        };

        $stats = [
            'total' => $evaluated->count(),
            'avg_post_test' => $postTests->count() ? round($postTests->avg(), 1) : null,
            'avg_pemahaman' => $avg('pemahaman'),
            'avg_analisa' => $avg('kemampuan_analisa'),
            'avg_hafalan' => $avg('kemampuan_hafalan'),
            'avg_kepercayaan' => $avg('kepercayaan_diri'),
            'hadir' => $evaluated->filter(fn ($s) => $s->evaluation->student_attendance === 'hadir')->count(),
            'izin' => $evaluated->filter(fn ($s) => $s->evaluation->student_attendance === 'izin')->count(),
            'alfa' => $evaluated->filter(fn ($s) => $s->evaluation->student_attendance === 'alfa')->count(),
        ];

        return view('siswa.evaluations.index', compact('student', 'stats'));
    }

    /** Data server-side untuk tabel rincian nilai per sesi. */
    public function data(Request $request)
    {
        $student = $this->student();

        $query = $this->publishedEvaluationQuery($student->id)
            ->when($request->input('start'), fn ($q, $v) => $q->whereDate('class_date', '>=', $v))
            ->when($request->input('end'), fn ($q, $v) => $q->whereDate('class_date', '<=', $v))
            ->orderBy('class_date', 'desc')->orderBy('start_time', 'desc');

        $dash = '<span class="text-muted">—</span>';
        $numBadge = function ($v) use ($dash) {
            if ($v === null) return $dash;
            $cls = $v >= 85 ? 'bg-success' : ($v >= 70 ? 'bg-primary' : ($v >= 60 ? 'bg-warning' : 'bg-danger'));
            return '<span class="badge ' . $cls . '">' . (int) $v . '</span>';
        };

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('class_date', fn ($s) => $s->class_date->translatedFormat('d M Y')
                . '<br><small class="text-muted">' . substr($s->start_time, 0, 5) . '–' . substr($s->end_time, 0, 5) . '</small>')
            ->addColumn('subject_name', fn ($s) => e($s->subject->subject_name ?? '-'))
            ->addColumn('tutor_name', fn ($s) => e($s->tutor->name ?? '-'))
            ->addColumn('materi', function ($s) use ($dash) {
                $m = $s->evaluation?->materi_display;
                if (!$m) return $dash;
                return '<div class="small fw-semibold">' . e($m['pokok']) . '</div>'
                    . ($m['sub'] ? '<small class="text-muted">' . e($m['sub']) . '</small>' : '');
            })
            ->addColumn('attendance', function ($s) {
                $att = $s->evaluation->student_attendance;
                $badge = match ($att) {
                    'hadir' => 'bg-success', 'izin' => 'bg-warning', default => 'bg-danger',
                };
                return '<span class="badge ' . $badge . '">' . ucfirst($att) . '</span>';
            })
            ->addColumn('post_test', fn ($s) => $numBadge($s->evaluation->post_test))
            ->addColumn('pemahaman', fn ($s) => $numBadge($s->evaluation->pemahaman))
            ->addColumn('kemampuan_analisa', fn ($s) => $numBadge($s->evaluation->kemampuan_analisa))
            ->addColumn('kemampuan_hafalan', fn ($s) => $numBadge($s->evaluation->kemampuan_hafalan))
            ->addColumn('kepercayaan_diri', fn ($s) => $numBadge($s->evaluation->kepercayaan_diri))
            ->addColumn('notes', fn ($s) => $s->evaluation->tutor_notes
                ? '<small class="text-muted">' . e($s->evaluation->tutor_notes) . '</small>' : $dash)
            ->rawColumns([
                'class_date', 'materi', 'attendance', 'post_test', 'pemahaman',
                'kemampuan_analisa', 'kemampuan_hafalan', 'kepercayaan_diri', 'notes',
            ])
            ->make(true);
    }
}
