<?php

namespace App\Http\Controllers\Admin;

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
 */
class AdministrasiDashboardController extends Controller
{
    /** Hari overdue/mendekati expired yang dianggap "reminder" (sama seperti Pengingat Pembayaran). */
    private const REMINDER_DAYS_AHEAD = 7;

    public function index(Request $request)
    {
        $now = now();

        // ── (a) Pembayaran: total keseluruhan & per bulan (12 bulan terakhir) ──
        $totalPembayaran = Payment::sum('amount');
        $monthsRange = collect(range(11, 0))->map(fn ($i) => $now->copy()->startOfMonth()->subMonths($i));
        $revenueByMonthRaw = Payment::whereBetween('payment_date', [
                $monthsRange->first()->toDateString(),
                $now->copy()->endOfMonth()->toDateString(),
            ])
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') as ym, sum(amount) as total")
            ->groupBy('ym')->pluck('total', 'ym');
        $revenuePerMonth = $monthsRange->map(fn ($m) => [
            'label' => $m->translatedFormat('M Y'),
            'total' => (float) ($revenueByMonthRaw[$m->format('Y-m')] ?? 0),
        ]);
        $revenueThisMonth = (float) ($revenueByMonthRaw[$now->format('Y-m')] ?? 0);

        // ── (b) Outstanding (piutang) — dari siswa aktif yg masuk daftar reminder (SPP lewat/mau expired) ──
        [$outstandingCount, $outstandingTotal] = $this->outstandingSummary();

        // ── (c) Payment multi-bulan (periode > 1: 2/3/6/12) — ringkasan jumlah transaksi & nilai ──
        $multiMonthQuery = Payment::whereIn('category_payment', [2, 4])->where('period', '>', 1);
        $multiMonthCount = (clone $multiMonthQuery)->count();
        $multiMonthTotal = (clone $multiMonthQuery)->sum('amount');

        // ── (d) Kepadatan sesi per hari, per minggu terpilih ──
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

        // ── (e) Reminder evaluasi belum diisi (seluruh tutor) ──
        $pendingEvalCount = Schedule::whereDoesntHave('evaluation')
            ->where(function ($q) {
                $q->where('status_schedule', 'done')
                    ->orWhere(function ($q) {
                        $q->where('status_schedule', 'scheduled')
                            ->whereDate('class_date', '<', now()->toDateString());
                    });
            })->count();

        return view('admin.administrasi.dashboard', [
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

    /** Data server-side: pembayaran multi-bulan (periode > 1) + breakdown per bulan. */
    public function dataMultiMonth(Request $request)
    {
        $query = Payment::with('student')->whereIn('category_payment', [2, 4])->where('period', '>', 1)
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
                $months = collect(range(0, $p->period - 1))->map(fn ($i) => $start->copy()->addMonths($i)->translatedFormat('M Y'));
                return $months->implode(', ');
            })
            ->make(true);
    }

    /** Data server-side: sesi yang sudah selesai/lewat tapi belum ada evaluasi (seluruh tutor). */
    public function dataPendingEvaluations(Request $request)
    {
        $query = Schedule::with(['student', 'tutor', 'subject'])
            ->whereDoesntHave('evaluation')
            ->where(function ($q) {
                $q->where('status_schedule', 'done')
                    ->orWhere(function ($q) {
                        $q->where('status_schedule', 'scheduled')
                            ->whereDate('class_date', '<', now()->toDateString());
                    });
            })
            ->orderBy('class_date')->orderBy('start_time');

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('class_date', fn ($s) => $s->class_date->translatedFormat('d M Y')
                . '<br><small class="text-muted">' . substr($s->start_time, 0, 5) . '–' . substr($s->end_time, 0, 5) . '</small>')
            ->addColumn('student_name', fn ($s) => e($s->student->full_name ?? '-'))
            ->addColumn('tutor_name', fn ($s) => e($s->tutor->name ?? '-'))
            ->addColumn('subject_name', fn ($s) => e($s->subject->subject_name ?? '-'))
            ->addColumn('days_overdue', function ($s) {
                $days = now()->startOfDay()->diffInDays($s->class_date->copy()->startOfDay(), false);
                return $days < 0 ? abs($days) . ' hari' : '-';
            })
            ->rawColumns(['class_date'])
            ->make(true);
    }
}
