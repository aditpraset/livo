<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesDashboardDateRange;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

/**
 * Dashboard Siswa — akumulasi status, pembayaran bulan berjalan,
 * pertumbuhan siswa baru, dan sebaran per jenjang.
 *
 * Rentang tanggal (default: 1 tahun penuh tahun berjalan) memengaruhi bagian
 * "penambahan siswa baru" (berdasarkan registration_date). Akumulasi status &
 * sebaran jenjang tetap kondisi terkini.
 */
class StudentDashboardController extends Controller
{
    use ResolvesDashboardDateRange;

    // Urutan jenjang yang dikenal, dari yang termuda — pakai daftar resmi yang
    // sama dengan Master Jadwal (ClassScheduleController::KELAS: TK, SD 1-6,
    // SMP 7-9, SMA 10-12) supaya konsisten. Jenjang di luar daftar ini (data
    // tidak baku) tetap ditampilkan, ditaruh di akhir.

    public function index(Request $request)
    {
        $now = now();
        [$start, $end] = $this->resolveDashboardRange($request);
        $bulanRekap = $this->resolveBulanRekap($request);

        // ── (a) Akumulasi siswa: total & status (kondisi terkini) ──
        $statusCounts = Student::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $stats = [
            'total'      => Student::count(),
            'aktif'      => (int) ($statusCounts[1] ?? 0),
            'non_aktif'  => (int) ($statusCounts[2] ?? 0),
            'cuti'       => (int) ($statusCounts[3] ?? 0),
        ];

        // Siswa aktif yang BELUM ada pembayaran SPP (kategori 2/4) di bulan berjalan.
        $unpaidCount = Student::where('status', 1)
            ->whereDoesntHave('payments', function ($q) use ($now) {
                $q->whereIn('category_payment', [2, 4])
                    ->whereYear('payment_date', $now->year)
                    ->whereMonth('payment_date', $now->month);
            })
            ->count();

        // ── (b) Penambahan siswa baru: dalam rentang, tahun berjalan, keseluruhan ──
        $newInRange = Student::whereBetween('registration_date', [$start->toDateString(), $end->toDateString()])->count();
        $newThisMonth = Student::whereYear('registration_date', $now->year)
            ->whereMonth('registration_date', $now->month)->count();
        $newThisYear = Student::whereYear('registration_date', $now->year)->count();

        // Tren per bulan sepanjang rentang.
        $monthsRange = $this->monthsBetween($start, $end);
        $byMonthRaw = Student::whereBetween('registration_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw("DATE_FORMAT(registration_date, '%Y-%m') as ym, count(*) as total")
            ->groupBy('ym')->pluck('total', 'ym');
        $newPerMonth = $monthsRange->map(fn ($m) => [
            'label' => $m->translatedFormat('M Y'),
            'total' => (int) ($byMonthRaw[$m->format('Y-m')] ?? 0),
        ]);

        // Tren 5 tahun terakhir (termasuk tahun berjalan) — tampilan jangka panjang, lepas dari rentang.
        $yearsRange = collect(range(4, 0))->map(fn ($i) => $now->year - $i);
        $byYearRaw = Student::whereYear('registration_date', '>=', $yearsRange->first())
            ->selectRaw('YEAR(registration_date) as y, count(*) as total')
            ->groupBy('y')->pluck('total', 'y');
        $newPerYear = $yearsRange->map(fn ($y) => ['label' => (string) $y, 'total' => (int) ($byYearRaw[$y] ?? 0)]);

        // ── (c) Jumlah siswa per jenjang (kondisi terkini) ──
        $byGradeRaw = Student::whereNotNull('grade')->where('grade', '!=', '')
            ->selectRaw('grade, count(*) as total')->groupBy('grade')->pluck('total', 'grade');
        $knownOrder = array_flip(ClassScheduleController::KELAS);
        $perGrade = $byGradeRaw->keys()
            ->sort(function ($a, $b) use ($knownOrder) {
                $pa = $knownOrder[$a] ?? PHP_INT_MAX;
                $pb = $knownOrder[$b] ?? PHP_INT_MAX;
                return $pa <=> $pb ?: strcmp($a, $b);
            })
            ->values()
            ->map(fn ($grade) => ['label' => $grade, 'total' => (int) $byGradeRaw[$grade]]);

        return view('admin.students.dashboard', [
            'rangeStart'   => $start,
            'rangeEnd'     => $end,
            'stats'        => $stats,
            'unpaidCount'  => $unpaidCount,
            'newInRange'   => $newInRange,
            'newThisMonth' => $newThisMonth,
            'newThisYear'  => $newThisYear,
            'newPerMonth'  => $newPerMonth,
            'newPerYear'   => $newPerYear,
            'perGrade'     => $perGrade,
            'monthLabel'   => $now->translatedFormat('F Y'),
            'bulanRekap'   => $bulanRekap,
            'bulanRekapPrev' => $bulanRekap->copy()->subMonthNoOverflow()->format('Y-m'),
            'bulanRekapNext' => $bulanRekap->copy()->addMonthNoOverflow()->format('Y-m'),
        ]);
    }

    /** Bulan (awal bulan) untuk rekap izin & alfa, dari ?bulan=YYYY-MM (default bulan berjalan). */
    private function resolveBulanRekap(Request $request): Carbon
    {
        try {
            return $request->filled('bulan')
                ? Carbon::createFromFormat('Y-m', $request->input('bulan'))->startOfMonth()
                : now()->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }

    /**
     * Data server-side: rekap izin & alfa seluruh siswa untuk satu bulan
     * (satu baris per siswa, diurutkan dari yang paling sering tidak hadir).
     */
    public function dataIzinAlfa(Request $request)
    {
        $bulan = $this->resolveBulanRekap($request);

        $rows = Schedule::with('student:id,full_name,grade', 'evaluation:id,schedule_id,student_attendance')
            ->whereHas('evaluation', fn ($q) => $q->whereIn('student_attendance', ['izin', 'alfa']))
            ->whereYear('class_date', $bulan->year)
            ->whereMonth('class_date', $bulan->month)
            ->get(['id', 'student_id', 'class_date'])
            ->filter(fn ($s) => $s->student)
            ->groupBy('student_id')
            ->map(function ($items) {
                $student = $items->first()->student;
                $izin = $items->filter(fn ($s) => $s->evaluation->student_attendance === 'izin')->count();
                $alfa = $items->filter(fn ($s) => $s->evaluation->student_attendance === 'alfa')->count();

                return [
                    'student_id'   => $student->id,
                    'student_name' => $student->full_name,
                    'grade'        => $student->grade,
                    'izin'         => $izin,
                    'alfa'         => $alfa,
                    'total'        => $izin + $alfa,
                ];
            })
            ->sortByDesc('total')
            ->values();

        return DataTables::of($rows)
            ->addIndexColumn()
            ->addColumn('student_name', fn ($r) => '<span class="fw-semibold">' . e($r['student_name']) . '</span>')
            ->addColumn('grade', fn ($r) => $r['grade']
                ? '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">' . e($r['grade']) . '</span>'
                : '<span class="text-muted">—</span>')
            ->addColumn('izin_label', fn ($r) => $r['izin'] > 0
                ? '<span class="badge bg-warning text-dark">' . $r['izin'] . '</span>'
                : '<span class="text-muted">0</span>')
            ->addColumn('alfa_label', fn ($r) => $r['alfa'] > 0
                ? '<span class="badge bg-danger">' . $r['alfa'] . '</span>'
                : '<span class="text-muted">0</span>')
            ->addColumn('action', fn ($r) => '<a href="' . route('admin.evaluations.student', $r['student_id'])
                . '" class="btn btn-sm btn-outline-primary" title="Lihat Laporan Siswa"><i class="bi bi-clipboard2-data"></i></a>')
            ->rawColumns(['student_name', 'grade', 'izin_label', 'alfa_label', 'action'])
            ->make(true);
    }

    /** Data server-side: daftar siswa aktif yang belum bayar SPP bulan berjalan. */
    public function dataUnpaid(Request $request)
    {
        $now = now();

        $query = Student::where('status', 1)
            ->whereDoesntHave('payments', function ($q) use ($now) {
                $q->whereIn('category_payment', [2, 4])
                    ->whereYear('payment_date', $now->year)
                    ->whereMonth('payment_date', $now->month);
            })
            ->orderBy('full_name');

        // Tanggal expired SPP terakhir per siswa (kalau ada), utk konteks di tabel.
        $lastExpired = Payment::whereIn('category_payment', [2, 4])
            ->whereNotNull('expired_date')
            ->selectRaw('student_id, MAX(expired_date) as last_expired')
            ->groupBy('student_id')
            ->pluck('last_expired', 'student_id');

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('grade', fn ($s) => $s->grade ?: '-')
            ->addColumn('whatsapp', fn ($s) => $s->whatsapp ?: ($s->phone ?: '-'))
            ->addColumn('last_expired', function ($s) use ($lastExpired) {
                $exp = $lastExpired[$s->id] ?? null;
                if (!$exp) {
                    return '<span class="text-muted">Belum pernah bayar SPP</span>';
                }
                $expCarbon = Carbon::parse($exp);
                $badge = $expCarbon->isPast() ? 'bg-danger' : 'bg-warning text-dark';
                $text = $expCarbon->isPast() ? 'Lewat sejak ' : 'Expired ';
                return '<span class="badge ' . $badge . '">' . $text . $expCarbon->translatedFormat('d M Y') . '</span>';
            })
            ->addColumn('action', fn ($s) => '<a href="' . route('admin.payments.create', ['student_id' => $s->id]) . '" class="btn btn-sm btn-primary">
                    <i class="bi bi-cash-coin me-1"></i>Bayar</a>')
            ->rawColumns(['last_expired', 'action'])
            ->make(true);
    }
}
