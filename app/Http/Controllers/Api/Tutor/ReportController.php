<?php

namespace App\Http\Controllers\Api\Tutor;

use App\Http\Controllers\Concerns\ComputesTutorTeachingStats;
use App\Models\FeePeriod;
use App\Models\Schedule;
use App\Models\TutorFee;
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
            'student_feedback' => $s->student_feedback,
        ]);

        return response()->json([
            'month' => $month->format('Y-m'),
            'stats' => $stats,
            'schedules' => $schedules,
        ]);
    }

    /**
     * Rekapitulasi fee per bulan dalam satu tahun (breakdown a + b + c + d).
     * Hanya menampilkan bulan yang periodenya sudah DITERBITKAN oleh admin;
     * bulan yang masih draft/belum di-generate tampil kosong (published=false).
     */
    public function rekapFee(Request $request)
    {
        $tutor = $this->tutor();
        $year = (int) ($request->input('year') ?: now()->year);

        $published = TutorFee::with('period')
            ->where('tutor_id', $tutor->id)
            ->whereHas('period', fn ($q) => $q->whereYear('month', $year)->where('status', 'published'))
            ->get()
            ->keyBy(fn ($tf) => (int) $tf->period->month->format('n'));

        $empty = ['private_count' => 0, 'regular_count' => 0, 'session_count' => 0, 'day_count' => 0,
            'fee_private' => 0, 'fee_regular' => 0, 'fee_session' => 0, 'fee_transport' => 0, 'total' => 0];

        $rows = collect(range(1, 12))->map(function ($m) use ($published, $year, $empty) {
            $tf = $published->get($m);
            return array_merge($empty, [
                'month'       => $m,
                'month_label' => Carbon::create($year, $m, 1)->translatedFormat('F'),
                'published'   => (bool) $tf,
            ], $tf ? $tf->only(array_keys($empty)) : []);
        });

        $sum = fn ($key) => $rows->sum($key);

        return response()->json([
            'year'  => $year,
            'rates' => [
                'session'   => (float) ($tutor->fee_per_session ?? 0),
                'private'   => (float) ($tutor->fee_per_student_private ?? 0),
                'student'   => (float) ($tutor->fee_per_student ?? 0),
                'transport' => (float) ($tutor->fee_transport_per_day ?? 0),
            ],
            'rows'  => $rows,
            'totals' => [
                'private_count' => $sum('private_count'),
                'regular_count' => $sum('regular_count'),
                'session_count' => $sum('session_count'),
                'day_count'     => $sum('day_count'),
                'fee_private'   => $sum('fee_private'),
                'fee_regular'   => $sum('fee_regular'),
                'fee_session'   => $sum('fee_session'),
                'fee_transport' => $sum('fee_transport'),
                'total'         => $sum('total'),
            ],
        ]);
    }

    /** Slip gaji PDF untuk bulan terpilih. Hanya tersedia bila periode sudah diterbitkan admin. */
    public function slipGaji(Request $request)
    {
        $tutor = $this->tutor();
        $month = $this->resolveMonth($request);

        $period = FeePeriod::publishedFor($month);
        $tutorFee = $period
            ? TutorFee::where('fee_period_id', $period->id)->where('tutor_id', $tutor->id)->first()
            : null;

        if (!$tutorFee) {
            return response()->json([
                'message' => 'Fee bulan ' . $month->locale('id')->translatedFormat('F Y') . ' belum diterbitkan oleh admin.',
            ], 404);
        }

        $fee = $tutorFee->only([
            'private_count', 'regular_count', 'session_count', 'day_count',
            'fee_private', 'fee_regular', 'fee_session', 'fee_transport', 'total',
        ]);

        $pdf = Pdf::loadView('tutor.reports.pdf.slip-gaji', compact('tutor', 'month', 'fee'))
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
