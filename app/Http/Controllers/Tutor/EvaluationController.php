<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Concerns\ManagesEvaluations;
use App\Models\Evaluation;
use App\Models\Schedule;
use App\Models\Syllabus;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class EvaluationController extends BaseTutorController
{
    use ManagesEvaluations;

    /** Daftar sesi yang evaluasinya harus diisi (selesai / sudah lewat, belum ada evaluasi). */
    public function index()
    {
        $tutor = $this->tutor();
        return view('tutor.evaluations.index', compact('tutor'));
    }

    /** Data server-side untuk tabel evaluasi (mode: pending = belum diisi, done = sudah diisi & bisa diedit). */
    public function data(Request $request)
    {
        $tutor = $this->tutor();
        $mode  = $request->input('mode', 'pending');

        $query = Schedule::with(['student', 'subject', 'evaluation'])
            ->where('tutor_id', $tutor->id);

        if ($mode === 'done') {
            // Sesi yang sudah dievaluasi — evaluasinya masih bisa diedit kembali
            $query->whereHas('evaluation')
                ->orderByDesc('class_date')->orderByDesc('start_time');
        } else {
            $query->whereDoesntHave('evaluation')
                ->where(function ($q) {
                    $q->where('status_schedule', 'done')
                        ->orWhere(function ($q) {
                            $q->where('status_schedule', 'scheduled')
                                ->whereDate('class_date', '<', now()->toDateString());
                        });
                })
                ->orderBy('class_date')->orderBy('start_time');
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('class_date', fn ($s) => $s->class_date->translatedFormat('d M Y')
                . '<br><small class="text-muted">' . substr($s->start_time, 0, 5) . '–' . substr($s->end_time, 0, 5) . '</small>')
            ->addColumn('student_name', fn ($s) => '<div class="fw-semibold">' . e($s->student->full_name ?? '-') . '</div>'
                . '<small class="text-muted">' . e($s->student->grade ?? '') . '</small>')
            ->addColumn('subject_name', fn ($s) => e($s->subject->subject_name ?? '-'))
            ->editColumn('room', fn ($s) => e($s->room ?: '-'))
            ->editColumn('status_schedule', function ($s) use ($mode) {
                if ($mode === 'done') {
                    $att = $s->evaluation->student_attendance ?? null;
                    $badge = match ($att) {
                        'hadir' => 'bg-success', 'izin' => 'bg-warning text-dark', 'alfa' => 'bg-danger', default => 'bg-secondary',
                    };
                    return $att ? '<span class="badge ' . $badge . '">' . ucfirst($att) . '</span>' : '<span class="text-muted">—</span>';
                }
                return $s->status_schedule === 'done'
                    ? '<span class="badge bg-success">Selesai</span>'
                    : '<span class="badge bg-warning">Lewat, belum ditandai</span>';
            })
            ->addColumn('action', fn ($s) => $s->evaluation
                ? '<a href="' . route('tutor.evaluations.create', $s->id) . '" class="btn btn-sm btn-outline-warning">
                    <i class="bi bi-pencil me-1"></i> Edit Evaluasi
                </a>'
                : '<a href="' . route('tutor.evaluations.create', $s->id) . '" class="btn btn-sm btn-primary">
                    <i class="bi bi-pencil-square me-1"></i> Isi Evaluasi
                </a>')
            ->rawColumns(['class_date', 'student_name', 'status_schedule', 'action'])
            ->make(true);
    }

    /** Form isi evaluasi untuk satu sesi. */
    public function create(Schedule $schedule)
    {
        $tutor = $this->tutor();
        abort_unless($schedule->tutor_id === $tutor->id, 403);

        $schedule->load(['student', 'subject', 'evaluation']);

        $syllabi = $schedule->subject_id
            ? Syllabus::where('subject_id', $schedule->subject_id)->orderBy('pokok_bahasan')->get()
            : collect();

        return view('tutor.evaluations.create', compact('tutor', 'schedule', 'syllabi'));
    }

    /** Simpan evaluasi (buat baru atau perbarui bila sudah ada). */
    public function store(Request $request, Schedule $schedule)
    {
        $tutor = $this->tutor();
        abort_unless($schedule->tutor_id === $tutor->id, 403);

        $validated = $request->validate([
            'syllabus_id'        => 'nullable|exists:syllabi,id',
            'materi_manual'      => 'nullable|string|max:255',
            'student_attendance' => 'required|in:hadir,izin,alfa',
            'post_test'          => 'nullable|integer|min:1|max:100',
            'pemahaman'          => 'nullable|integer|min:1|max:100',
            'kemampuan_analisa'  => 'nullable|integer|min:1|max:100',
            'kemampuan_hafalan'  => 'nullable|integer|min:1|max:100',
            'kepercayaan_diri'   => 'nullable|integer|min:1|max:100',
            'tutor_notes'        => 'nullable|string|max:1000',
        ]);

        $validated = $this->normalizeMateri($validated);

        $evaluation = Evaluation::updateOrCreate(
            ['schedule_id' => $schedule->id],
            $validated
        );

        // Sesi yang sudah lewat otomatis ditandai selesai saat evaluasinya diisi
        if ($schedule->status_schedule === 'scheduled') {
            $schedule->update(['status_schedule' => 'done']);
        }

        $this->syncQuota($evaluation);

        return redirect()->route('tutor.evaluations.index')
            ->with('success', 'Evaluasi ' . ($schedule->student->full_name ?? 'siswa') . ' berhasil disimpan.');
    }
}
