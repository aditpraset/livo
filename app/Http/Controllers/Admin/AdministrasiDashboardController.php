<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesDashboardDateRange;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\ScheduleSession;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

/**
 * Dashboard Administrasi — pembayaran, piutang (outstanding), breakdown paket
 * multi-bulan, kepadatan sesi mingguan, dan reminder evaluasi belum diisi.
 *
 * Rentang tanggal (default: 1 tahun penuh tahun berjalan) memengaruhi (a) pembayaran,
 * (c) breakdown paket multi-bulan, dan (e) reminder evaluasi. Bagian (b) outstanding
 * selalu kondisi terkini, (d) kepadatan sesi punya navigasi minggu sendiri.
 */
class AdministrasiDashboardController extends Controller
{
    use ResolvesDashboardDateRange;

    /** Hari overdue/mendekati expired yang dianggap "reminder" (sama seperti Pengingat Pembayaran). */
    private const REMINDER_DAYS_AHEAD = 7;

    public function index(Request $request)
    {
        $now = now();
        [$start, $end] = $this->resolveDashboardRange($request);

        // ── (a) Pembayaran: total & per bulan dalam rentang ──
        $paymentsInRange = Payment::whereBetween('payment_date', [$start->toDateString(), $end->toDateString()]);
        $totalPembayaran = (clone $paymentsInRange)->sum('amount');

        $monthsRange = $this->monthsBetween($start, $end);
        $revenueByMonthRaw = (clone $paymentsInRange)
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') as ym, sum(amount) as total")
            ->groupBy('ym')->pluck('total', 'ym');
        $revenuePerMonth = $monthsRange->map(fn ($m) => [
            'label' => $m->translatedFormat('M Y'),
            'total' => (float) ($revenueByMonthRaw[$m->format('Y-m')] ?? 0),
        ]);
        $revenueThisMonth = (float) (Payment::whereYear('payment_date', $now->year)
            ->whereMonth('payment_date', $now->month)->sum('amount'));

        // ── (b) Outstanding (piutang) — kondisi terkini, tidak terpengaruh rentang ──
        [$outstandingCount, $outstandingTotal] = $this->outstandingSummary();

        // ── (c) Payment multi-bulan (periode > 1: 2/3/6/12) dalam rentang ──
        $multiMonthQuery = Payment::whereIn('category_payment', [2, 4])->where('period', '>', 1)
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()]);
        $multiMonthCount = (clone $multiMonthQuery)->count();
        $multiMonthTotal = (clone $multiMonthQuery)->sum('amount');

        // ── (d) Kepadatan sesi per hari, per minggu terpilih (navigasi minggu sendiri) ──
        $anchor = $request->filled('week') ? Carbon::parse($request->week) : $now;
        $weekStart = $anchor->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $anchor->copy()->endOfWeek(Carbon::SUNDAY);

        $sessions = ScheduleSession::orderBy('time_start')->get();
        $weekSchedules = Schedule::where('status_schedule', '!=', 'canceled')
            ->whereDate('class_date', '>=', $weekStart->toDateString())
            ->whereDate('class_date', '<=', $weekEnd->toDateString())
            ->get(['class_date', 'start_time', 'end_time']);

        $days = collect(range(0, 6))->map(fn ($i) => $weekStart->copy()->addDays($i));
        $densityMatrix = $sessions->map(function ($session) use ($weekSchedules, $days) {
            $timeStart = substr($session->time_start, 0, 5);
            $timeEnd = substr($session->time_end, 0, 5);
            $row = $days->map(function ($day) use ($weekSchedules, $timeStart, $timeEnd) {
                return $weekSchedules->filter(fn ($s) => $s->class_date->toDateString() === $day->toDateString()
                    && substr($s->start_time, 0, 5) === $timeStart
                    && substr($s->end_time, 0, 5) === $timeEnd
                )->count();
            });
            return [
                'session' => $session->name . ' (' . $timeStart . '–' . $timeEnd . ')',
                'counts'  => $row->values(),
                'total'   => $row->sum(),
            ];
        });

        // ── (e) Reminder evaluasi belum diisi (dalam rentang, dikelompokkan per tutor) ──
        $pendingEvals = $this->pendingEvaluationQuery($start, $end)->get(['id', 'tutor_id']);
        $pendingEvalCount = $pendingEvals->count();
        $pendingEvalTutorCount = $pendingEvals->pluck('tutor_id')->unique()->count();

        return view('admin.administrasi.dashboard', [
            'rangeStart'        => $start,
            'rangeEnd'          => $end,
            'totalPembayaran'   => $totalPembayaran,
            'revenueThisMonth'  => $revenueThisMonth,
            'revenuePerMonth'   => $revenuePerMonth,
            'outstandingCount'  => $outstandingCount,
            'outstandingTotal'  => $outstandingTotal,
            'multiMonthCount'   => $multiMonthCount,
            'multiMonthTotal'   => $multiMonthTotal,
            'days'              => $days,
            'densityMatrix'     => $densityMatrix,
            'weekStart'         => $weekStart,
            'weekEnd'           => $weekEnd,
            'prevWeek'          => $weekStart->copy()->subWeek()->toDateString(),
            'nextWeek'          => $weekStart->copy()->addWeek()->toDateString(),
            'pendingEvalCount'  => $pendingEvalCount,
            'pendingEvalTutorCount' => $pendingEvalTutorCount,
            'monthLabel'        => $now->translatedFormat('F Y'),
        ]);
    }

    /**
     * Ringkasan outstanding: siswa aktif dgn SPP terakhir sudah lewat atau
     * akan expired dlm 7 hari (sama seperti Pengingat Pembayaran), diestimasi
     * dari nominal pembayaran SPP terakhir masing-masing (bukan tagihan pasti).
     */
    private function outstandingSummary(): array
    {
        $threshold = now()->copy()->addDays(self::REMINDER_DAYS_AHEAD)->endOfDay();

        // Payment SPP terakhir (berdasarkan expired_date) per siswa — perlu baris lengkap utk ambil amount.
        $lastPaymentIds = Payment::whereIn('category_payment', [2, 4])
            ->whereNotNull('expired_date')
            ->selectRaw('MAX(id) as id')
            ->groupBy('student_id')
            ->pluck('id');
        $lastPayments = Payment::whereIn('id', $lastPaymentIds)->get()->keyBy('student_id');

        $activeStudentIds = Student::where('status', 1)->pluck('id');

        $rows = $lastPayments->filter(function ($payment, $studentId) use ($activeStudentIds, $threshold) {
            return $activeStudentIds->contains($studentId)
                && $payment->expired_date
                && Carbon::parse($payment->expired_date)->lte($threshold);
        });

        return [$rows->count(), (float) $rows->sum('amount')];
    }

    /** Data server-side: daftar outstanding (piutang) per siswa. */
    public function dataOutstanding(Request $request)
    {
        $threshold = now()->copy()->addDays(self::REMINDER_DAYS_AHEAD)->endOfDay();

        $lastPaymentIds = Payment::whereIn('category_payment', [2, 4])
            ->whereNotNull('expired_date')
            ->selectRaw('MAX(id) as id')
            ->groupBy('student_id')
            ->pluck('id');

        $rows = Payment::with('student')
            ->whereIn('id', $lastPaymentIds)
            ->whereHas('student', fn ($q) => $q->where('status', 1))
            ->whereDate('expired_date', '<=', $threshold)
            ->get()
            ->sortBy('expired_date')
            ->values();

        return DataTables::of($rows)
            ->addIndexColumn()
            ->addColumn('full_name', fn ($p) => e($p->student->full_name ?? '-'))
            ->addColumn('grade', fn ($p) => e($p->student->grade ?? '-'))
            ->addColumn('whatsapp', fn ($p) => e($p->student->whatsapp ?: ($p->student->phone ?: '-')))
            ->addColumn('expired_label', function ($p) {
                $exp = Carbon::parse($p->expired_date);
                $badge = $exp->isPast() ? 'bg-danger' : 'bg-warning text-dark';
                $text = $exp->isPast() ? 'Lewat sejak ' : 'Expired ';
                return '<span class="badge ' . $badge . '">' . $text . $exp->translatedFormat('d M Y') . '</span>';
            })
            ->addColumn('amount_label', fn ($p) => 'Rp ' . number_format($p->amount, 0, ',', '.'))
            ->rawColumns(['expired_label'])
            ->make(true);
    }

    /** Data server-side: pembayaran multi-bulan (periode > 1) + breakdown per bulan, dalam rentang. */
    public function dataMultiMonth(Request $request)
    {
        [$start, $end] = $this->resolveDashboardRange($request);

        $query = Payment::with('student')->whereIn('category_payment', [2, 4])->where('period', '>', 1)
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->orderByDesc('payment_date');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('full_name', fn ($p) => e($p->student->full_name ?? '-'))
            ->addColumn('period_label', fn ($p) => $p->period . ' Bulan')
            ->addColumn('amount_label', fn ($p) => 'Rp ' . number_format($p->amount, 0, ',', '.'))
            ->addColumn('per_month_label', fn ($p) => 'Rp ' . number_format($p->amount / $p->period, 0, ',', '.'))
            ->addColumn('coverage', function ($p) {
                if (!$p->active_date) return '-';
                $start = Carbon::parse($p->active_date)->startOfMonth();
                $months = collect(range(0, $p->period - 1))->map(fn ($i) => $start->copy()->addMonthsNoOverflow($i)->translatedFormat('M Y'));
                return $months->implode(', ');
            })
            ->make(true);
    }

    /**
     * Data server-side (e) — daftar tutor dengan jumlah sesi belum dievaluasi (dalam rentang).
     * Detail per tutor diambil lewat dataPendingEvaluationsDetail().
     */
    public function dataPendingEvaluations(Request $request)
    {
        [$start, $end] = $this->resolveDashboardRange($request);

        $rows = $this->pendingEvaluationQuery($start, $end)
            ->with('tutor:id,name')
            ->get(['id', 'tutor_id', 'class_date'])
            ->groupBy(fn ($s) => $s->tutor_id ?? 0)
            ->map(function ($group) {
                $tutorId = (int) ($group->first()->tutor_id ?? 0);
                return [
                    'tutor_id'   => $tutorId,
                    'tutor_name' => $tutorId ? ($group->first()->tutor->name ?? 'Tutor #' . $tutorId) : '— Belum ada tutor —',
                    'total'      => $group->count(),
                    'oldest'     => $group->min(fn ($s) => $s->class_date->toDateString()),
                ];
            })
            ->sortByDesc('total')
            ->values();

        return DataTables::of($rows)
            ->addIndexColumn()
            ->addColumn('tutor_label', fn ($r) => $r['tutor_id']
                ? '<span class="fw-semibold">' . e($r['tutor_name']) . '</span>'
                : '<span class="fw-semibold text-danger">' . e($r['tutor_name']) . '</span>')
            ->addColumn('total_label', fn ($r) => '<span class="badge bg-warning text-dark">' . $r['total'] . ' sesi</span>')
            ->addColumn('oldest_label', fn ($r) => Carbon::parse($r['oldest'])->translatedFormat('d M Y'))
            ->addColumn('action', fn ($r) => '<button type="button" class="btn btn-sm btn-outline-primary btn-eval-detail"'
                . ' data-tutor-id="' . $r['tutor_id'] . '" data-tutor-name="' . e($r['tutor_name']) . '">'
                . '<i class="bi bi-list-ul me-1"></i>Lihat Detail</button>')
            ->rawColumns(['tutor_label', 'total_label', 'action'])
            ->make(true);
    }

    /** Data server-side (e-detail) — sesi belum dievaluasi milik satu tutor (dalam rentang). */
    public function dataPendingEvaluationsDetail(Request $request)
    {
        [$start, $end] = $this->resolveDashboardRange($request);
        $tutorId = (int) $request->input('tutor_id');

        $query = $this->pendingEvaluationQuery($start, $end)
            ->with(['student:id,full_name,grade', 'subject:id,subject_name'])
            ->when($tutorId > 0, fn ($q) => $q->where('tutor_id', $tutorId))
            ->when($tutorId === 0, fn ($q) => $q->whereNull('tutor_id'))
            ->orderBy('class_date')->orderBy('start_time');

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('class_date', fn ($s) => $s->class_date->translatedFormat('l, d M Y')
                . '<br><small class="text-muted">' . substr($s->start_time, 0, 5) . '–' . substr($s->end_time, 0, 5) . '</small>')
            ->addColumn('student_name', fn ($s) => e($s->student->full_name ?? '-')
                . '<br><small class="text-muted">' . e($s->student->grade ?? '') . '</small>')
            ->addColumn('subject_name', fn ($s) => e($s->subject->subject_name ?? '-'))
            ->addColumn('days_overdue', function ($s) {
                $days = now()->startOfDay()->diffInDays($s->class_date->copy()->startOfDay(), false);
                return $days < 0 ? '<span class="badge bg-danger">' . abs($days) . ' hari</span>' : '<span class="text-muted">-</span>';
            })
            ->rawColumns(['class_date', 'student_name', 'days_overdue'])
            ->make(true);
    }

    /**
     * Query dasar sesi yang belum dievaluasi: tanpa evaluasi, dan sudah selesai
     * ATAU dijadwalkan tapi tanggalnya sudah lewat — dibatasi rentang $start–$end.
     */
    private function pendingEvaluationQuery(Carbon $start, Carbon $end)
    {
        return Schedule::whereDoesntHave('evaluation')
            ->whereBetween('class_date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($q) {
                $q->where('status_schedule', 'done')
                    ->orWhere(function ($q) {
                        $q->where('status_schedule', 'scheduled')
                            ->whereDate('class_date', '<', now()->toDateString());
                    });
            });
    }
}
