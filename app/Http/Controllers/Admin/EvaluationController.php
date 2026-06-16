<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class EvaluationController extends Controller
{
    public function index()
    {
        $subjects = Subject::orderBy('subject_name')->get(['id', 'subject_name']);
        $grades   = Student::whereNotNull('grade')->where('grade', '!=', '')
            ->distinct()->orderBy('grade')->pluck('grade');

        return view('admin.evaluations.index', compact('subjects', 'grades'));
    }

    public function data(Request $request)
    {
        $grade   = $request->input('grade');
        $subject = $request->input('subject_id');
        $start   = $request->input('start_date');
        $end     = $request->input('end_date');

        // Satu baris = satu jadwal yang sudah dievaluasi (inner join evaluations).
        $query = Schedule::query()
            ->select('schedules.*')
            ->join('evaluations', 'evaluations.schedule_id', '=', 'schedules.id')
            ->join('students', 'students.id', '=', 'schedules.student_id')
            ->leftJoin('subjects', 'subjects.id', '=', 'schedules.subject_id')
            ->leftJoin('tutors', 'tutors.id', '=', 'schedules.tutor_id')
            ->with(['student', 'subject', 'tutor', 'evaluation.syllabus'])
            ->when($grade, fn($q) => $q->where('students.grade', $grade))
            ->when($subject, fn($q) => $q->where('schedules.subject_id', $subject))
            ->when($start, fn($q) => $q->whereDate('schedules.class_date', '>=', $start))
            ->when($end, fn($q) => $q->whereDate('schedules.class_date', '<=', $end));

        $gradeBadge = function ($g) {
            if (!$g) return '<span class="text-muted">—</span>';
            $cls = str_starts_with($g, 'A') ? 'bg-success'
                 : (str_starts_with($g, 'B') ? 'bg-primary'
                 : (str_starts_with($g, 'C') ? 'bg-warning text-dark' : 'bg-danger'));
            return '<span class="badge ' . $cls . '">' . e($g) . '</span>';
        };

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('class_date', fn($s) => \Carbon\Carbon::parse($s->class_date)->translatedFormat('d M Y')
                . '<br><small class="text-muted">' . substr($s->start_time, 0, 5) . '–' . substr($s->end_time, 0, 5) . '</small>')
            ->addColumn('student_name', fn($s) => '<div class="fw-semibold">' . e($s->student->full_name ?? '-') . '</div>'
                . '<small class="text-muted">' . e($s->student->nickname ?? '') . '</small>')
            ->addColumn('grade', fn($s) => $s->student && $s->student->grade
                ? '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">' . e($s->student->grade) . '</span>'
                : '<span class="text-muted">—</span>')
            ->addColumn('subject_name', fn($s) => e($s->subject->subject_name ?? '-'))
            ->addColumn('tutor_name', fn($s) => e($s->tutor->name ?? '-'))
            ->addColumn('materi', fn($s) => $s->evaluation && $s->evaluation->syllabus
                ? '<div class="small fw-semibold">' . e($s->evaluation->syllabus->pokok_bahasan) . '</div>'
                  . ($s->evaluation->syllabus->sub_pokok_bahasan ? '<small class="text-muted">' . e($s->evaluation->syllabus->sub_pokok_bahasan) . '</small>' : '')
                : '<span class="text-muted">—</span>')
            ->addColumn('attendance', function ($s) {
                $att = $s->evaluation->student_attendance ?? null;
                $badge = match ($att) {
                    'hadir' => 'bg-success', 'izin' => 'bg-warning text-dark', 'alfa' => 'bg-danger', default => 'bg-secondary',
                };
                return $att ? '<span class="badge ' . $badge . '">' . ucfirst($att) . '</span>' : '<span class="text-muted">—</span>';
            })
            ->addColumn('pre_test', fn($s) => $s->evaluation && $s->evaluation->pre_test !== null
                ? '<span class="badge bg-light text-dark border">' . $s->evaluation->pre_test . '</span>'
                : '<span class="text-muted">—</span>')
            ->addColumn('post_test', fn($s) => $s->evaluation && $s->evaluation->post_test !== null
                ? '<span class="badge bg-light text-dark border fs-6">' . $s->evaluation->post_test . '</span>'
                : '<span class="text-muted">—</span>')
            ->addColumn('pemahaman', fn($s) => $gradeBadge($s->evaluation->pemahaman ?? null))
            ->addColumn('poin', fn($s) => $gradeBadge($s->evaluation->poin ?? null))
            ->addColumn('action', fn($s) => '<a href="' . route('admin.evaluations.student', $s->student_id) . '" class="btn btn-sm btn-outline-primary" title="Lihat Laporan Siswa">
                            <i class="bi bi-clipboard2-data"></i>
                        </a>')
            ->rawColumns(['class_date', 'student_name', 'grade', 'materi', 'attendance', 'pre_test', 'post_test', 'pemahaman', 'poin', 'action'])
            ->make(true);
    }

    public function studentReport(Student $student)
    {
        $schedules = Schedule::with(['subject', 'tutor', 'evaluation.syllabus'])
            ->where('student_id', $student->id)
            ->where('status_schedule', 'done')
            ->orderBy('class_date', 'desc')
            ->get();

        $evaluated = $schedules->filter(fn($s) => $s->evaluation);
        $postTests = $evaluated->filter(fn($s) => $s->evaluation->post_test !== null)->map(fn($s) => $s->evaluation->post_test);

        $stats = [
            'total'     => $schedules->count(),
            'evaluated' => $evaluated->count(),
            'avg_score' => $postTests->count() ? round($postTests->avg(), 1) : null,
            'hadir'     => $evaluated->filter(fn($s) => $s->evaluation->student_attendance === 'hadir')->count(),
            'izin'      => $evaluated->filter(fn($s) => $s->evaluation->student_attendance === 'izin')->count(),
            'alfa'      => $evaluated->filter(fn($s) => $s->evaluation->student_attendance === 'alfa')->count(),
        ];

        return view('admin.evaluations.student', compact('student', 'schedules', 'stats'));
    }

    /**
     * Data server-side untuk tabel rincian evaluasi per sesi (dengan filter tanggal).
     */
    public function dataStudentReport(Request $request, Student $student)
    {
        $start = $request->input('start');
        $end   = $request->input('end');

        $query = Schedule::query()
            ->select('schedules.*')
            ->leftJoin('subjects', 'subjects.id', '=', 'schedules.subject_id')
            ->leftJoin('tutors', 'tutors.id', '=', 'schedules.tutor_id')
            ->leftJoin('evaluations', 'evaluations.schedule_id', '=', 'schedules.id')
            ->with(['subject', 'tutor', 'evaluation.syllabus'])
            ->where('schedules.student_id', $student->id)
            ->where('schedules.status_schedule', 'done')
            ->when($start, fn($q) => $q->whereDate('schedules.class_date', '>=', $start))
            ->when($end, fn($q) => $q->whereDate('schedules.class_date', '<=', $end));

        $gradeBadge = function ($g) {
            if (!$g) return '<span class="text-muted small">—</span>';
            $cls = str_starts_with($g, 'A') ? 'bg-success'
                 : (str_starts_with($g, 'B') ? 'bg-primary'
                 : (str_starts_with($g, 'C') ? 'bg-warning text-dark' : 'bg-danger'));
            return '<span class="badge ' . $cls . '">' . e($g) . '</span>';
        };
        $dash = '<span class="text-muted small">—</span>';

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('class_date', fn($s) =>
                '<div class="fw-semibold text-nowrap">' . \Carbon\Carbon::parse($s->class_date)->translatedFormat('d M Y') . '</div>'
                . '<small class="text-muted">' . substr($s->start_time, 0, 5) . ' – ' . substr($s->end_time, 0, 5) . '</small>')
            ->addColumn('subject_name', fn($s) =>
                '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">' . e($s->subject->subject_name ?? '-') . '</span>')
            ->addColumn('tutor_name', fn($s) => e($s->tutor->name ?? '-'))
            ->addColumn('materi', fn($s) => ($s->evaluation && $s->evaluation->syllabus)
                ? '<div class="small fw-semibold">' . e($s->evaluation->syllabus->pokok_bahasan) . '</div>'
                  . ($s->evaluation->syllabus->sub_pokok_bahasan ? '<small class="text-muted">' . e($s->evaluation->syllabus->sub_pokok_bahasan) . '</small>' : '')
                : $dash)
            ->addColumn('attendance', function ($s) {
                if (!$s->evaluation) return '<span class="text-muted small">—</span>';
                $att = $s->evaluation->student_attendance;
                $badge = match ($att) {
                    'hadir' => 'bg-success', 'izin' => 'bg-warning text-dark', 'alfa' => 'bg-danger', default => 'bg-secondary',
                };
                return '<span class="badge ' . $badge . '">' . ucfirst($att) . '</span>';
            })
            ->addColumn('pre_test', fn($s) => ($s->evaluation && $s->evaluation->pre_test !== null)
                ? '<span class="badge bg-light text-dark border">' . $s->evaluation->pre_test . '</span>' : $dash)
            ->addColumn('post_test', fn($s) => ($s->evaluation && $s->evaluation->post_test !== null)
                ? '<span class="badge bg-light text-dark border fs-6">' . $s->evaluation->post_test . '</span>' : $dash)
            ->addColumn('pemahaman', fn($s) => $gradeBadge($s->evaluation->pemahaman ?? null))
            ->addColumn('poin', fn($s) => $gradeBadge($s->evaluation->poin ?? null))
            ->addColumn('notes', fn($s) => $s->evaluation && $s->evaluation->tutor_notes
                ? '<span class="text-muted small">' . e($s->evaluation->tutor_notes) . '</span>' : $dash)
            ->addColumn('published', function ($s) {
                if (!$s->evaluation) return '<span class="text-muted small">Belum dievaluasi</span>';
                return $s->evaluation->is_published
                    ? '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-send-check me-1"></i>Diterbitkan</span>'
                    : '<span class="badge bg-secondary-subtle text-secondary border"><i class="bi bi-eye-slash me-1"></i>Privat</span>';
            })
            ->addColumn('action', function ($s) {
                if (!$s->evaluation) return '<span class="text-muted">—</span>';
                $p = $s->evaluation->is_published;
                return '<button class="btn btn-sm btn-outline-primary btn-toggle-publish" data-id="' . $s->evaluation->id . '" data-published="' . ($p ? 1 : 0) . '">'
                    . ($p ? '<i class="bi bi-eye-slash me-1"></i>Sembunyikan' : '<i class="bi bi-send me-1"></i>Terbitkan')
                    . '</button>';
            })
            ->rawColumns(['class_date', 'subject_name', 'materi', 'attendance', 'pre_test', 'post_test', 'pemahaman', 'poin', 'notes', 'published', 'action'])
            ->make(true);
    }

    /**
     * Download Laporan Hasil Belajar (PDF) — rata-rata per bulan per mata pelajaran,
     * plus kemampuan analisa/hafalan/kepercayaan diri, grafik sesi & radar.
     */
    public function downloadSummary(Request $request, Student $student)
    {
        $start = $request->input('start'); // format YYYY-MM
        $end   = $request->input('end');

        $startDate = $start ? \Carbon\Carbon::createFromFormat('Y-m', $start)->startOfMonth() : null;
        $endDate   = $end ? \Carbon\Carbon::createFromFormat('Y-m', $end)->endOfMonth() : null;

        $schedules = Schedule::with(['subject', 'evaluation.syllabus'])
            ->where('student_id', $student->id)
            ->whereHas('evaluation')
            ->when($startDate, fn($q) => $q->whereDate('class_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('class_date', '<=', $endDate))
            ->orderBy('class_date')
            ->get();

        if ($schedules->isNotEmpty()) {
            $startDate = $startDate ?? \Carbon\Carbon::parse($schedules->min('class_date'))->startOfMonth();
            $endDate   = $endDate ?? \Carbon\Carbon::parse($schedules->max('class_date'))->endOfMonth();
        }

        $programs = $schedules->map(fn($s) => $s->subject->subject_name ?? 'Tanpa Program')
            ->unique()->sort()->values();

        // Daftar bulan
        $months = [];
        if ($startDate && $endDate) {
            $cursor = $startDate->copy()->startOfMonth();
            $limit  = $endDate->copy()->startOfMonth();
            while ($cursor <= $limit) {
                $months[] = $cursor->copy();
                $cursor->addMonth();
            }
        }

        // Nilai mata pelajaran per (bulan, program) = rata-rata( avg post_test, avg poin→angka )
        $subjectScore = function ($items) {
            $post = $this->avgNum($items->pluck('evaluation.post_test'));
            $poin = $this->avgNum($items->map(fn($s) => Evaluation::scoreOf($s->evaluation->poin)));
            $vals = array_values(array_filter([$post, $poin], fn($v) => $v !== null));
            return count($vals) ? round(array_sum($vals) / count($vals)) : null;
        };
        // Rata-rata skor huruf (analisa/hafalan/kepercayaan) per bulan
        $abilityScore = fn($items, $field) => $this->avgNum(
            $items->map(fn($s) => Evaluation::scoreOf($s->evaluation->{$field}))
        );

        $rows = [];
        foreach ($months as $m) {
            $ym = $m->format('Y-m');
            $mItems = $schedules->filter(fn($s) => \Carbon\Carbon::parse($s->class_date)->format('Y-m') === $ym);

            $subjectVals = [];
            foreach ($programs as $prog) {
                $items = $mItems->filter(fn($s) => ($s->subject->subject_name ?? 'Tanpa Program') === $prog);
                $subjectVals[$prog] = $subjectScore($items);
            }

            $rows[] = [
                'label'       => $m->translatedFormat('F'),
                'sesi'        => $mItems->count(),
                'subjects'    => $subjectVals,
                'analisa'     => $abilityScore($mItems, 'kemampuan_analisa'),
                'hafalan'     => $abilityScore($mItems, 'kemampuan_hafalan'),
                'kepercayaan' => $abilityScore($mItems, 'kepercayaan_diri'),
            ];
        }
        $rows = array_values(array_filter($rows, fn($r) => $r['sesi'] > 0));

        // Rata-rata kolom (footer)
        $colAvg = function (callable $pick) use ($rows) {
            $vals = array_values(array_filter(array_map($pick, $rows), fn($v) => $v !== null));
            return count($vals) ? round(array_sum($vals) / count($vals)) : null;
        };
        $footer = [
            'sesi'        => count($rows) ? round(array_sum(array_column($rows, 'sesi')) / count($rows)) : 0,
            'subjects'    => [],
            'analisa'     => $colAvg(fn($r) => $r['analisa']),
            'hafalan'     => $colAvg(fn($r) => $r['hafalan']),
            'kepercayaan' => $colAvg(fn($r) => $r['kepercayaan']),
        ];
        foreach ($programs as $prog) {
            $footer['subjects'][$prog] = $colAvg(fn($r) => $r['subjects'][$prog] ?? null);
        }

        // Predikat dari rata-rata semua metrik
        $allAvgs = array_filter(array_merge(array_values($footer['subjects']), [$footer['analisa'], $footer['hafalan'], $footer['kepercayaan']]), fn($v) => $v !== null);
        $overall = count($allAvgs) ? array_sum($allAvgs) / count($allAvgs) : 0;
        $predikat = $overall >= 90 ? 'Amat Baik' : ($overall >= 80 ? 'Baik' : ($overall >= 70 ? 'Cukup' : ($overall > 0 ? 'Perlu Bimbingan' : '-')));

        // Materi per mata pelajaran (dari silabus evaluasi)
        $materi = [];
        foreach ($programs as $prog) {
            $items = $schedules->filter(fn($s) => ($s->subject->subject_name ?? 'Tanpa Program') === $prog && $s->evaluation->syllabus);
            $grouped = $items->groupBy(fn($s) => $s->evaluation->syllabus->pokok_bahasan
                . ($s->evaluation->syllabus->sub_pokok_bahasan ? ' — ' . $s->evaluation->syllabus->sub_pokok_bahasan : ''));
            $materi[$prog] = $grouped->map(fn($g, $name) => ['name' => $name, 'nilai' => $subjectScore($g)])->values()->all();
        }

        // Grafik
        $barSvg   = $this->buildBarChartSvg(array_column($rows, 'label'), array_column($rows, 'sesi'));
        $radarAxes = [];
        foreach ($programs as $prog) {
            $radarAxes[$prog] = $footer['subjects'][$prog] ?? 0;
        }
        $radarAxes['Kemampuan Analisa']  = $footer['analisa'] ?? 0;
        $radarAxes['Kemampuan Hafalan']  = $footer['hafalan'] ?? 0;
        $radarAxes['Kepercayaan Diri']   = $footer['kepercayaan'] ?? 0;
        $radarSvg = $this->buildRadarSvg($radarAxes);

        $periode = $this->periodLabel($startDate, $endDate);
        $student->loadMissing('scheduleSession');

        $logoPath = public_path('frontend/images/logo.jpeg');
        $logo = file_exists($logoPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath)) : null;

        $pdf = Pdf::loadView('admin.evaluations.summary-pdf', compact(
            'student', 'programs', 'rows', 'footer', 'predikat', 'periode', 'materi', 'barSvg', 'radarSvg', 'logo'
        ))->setPaper('a4', 'portrait');

        return $pdf->download('laporan-hasil-belajar-' . str()->slug($student->full_name) . '.pdf');
    }

    /** Bar chart sederhana (SVG) untuk jumlah sesi per bulan. */
    private function buildBarChartSvg(array $labels, array $values): string
    {
        $w = 460; $h = 200; $padL = 30; $padB = 30; $padT = 10; $padR = 10;
        $max = max(1, max($values ?: [1]));
        $max = (int) ceil($max / 2) * 2;
        $plotW = $w - $padL - $padR; $plotH = $h - $padT - $padB;
        $n = max(1, count($values));
        $bw = $plotW / $n * 0.55;
        $svg = '<svg width="' . $w . '" height="' . $h . '" xmlns="http://www.w3.org/2000/svg">';
        // sumbu Y grid
        for ($i = 0; $i <= $max; $i += max(1, (int) ($max / 4))) {
            $y = $padT + $plotH - ($i / $max) * $plotH;
            $svg .= '<line x1="' . $padL . '" y1="' . $y . '" x2="' . ($w - $padR) . '" y2="' . $y . '" stroke="#e5e7eb" stroke-width="1"/>';
            $svg .= '<text x="' . ($padL - 5) . '" y="' . ($y + 3) . '" font-size="8" text-anchor="end" fill="#666">' . $i . '</text>';
        }
        foreach ($values as $idx => $v) {
            $cx = $padL + ($idx + 0.5) * ($plotW / $n);
            $bh = ($v / $max) * $plotH;
            $x = $cx - $bw / 2;
            $y = $padT + $plotH - $bh;
            $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $bw . '" height="' . $bh . '" fill="#4299e1" rx="2"/>';
            $svg .= '<text x="' . $cx . '" y="' . ($y - 3) . '" font-size="9" text-anchor="middle" fill="#222" font-weight="bold">' . $v . '</text>';
            $svg .= '<text x="' . $cx . '" y="' . ($h - $padB + 12) . '" font-size="8" text-anchor="middle" fill="#444">' . htmlspecialchars(mb_substr($labels[$idx] ?? '', 0, 4)) . '</text>';
        }
        $svg .= '</svg>';
        return $svg;
    }

    /** Radar/spider chart (SVG) untuk profil kemampuan (skala 0-100). */
    private function buildRadarSvg(array $axes): string
    {
        $size = 240; $cx = $size / 2; $cy = $size / 2 + 6; $r = 78; $max = 100;
        $labels = array_keys($axes); $vals = array_values($axes);
        $n = count($labels);
        if ($n < 3) return '<svg width="' . $size . '" height="' . $size . '"></svg>';
        $angle = fn($i) => (-90 + $i * 360 / $n) * M_PI / 180;
        $svg = '<svg width="' . $size . '" height="' . $size . '" xmlns="http://www.w3.org/2000/svg">';
        // grid rings
        foreach ([0.25, 0.5, 0.75, 1] as $ring) {
            $pts = [];
            for ($i = 0; $i < $n; $i++) {
                $pts[] = round($cx + cos($angle($i)) * $r * $ring, 1) . ',' . round($cy + sin($angle($i)) * $r * $ring, 1);
            }
            $svg .= '<polygon points="' . implode(' ', $pts) . '" fill="none" stroke="#d1d5db" stroke-width="0.8"/>';
        }
        // axes + labels
        for ($i = 0; $i < $n; $i++) {
            $x = $cx + cos($angle($i)) * $r; $y = $cy + sin($angle($i)) * $r;
            $svg .= '<line x1="' . $cx . '" y1="' . $cy . '" x2="' . round($x, 1) . '" y2="' . round($y, 1) . '" stroke="#d1d5db" stroke-width="0.8"/>';
            $lx = $cx + cos($angle($i)) * ($r + 16); $ly = $cy + sin($angle($i)) * ($r + 12);
            $anchor = abs(cos($angle($i))) < 0.3 ? 'middle' : ($x < $cx ? 'end' : 'start');
            foreach (explode(' ', wordwrap($labels[$i], 12, "\n", true)) as $li => $line) {
                $svg .= '<text x="' . round($lx, 1) . '" y="' . round($ly + $li * 9, 1) . '" font-size="7" text-anchor="' . $anchor . '" fill="#444">' . htmlspecialchars($line) . '</text>';
            }
        }
        // data polygon
        $pts = [];
        for ($i = 0; $i < $n; $i++) {
            $ratio = max(0, min(1, ($vals[$i] ?? 0) / $max));
            $pts[] = round($cx + cos($angle($i)) * $r * $ratio, 1) . ',' . round($cy + sin($angle($i)) * $r * $ratio, 1);
        }
        $svg .= '<polygon points="' . implode(' ', $pts) . '" fill="rgba(66,153,225,0.35)" stroke="#1a4fd6" stroke-width="1.5"/>';
        foreach ($pts as $p) {
            [$px, $py] = explode(',', $p);
            $svg .= '<circle cx="' . $px . '" cy="' . $py . '" r="2" fill="#1a4fd6"/>';
        }
        $svg .= '</svg>';
        return $svg;
    }

    private function avgNum($collection): ?float
    {
        $vals = $collection->filter(fn($v) => $v !== null && $v !== '')->map(fn($v) => (float) $v);
        return $vals->count() ? round($vals->avg(), 1) : null;
    }

    private function periodLabel(?\Carbon\Carbon $start, ?\Carbon\Carbon $end): string
    {
        if (!$start || !$end) return 'Semua Periode';
        if ($start->format('Y-m') === $end->format('Y-m')) return $start->translatedFormat('F Y');
        if ($start->year === $end->year) return $start->translatedFormat('F') . ' - ' . $end->translatedFormat('F Y');
        return $start->translatedFormat('F Y') . ' - ' . $end->translatedFormat('F Y');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'schedule_id'        => 'required|exists:schedules,id',
            'syllabus_id'        => 'nullable|exists:syllabi,id',
            'student_attendance' => 'required|in:hadir,izin,alfa',
            'pre_test'           => 'nullable|integer|min:0|max:100',
            'post_test'          => 'nullable|integer|min:0|max:100',
            'pemahaman'          => ['nullable', Rule::in(Evaluation::GRADES)],
            'poin'               => ['nullable', Rule::in(Evaluation::GRADES)],
            'kemampuan_analisa'  => ['nullable', Rule::in(Evaluation::GRADES)],
            'kemampuan_hafalan'  => ['nullable', Rule::in(Evaluation::GRADES)],
            'kepercayaan_diri'   => ['nullable', Rule::in(Evaluation::GRADES)],
            'tutor_notes'        => 'nullable|string|max:1000',
        ]);

        $evaluation = Evaluation::updateOrCreate(
            ['schedule_id' => $validated['schedule_id']],
            $validated
        );

        return response()->json(['success' => true, 'message' => 'Evaluasi berhasil disimpan.', 'data' => $evaluation]);
    }

    public function update(Request $request, Evaluation $evaluation)
    {
        $validated = $request->validate([
            'syllabus_id'        => 'nullable|exists:syllabi,id',
            'student_attendance' => 'required|in:hadir,izin,alfa',
            'pre_test'           => 'nullable|integer|min:0|max:100',
            'post_test'          => 'nullable|integer|min:0|max:100',
            'pemahaman'          => ['nullable', Rule::in(Evaluation::GRADES)],
            'poin'               => ['nullable', Rule::in(Evaluation::GRADES)],
            'kemampuan_analisa'  => ['nullable', Rule::in(Evaluation::GRADES)],
            'kemampuan_hafalan'  => ['nullable', Rule::in(Evaluation::GRADES)],
            'kepercayaan_diri'   => ['nullable', Rule::in(Evaluation::GRADES)],
            'tutor_notes'        => 'nullable|string|max:1000',
        ]);

        $evaluation->update($validated);
        return response()->json(['success' => true, 'message' => 'Evaluasi berhasil diperbarui.']);
    }

    public function publish(Evaluation $evaluation)
    {
        $evaluation->update(['is_published' => !$evaluation->is_published]);
        $label = $evaluation->is_published ? 'diterbitkan ke orang tua' : 'disembunyikan';
        return response()->json(['success' => true, 'message' => "Laporan evaluasi berhasil $label.", 'is_published' => $evaluation->is_published]);
    }
}
