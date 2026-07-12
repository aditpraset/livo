<?php

namespace App\Http\Controllers\Api\Tutor;

use App\Http\Controllers\Concerns\ComputesTutorTeachingStats;
use App\Models\Schedule;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends BaseApiTutorController
{
    use ComputesTutorTeachingStats;

    /** Rekapitulasi hasil pengajaran per bulan: statistik + daftar sesi (dipaginasi). */
    public function rekapPengajaran(Request $request)
    {
        $tutor = $this->tutor();
        $month = $this->resolveMonth($request);

        [, $stats] = $this->teachingData($tutor->id, $month);

        $schedules = Schedule::with(['student', 'subject', 'evaluation.syllabus'])
            ->where('tutor_id', $tutor->id)
            ->where('status_schedule', 'done')
            ->whereYear('class_date', $month->year)
            ->whereMonth('class_date', $month->month)
            ->orderBy('class_date')->orderBy('start_time')
            ->paginate($request->integer('per_page', 15));

        $schedules->getCollection()->transform(fn (Schedule $s) => [
            'id' => $s->id,
            'class_date' => $s->class_date->toDateString(),
            'start_time' => substr($s->start_time, 0, 5),
            'end_time' => substr($s->end_time, 0, 5),
            'student' => ['id' => $s->student->id ?? null, 'full_name' => $s->student->full_name ?? null],
            'subject' => ['id' => $s->subject->id ?? null, 'subject_name' => $s->subject->subject_name ?? null],
            'materi' => $s->evaluation?->materi_display,
            'student_attendance' => $s->evaluation->student_attendance ?? null,
            'post_test' => $s->evaluation->post_test ?? null,
            'tutor_notes' => $s->evaluation->tutor_notes ?? null,
        ]);

        return response()->json([
            'month' => $month->format('Y-m'),
            'stats' => $stats,
            'schedules' => $schedules,
        ]);
    }

    /** Rekapitulasi fee per bulan dalam satu tahun. */
    public function rekapFee(Request $request)
    {
        $tutor = $this->tutor();
        $year = (int) ($request->input('year') ?: now()->year);
        $fee = (float) ($tutor->fee_per_session ?? 0);

        $counts = Schedule::where('tutor_id', $tutor->id)
            ->where('status_schedule', 'done')
            ->whereYear('class_date', $year)
            ->get(['class_date'])
            ->groupBy(fn ($s) => (int) $s->class_date->format('n'))
            ->map->count();

        $rows = collect(range(1, 12))->map(function ($m) use ($counts, $fee, $year) {
            $sessions = (int) ($counts[$m] ?? 0);
            return [
                'month' => $m,
                'month_label' => Carbon::create($year, $m, 1)->translatedFormat('F'),
                'sessions' => $sessions,
                'fee' => $sessions * $fee,
            ];
        });

        return response()->json([
            'year' => $year,
            'fee_per_session' => $fee,
            'rows' => $rows,
            'total_sessions' => $rows->sum('sessions'),
            'total_fee' => $rows->sum('fee'),
        ]);
    }

    /** Slip gaji PDF untuk bulan terpilih. */
    public function slipGaji(Request $request)
    {
        $tutor = $this->tutor();
        $month = $this->resolveMonth($request);
        $fee = (float) ($tutor->fee_per_session ?? 0);

        [, $stats] = $this->teachingData($tutor->id, $month);
        $total = $stats['done'] * $fee;

        $pdf = Pdf::loadView('tutor.reports.pdf.slip-gaji', compact('tutor', 'month', 'stats', 'fee', 'total'))
            ->setPaper('a5', 'landscape');

        return $pdf->download('slip-gaji-' . $month->format('Y-m') . '.pdf');
    }

    /** Summary pengajaran PDF untuk bulan terpilih. */
    public function summaryPengajaran(Request $request)
    {
        $tutor = $this->tutor();
        $month = $this->resolveMonth($request);

        [$schedules, $stats] = $this->teachingData($tutor->id, $month);

        $pdf = Pdf::loadView('tutor.reports.pdf.summary', compact('tutor', 'month', 'schedules', 'stats'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('summary-pengajaran-' . $month->format('Y-m') . '.pdf');
    }
}
