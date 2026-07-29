<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Concerns\ComputesTutorTeachingStats;
use App\Models\FeePeriod;
use App\Models\Schedule;
use App\Models\TutorFee;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends BaseTutorController
{
    use ComputesTutorTeachingStats;

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

    /**
     * Rekapitulasi fee per bulan dalam satu tahun (breakdown a + b + c + d).
     * Hanya menampilkan bulan yang periodenya sudah DITERBITKAN oleh admin;
     * bulan yang masih draft/belum di-generate tampil kosong dengan keterangan.
     */
    public function rekapFee(Request $request)
    {
        $tutor = $this->tutor();
        $year = (int) ($request->input('year') ?: now()->year);

        // Ambil seluruh TutorFee milik tutor ini untuk periode published di tahun tsb (1 query).
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
                'month'     => Carbon::create($year, $m, 1),
                'published' => (bool) $tf,
            ], $tf ? $tf->only(array_keys($empty)) : []);
        });

        $sum = fn ($key) => $rows->sum($key);

        return view('tutor.reports.rekap-fee', [
            'tutor' => $tutor,
            'year'  => $year,
            'rows'  => $rows,
            'rates' => [
                'session'   => (float) ($tutor->fee_per_session ?? 0),
                'private'   => (float) ($tutor->fee_per_student_private ?? 0),
                'student'   => (float) ($tutor->fee_per_student ?? 0),
                'transport' => (float) ($tutor->fee_transport_per_day ?? 0),
            ],
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

    /** Halaman laporan: pilih bulan untuk slip gaji / summary pengajaran. */
    public function index()
    {
        $tutor = $this->tutor();
        return view('tutor.reports.index', compact('tutor'));
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
            return redirect()->route('tutor.reports.index')
                ->with('error', 'Fee bulan ' . $month->locale('id')->translatedFormat('F Y') . ' belum diterbitkan oleh admin.');
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
