<?php

namespace App\Http\Controllers\Api\Tutor;

use App\Http\Controllers\Concerns\ManagesEvaluations;
use App\Models\Evaluation;
use App\Models\Schedule;
use App\Models\Syllabus;
use Illuminate\Http\Request;

class EvaluationController extends BaseApiTutorController
{
    use ManagesEvaluations;

    /** Daftar sesi yang evaluasinya harus diisi (selesai / sudah lewat, belum ada evaluasi), dipaginasi. */
    public function index(Request $request)
    {
        $tutor = $this->tutor();

        $pending = Schedule::with(['student', 'subject'])
            ->where('tutor_id', $tutor->id)
            ->whereDoesntHave('evaluation')
            ->where(function ($q) {
                $q->where('status_schedule', 'done')
                    ->orWhere(function ($q) {
                        $q->where('status_schedule', 'scheduled')
                            ->whereDate('class_date', '<', now()->toDateString());
                    });
            })
            ->orderBy('class_date')->orderBy('start_time')
            ->paginate($request->integer('per_page', 15));

        $pending->getCollection()->transform(fn (Schedule $s) => [
            'id' => $s->id,
            'class_date' => $s->class_date->toDateString(),
            'start_time' => substr($s->start_time, 0, 5),
            'end_time' => substr($s->end_time, 0, 5),
            'room' => $s->room,
            'status_schedule' => $s->status_schedule,
            'student' => ['id' => $s->student->id ?? null, 'full_name' => $s->student->full_name ?? null],
            'subject' => ['id' => $s->subject->id ?? null, 'subject_name' => $s->subject->subject_name ?? null],
        ]);

        return response()->json($pending);
    }

    /** Detail sesi + opsi silabus, untuk mengisi form evaluasi. */
    public function show(Schedule $schedule)
    {
        $tutor = $this->tutor();
        abort_unless($schedule->tutor_id === $tutor->id, 403);

        $schedule->load(['student', 'subject', 'evaluation.syllabus']);

        $syllabi = $schedule->subject_id
            ? Syllabus::where('subject_id', $schedule->subject_id)->orderBy('pokok_bahasan')->get(['id', 'pokok_bahasan', 'sub_pokok_bahasan'])
            : collect();

        return response()->json([
            'schedule' => $schedule,
            'syllabi' => $syllabi,
        ]);
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

        return response()->json([
            'message' => 'Evaluasi ' . ($schedule->student->full_name ?? 'siswa') . ' berhasil disimpan.',
            'evaluation' => $evaluation->fresh(),
        ]);
    }
}
