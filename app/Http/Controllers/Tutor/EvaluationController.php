<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Concerns\ManagesEvaluations;
use App\Models\Evaluation;
use App\Models\Schedule;
use App\Models\Syllabus;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EvaluationController extends BaseTutorController
{
    use ManagesEvaluations;

    /**
     * Daftar sesi per minggu, dikelompokkan per hari lalu per sesi (jam) — sama
     * seperti tampilan Jadwal Mingguan. Mode `pending` = belum dievaluasi
     * (selesai/lewat, belum ada evaluasi), mode `done` = sudah dievaluasi.
     */
    public function index(Request $request)
    {
        $tutor = $this->tutor();
        $mode  = $request->input('mode', 'pending');

        $anchor = $request->filled('week') ? Carbon::parse($request->week) : now();
        $start = $anchor->copy()->startOfWeek(Carbon::MONDAY);
        $end = $anchor->copy()->endOfWeek(Carbon::SUNDAY);

        $query = Schedule::with(['student', 'subject', 'evaluation'])
            ->where('tutor_id', $tutor->id)
            ->whereDate('class_date', '>=', $start->toDateString())
            ->whereDate('class_date', '<=', $end->toDateString());

        if ($mode === 'done') {
            $query->whereHas('evaluation');
        } else {
            $query->whereDoesntHave('evaluation')
                ->where(function ($q) {
                    $q->where('status_schedule', 'done')
                        ->orWhere(function ($q) {
                            $q->where('status_schedule', 'scheduled')
                                ->whereDate('class_date', '<', now()->toDateString());
                        });
                });
        }

        $schedules = $query->orderBy('class_date')->orderBy('start_time')->get();

        $byDay = $schedules->groupBy(fn ($s) => $s->class_date->toDateString());
        $days = collect(range(0, 6))->map(fn ($i) => $start->copy()->addDays($i));

        // Kelompokkan per slot jam — sama seperti Jadwal Mingguan, satu sesi bisa berisi banyak siswa.
        $slotKey = fn ($s) => $s->start_time . '|' . $s->end_time;

        $sessionsByDay = $byDay->map(fn ($items) => $items->groupBy($slotKey)
            ->map(function ($group) {
                $first = $group->first();
                return [
                    'start_time' => $first->start_time,
                    'end_time'   => $first->end_time,
                    'room'       => $group->pluck('room')->filter()->unique()->values()->implode(', ') ?: '-',
                    'subject'    => $group->pluck('subject.subject_name')->filter()->unique()->values()->implode(', ') ?: '-',
                    'students'   => $group->map(fn ($s) => [
                        'schedule_id'     => $s->id,
                        'name'            => $s->student->full_name ?? '-',
                        'grade'           => $s->student->grade ?? '',
                        'subject'         => $s->subject->subject_name ?? '-',
                        'status_schedule' => $s->status_schedule,
                        'attendance'      => $s->evaluation->student_attendance ?? null,
                        'has_evaluation'  => (bool) $s->evaluation,
                        'student_feedback' => $s->student_feedback,
                        'student_feedback_label' => $s->student_feedback_label,
                        'create_url'      => route('tutor.evaluations.create', $s->id),
                    ])->values(),
                    'count'      => $group->count(),
                    'evaluated'  => $group->filter(fn ($s) => $s->evaluation)->count(),
                ];
            })
            ->sortBy('start_time')->values());

        $sesiPerDay = $sessionsByDay->map->count();
        $totalWeek = $sessionsByDay->sum->count();

        return view('tutor.evaluations.index', [
            'tutor' => $tutor,
            'mode' => $mode,
            'days' => $days,
            'sessionsByDay' => $sessionsByDay,
            'sesiPerDay' => $sesiPerDay,
            'start' => $start,
            'end' => $end,
            'prevWeek' => $start->copy()->subWeek()->toDateString(),
            'nextWeek' => $start->copy()->addWeek()->toDateString(),
            'totalWeek' => $totalWeek,
        ]);
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

    /**
     * Simpan/ubah feedback siswa langsung dari tabel evaluasi (tanpa buka form lengkap).
     * Feedback melekat pada sesi (Schedule), jadi bisa diisi kapan saja — termasuk
     * untuk sesi yang belum dievaluasi sama sekali.
     */
    public function updateFeedback(Request $request, Schedule $schedule)
    {
        $tutor = $this->tutor();
        abort_unless($schedule->tutor_id === $tutor->id, 403);

        $validated = $request->validate([
            'student_feedback' => ['nullable', 'in:' . implode(',', array_keys(Schedule::FEEDBACK_OPTIONS))],
        ]);

        $schedule->update($validated);

        return response()->json(['message' => 'Feedback siswa berhasil disimpan.']);
    }
}
