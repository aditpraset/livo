<?php

namespace App\Http\Controllers\Tutor;

use App\Models\Schedule;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends BaseTutorController
{
    /** Rekapitulasi hasil pengajaran per bulan. */
    public function rekapPengajaran(Request $request)
    {
        $tutor = $this->tutor();
        $month = $this->resolveMonth($request);

        [, $stats] = $this->teachingData($tutor->id, $month);

        return view('tutor.reports.rekap-pengajaran', compact('tutor', 'month', 'stats'));
    }

    /** Data server-side untuk tabel rekap pengajaran (filter bulan via ?month=YYYY-MM). */
    public function dataRekapPengajaran(Request $request)
    {
        $tutor = $this->tutor();
        $month = $this->resolveMonth($request);

        $query = Schedule::with(['student', 'subject', 'evaluation.syllabus'])
            ->where('tutor_id', $tutor->id)
            ->where('status_schedule', 'done')
            ->whereYear('class_date', $month->year)
            ->whereMonth('class_date', $month->month)
            ->orderBy('class_date')->orderBy('start_time');

        return \Yajra\DataTables\Facades\DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('class_date', fn ($s) => $s->class_date->translatedFormat('d M Y')
                . '<br><small class="text-muted">' . substr($s->start_time, 0, 5) . '–' . substr($s->end_time, 0, 5) . '</small>')
            ->addColumn('student_name', fn ($s) => '<div class="fw-semibold">' . e($s->student->full_name ?? '-') . '</div>'
                . '<small class="text-muted">' . e($s->student->grade ?? '') . '</small>')
            ->addColumn('subject_name', fn ($s) => e($s->subject->subject_name ?? '-'))
            ->addColumn('materi', function ($s) {
                $m = $s->evaluation?->materi_display;
                if (!$m) return '<span class="text-muted">—</span>';
                return '<div class="small fw-semibold">' . e($m['pokok']) . '</div>'
                    . ($m['sub'] ? '<small class="text-muted">' . e($m['sub']) . '</small>' : '');
            })
            ->addColumn('attendance', function ($s) {
                $att = $s->evaluation->student_attendance ?? null;
                if (!$att) return '<span class="text-muted">Belum dievaluasi</span>';
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
            ->addColumn('notes', fn ($s) => e(\Illuminate\Support\Str::limit($s->evaluation->tutor_notes ?? '', 60) ?: '—'))
            ->rawColumns(['class_date', 'student_name', 'materi', 'attendance', 'post_test'])
            ->make(true);
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
                'month' => Carbon::create($year, $m, 1),
                'sessions' => $sessions,
                'fee' => $sessions * $fee,
            ];
        });

        return view('tutor.reports.rekap-fee', [
            'tutor' => $tutor,
            'year' => $year,
            'fee' => $fee,
            'rows' => $rows,
            'totalSessions' => $rows->sum('sessions'),
            'totalFee' => $rows->sum('fee'),
        ]);
    }

    /** Halaman laporan: pilih bulan untuk slip gaji / summary pengajaran. */
    public function index()
    {
        $tutor = $this->tutor();
        return view('tutor.reports.index', compact('tutor'));
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

    /** Bulan dari query ?month=YYYY-MM (default bulan berjalan). */
    private function resolveMonth(Request $request): Carbon
    {
        try {
            return $request->filled('month')
                ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
                : now()->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }

    /** Sesi selesai (beserta evaluasi) dalam satu bulan + statistiknya. */
    private function teachingData(int $tutorId, Carbon $month): array
    {
        $schedules = Schedule::with(['student', 'subject', 'evaluation.syllabus'])
            ->where('tutor_id', $tutorId)
            ->where('status_schedule', 'done')
            ->whereYear('class_date', $month->year)
            ->whereMonth('class_date', $month->month)
            ->orderBy('class_date')->orderBy('start_time')
            ->get();

        $evaluated = $schedules->filter(fn ($s) => $s->evaluation);
        $postTests = $evaluated->filter(fn ($s) => $s->evaluation->post_test !== null)
            ->map(fn ($s) => $s->evaluation->post_test);

        $stats = [
            'done' => $schedules->count(),
            'students' => $schedules->pluck('student_id')->unique()->count(),
            'evaluated' => $evaluated->count(),
            'avg_post_test' => $postTests->count() ? round($postTests->avg(), 1) : null,
            'hadir' => $evaluated->filter(fn ($s) => $s->evaluation->student_attendance === 'hadir')->count(),
            'izin' => $evaluated->filter(fn ($s) => $s->evaluation->student_attendance === 'izin')->count(),
            'alfa' => $evaluated->filter(fn ($s) => $s->evaluation->student_attendance === 'alfa')->count(),
        ];

        return [$schedules, $stats];
    }
}
