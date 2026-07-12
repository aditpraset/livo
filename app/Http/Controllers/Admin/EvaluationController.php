<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yajra\DataTables\Facades\DataTables;

class EvaluationController extends Controller
{
    use \App\Http\Controllers\Concerns\ManagesEvaluations;

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

        $numBadge = function ($v) {
            if ($v === null || $v === '') return '<span class="text-muted">—</span>';
            $cls = $v >= 85 ? 'bg-success' : ($v >= 70 ? 'bg-primary' : ($v >= 60 ? 'bg-warning text-dark' : 'bg-danger'));
            return '<span class="badge ' . $cls . '">' . (int) $v . '</span>';
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
            ->addColumn('materi', function ($s) {
                $m = $s->evaluation?->materi_display;
                if (!$m) return '<span class="text-muted">—</span>';
                return '<div class="small fw-semibold">' . e($m['pokok']) . '</div>'
                    . ($m['sub'] ? '<small class="text-muted">' . e($m['sub']) . '</small>' : '');
            })
            ->addColumn('attendance', function ($s) {
                $att = $s->evaluation->student_attendance ?? null;
                $badge = match ($att) {
                    'hadir' => 'bg-success', 'izin' => 'bg-warning text-dark', 'alfa' => 'bg-danger', default => 'bg-secondary',
                };
                return $att ? '<span class="badge ' . $badge . '">' . ucfirst($att) . '</span>' : '<span class="text-muted">—</span>';
            })
            ->addColumn('post_test', fn($s) => $s->evaluation && $s->evaluation->post_test !== null
                ? '<span class="badge bg-light text-dark border fs-6">' . $s->evaluation->post_test . '</span>'
                : '<span class="text-muted">—</span>')
            ->addColumn('action', fn($s) => '<a href="' . route('admin.evaluations.student', $s->student_id) . '" class="btn btn-sm btn-outline-primary" title="Lihat Laporan Siswa">
                            <i class="bi bi-clipboard2-data"></i>
                        </a>')
            ->rawColumns(['class_date', 'student_name', 'grade', 'materi', 'attendance', 'post_test', 'action'])
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

        $dash = '<span class="text-muted small">—</span>';
        $numBadge = function ($v) use ($dash) {
            if ($v === null || $v === '') return $dash;
            $cls = $v >= 85 ? 'bg-success' : ($v >= 70 ? 'bg-primary' : ($v >= 60 ? 'bg-warning text-dark' : 'bg-danger'));
            return '<span class="badge ' . $cls . '">' . (int) $v . '</span>';
        };

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('class_date', fn($s) =>
                '<div class="fw-semibold text-nowrap">' . \Carbon\Carbon::parse($s->class_date)->translatedFormat('d M Y') . '</div>'
                . '<small class="text-muted">' . substr($s->start_time, 0, 5) . ' – ' . substr($s->end_time, 0, 5) . '</small>')
            ->addColumn('subject_name', fn($s) =>
                '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">' . e($s->subject->subject_name ?? '-') . '</span>')
            ->addColumn('tutor_name', fn($s) => e($s->tutor->name ?? '-'))
            ->addColumn('materi', function ($s) use ($dash) {
                $m = $s->evaluation?->materi_display;
                if (!$m) return $dash;
                return '<div class="small fw-semibold">' . e($m['pokok']) . '</div>'
                    . ($m['sub'] ? '<small class="text-muted">' . e($m['sub']) . '</small>' : '');
            })
            ->addColumn('attendance', function ($s) {
                if (!$s->evaluation) return '<span class="text-muted small">—</span>';
                $att = $s->evaluation->student_attendance;
                $badge = match ($att) {
                    'hadir' => 'bg-success', 'izin' => 'bg-warning text-dark', 'alfa' => 'bg-danger', default => 'bg-secondary',
                };
                return '<span class="badge ' . $badge . '">' . ucfirst($att) . '</span>';
            })
            ->addColumn('post_test', fn($s) => ($s->evaluation && $s->evaluation->post_test !== null)
                ? '<span class="badge bg-light text-dark border fs-6">' . $s->evaluation->post_test . '</span>' : $dash)
            ->addColumn('kemampuan_analisa', fn($s) => $numBadge($s->evaluation->kemampuan_analisa ?? null))
            ->addColumn('kemampuan_hafalan', fn($s) => $numBadge($s->evaluation->kemampuan_hafalan ?? null))
            ->addColumn('kepercayaan_diri', fn($s) => $numBadge($s->evaluation->kepercayaan_diri ?? null))
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
            ->rawColumns(['class_date', 'subject_name', 'materi', 'attendance', 'post_test', 'kemampuan_analisa', 'kemampuan_hafalan', 'kepercayaan_diri', 'notes', 'published', 'action'])
            ->make(true);
    }

    /**
     * Export Excel rincian evaluasi per sesi untuk satu siswa (mengikuti filter tanggal).
     */
    public function exportStudentExcel(Request $request, Student $student)
    {
        $start = $request->input('start');
        $end   = $request->input('end');

        $schedules = Schedule::with(['subject', 'tutor', 'evaluation.syllabus'])
            ->where('student_id', $student->id)
            ->where('status_schedule', 'done')
            ->when($start, fn($q) => $q->whereDate('class_date', '>=', $start))
            ->when($end, fn($q) => $q->whereDate('class_date', '<=', $end))
            ->orderBy('class_date')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Evaluasi');

        // Judul & info siswa
        $sheet->setCellValue('A1', 'Laporan Evaluasi Siswa');
        $sheet->setCellValue('A2', 'Nama');
        $sheet->setCellValue('B2', $student->full_name);
        $sheet->setCellValue('A3', 'Kelas');
        $sheet->setCellValue('B3', $student->grade ?? '-');
        $sheet->setCellValue('A4', 'Periode');
        $sheet->setCellValue('B4', trim(($start ?: 'Awal') . ' s/d ' . ($end ?: 'Sekarang')));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2:A4')->getFont()->setBold(true);

        $headers = [
            'No', 'Tanggal', 'Waktu', 'Mata Pelajaran', 'Tutor', 'Sub Pokok Bahasan',
            'Kehadiran', 'Nilai', 'Kemampuan Analisa', 'Kemampuan Hafalan',
            'Kepercayaan Diri', 'Catatan Tutor', 'Status Laporan',
        ];
        $headerRow = 6;
        $sheet->fromArray($headers, null, 'A' . $headerRow);

        $r = $headerRow + 1;
        $no = 1;
        foreach ($schedules as $s) {
            $ev = $s->evaluation;
            $materi = $ev?->materi_text ?? '';
            $sheet->fromArray([[
                $no++,
                \Carbon\Carbon::parse($s->class_date)->format('Y-m-d'),
                substr($s->start_time, 0, 5) . ' - ' . substr($s->end_time, 0, 5),
                $s->subject->subject_name ?? '-',
                $s->tutor->name ?? '-',
                $materi,
                $ev ? ucfirst($ev->student_attendance) : 'Belum dievaluasi',
                $ev?->post_test ?? '',
                $ev?->kemampuan_analisa ?? '',
                $ev?->kemampuan_hafalan ?? '',
                $ev?->kepercayaan_diri ?? '',
                $ev?->tutor_notes ?? '',
                $ev ? ($ev->is_published ? 'Diterbitkan' : 'Privat') : '-',
            ]], null, 'A' . $r++);
        }

        if ($schedules->isEmpty()) {
            $sheet->setCellValue('A' . $r, 'Tidak ada data evaluasi pada periode ini.');
        }

        $lastCol = $sheet->getHighestColumn();
        $sheet->getStyle('A' . $headerRow . ':' . $lastCol . $headerRow)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A' . $headerRow . ':' . $lastCol . $headerRow)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2C3E73');
        $sheet->getStyle('A' . $headerRow . ':' . $lastCol . $headerRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        for ($c = 1; $c <= Coordinate::columnIndexFromString($lastCol); $c++) {
            $sheet->getColumnDimensionByColumn($c)->setWidth(20);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'evaluasi-' . str()->slug($student->full_name) . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
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

        // Nilai mata pelajaran per (bulan, program) = rata-rata nilai (post_test)
        $subjectScore = function ($items) {
            $post = $this->avgNum($items->pluck('evaluation.post_test'));
            return $post !== null ? round($post) : null;
        };
        // Rata-rata angka kemampuan (analisa/hafalan/kepercayaan) per bulan
        $abilityScore = fn($items, $field) => $this->avgNum(
            $items->map(fn($s) => $s->evaluation->{$field})
        );

        // Akumulasi Sesi tidak menghitung sesi alfa (hanya sesi yang dihadiri)
        $notAlfa = fn($s) => ($s->evaluation->student_attendance ?? null) !== 'alfa';

        $rows = [];
        foreach ($months as $m) {
            $ym = $m->format('Y-m');
            $mItems   = $schedules->filter(fn($s) => \Carbon\Carbon::parse($s->class_date)->format('Y-m') === $ym);
            $attended = $mItems->filter($notAlfa);

            $subjectVals  = [];
            $subjectSesi  = [];
            foreach ($programs as $prog) {
                $items = $mItems->filter(fn($s) => ($s->subject->subject_name ?? 'Tanpa Program') === $prog);
                $subjectVals[$prog] = $subjectScore($items);
                $subjectSesi[$prog] = $items->filter($notAlfa)->count();
            }

            $rows[] = [
                'label'       => $m->translatedFormat('F'),
                'sesi'        => $attended->count(),
                'sesiAll'     => $mItems->count(),
                'sesiProg'    => $subjectSesi,
                'subjects'    => $subjectVals,
                'analisa'     => $abilityScore($mItems, 'kemampuan_analisa'),
                'hafalan'     => $abilityScore($mItems, 'kemampuan_hafalan'),
                'kepercayaan' => $abilityScore($mItems, 'kepercayaan_diri'),
            ];
        }
        // Pertahankan bulan yang punya evaluasi (termasuk yang semuanya alfa → tampil 0 sesi)
        $rows = array_values(array_filter($rows, fn($r) => $r['sesiAll'] > 0));

        // Total alfa pada periode (untuk header info laporan)
        $alfaTotal = $schedules->filter(fn($s) => ($s->evaluation->student_attendance ?? null) === 'alfa')->count();

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

        // Materi per mata pelajaran, dikelompokkan per pokok bahasan
        // (bila satu pokok bahasan punya >1 evaluasi, nilainya dirata-rata)
        $materi = [];
        foreach ($programs as $prog) {
            $items = $schedules->filter(fn($s) => ($s->subject->subject_name ?? 'Tanpa Program') === $prog
                && $s->evaluation?->student_attendance !== 'alfa'
                && $s->evaluation?->materi_display);
            $grouped = $items->groupBy(fn($s) => $s->evaluation->materi_display['pokok']);
            $materi[$prog] = $grouped->map(fn($g, $name) => ['name' => $name, 'nilai' => $subjectScore($g)])->values()->all();
        }

        // Grafik sesi: batang vertikal bertumpuk per bulan, dibagi per mata pelajaran
        $sessionSvg = $this->buildSessionStackedSvg($rows, $programs->values()->all());
        // Grafik profil kemampuan: batang vertikal (sumbu X = kemampuan, sumbu Y = nilai).
        // Mencakup Analisa, Hafalan, Percaya Diri, dan rata-rata tiap mata pelajaran.
        $abilityData = [
            'Analisa'      => $footer['analisa'],
            'Hafalan'      => $footer['hafalan'],
            'Percaya Diri' => $footer['kepercayaan'],
        ];
        foreach ($programs as $prog) {
            $abilityData[$prog] = $footer['subjects'][$prog] ?? null;
        }
        $abilitySvg = $this->buildAbilityBarSvg($abilityData);

        $periode = $this->periodLabel($startDate, $endDate);
        $student->loadMissing('scheduleSession');

        // QR code berisi NIS, Nama, dan periode laporan (untuk verifikasi keaslian)
        $qrCode = $this->buildQrDataUri(
            "NIS: " . ($student->nis ?? '-') . "\n" .
            "Nama: " . $student->full_name . "\n" .
            "Periode: " . $periode
        );

        $logoPath = public_path('frontend/images/logo.jpeg');
        $logo = file_exists($logoPath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath)) : null;

        $pdf = Pdf::loadView('admin.evaluations.summary-pdf', compact(
            'student', 'programs', 'rows', 'footer', 'predikat', 'periode', 'materi', 'sessionSvg', 'abilitySvg', 'qrCode', 'logo', 'alfaTotal'
        ))->setPaper('a4', 'portrait');

        $fileName = trim('Laporan Summary ' . ($student->nis ?? '-') . ' - ' . ($student->nickname ?? $student->full_name));

        return $pdf->download($fileName . '.pdf');
    }

    /**
     * Grafik batang vertikal bertumpuk (SVG) untuk jumlah sesi per bulan.
     * Sumbu X = bulan, sumbu Y = jumlah sesi; tiap batang dibagi per mata pelajaran.
     */
    private function buildSessionStackedSvg(array $rows, array $programs): string
    {
        $w = 340; $h = 180; $padL = 26; $padB = 48; $padT = 18; $padR = 10;
        $plotW = $w - $padL - $padR; $plotH = $h - $padT - $padB;
        $n = max(1, count($rows));
        $colors = ['#2C3E73', '#4299e1', '#16a34a', '#d97706', '#9333ea', '#dc2626'];

        $max = 1;
        foreach ($rows as $r) { $max = max($max, (int) $r['sesi']); }
        $max = max(2, (int) ceil($max / 2) * 2);
        $base = $padT + $plotH;
        $bw   = ($plotW / $n) * 0.5;

        $svg = '<svg width="' . $w . '" height="' . $h . '" viewBox="0 0 ' . $w . ' ' . $h . '" xmlns="http://www.w3.org/2000/svg">';
        // grid + skala sumbu Y
        $step = max(1, (int) ($max / 4));
        for ($i = 0; $i <= $max; $i += $step) {
            $y = $base - ($i / $max) * $plotH;
            $svg .= '<line x1="' . $padL . '" y1="' . $y . '" x2="' . ($w - $padR) . '" y2="' . $y . '" stroke="#e5e7eb" stroke-width="1"/>';
            $svg .= '<text x="' . ($padL - 5) . '" y="' . ($y + 3) . '" font-size="8" text-anchor="end" fill="#666">' . $i . '</text>';
        }
        // sumbu
        $svg .= '<line x1="' . $padL . '" y1="' . $padT . '" x2="' . $padL . '" y2="' . $base . '" stroke="#888" stroke-width="1"/>';
        $svg .= '<line x1="' . $padL . '" y1="' . $base . '" x2="' . ($w - $padR) . '" y2="' . $base . '" stroke="#888" stroke-width="1"/>';
        $svg .= '<text x="4" y="10" font-size="7.5" fill="#444" font-weight="bold">Sesi</text>';

        // batang bertumpuk per bulan
        foreach ($rows as $idx => $r) {
            $cx = $padL + ($idx + 0.5) * ($plotW / $n);
            $x  = $cx - $bw / 2;
            $yTop = $base;
            foreach ($programs as $pi => $prog) {
                $cnt = (int) ($r['sesiProg'][$prog] ?? 0);
                if ($cnt <= 0) continue;
                $segH = ($cnt / $max) * $plotH;
                $yTop -= $segH;
                $svg .= '<rect x="' . round($x, 1) . '" y="' . round($yTop, 1) . '" width="' . round($bw, 1) . '" height="' . round($segH, 1) . '" fill="' . $colors[$pi % count($colors)] . '"/>';
                if ($segH >= 10) {
                    $svg .= '<text x="' . round($cx, 1) . '" y="' . round($yTop + $segH / 2 + 3, 1) . '" font-size="7" text-anchor="middle" fill="#fff">' . $cnt . '</text>';
                }
            }
            // total di atas batang
            $svg .= '<text x="' . round($cx, 1) . '" y="' . round($yTop - 3, 1) . '" font-size="8" text-anchor="middle" fill="#222" font-weight="bold">' . (int) $r['sesi'] . '</text>';
            // label bulan (sumbu X)
            $svg .= '<text x="' . round($cx, 1) . '" y="' . ($base + 12) . '" font-size="8" text-anchor="middle" fill="#444">' . htmlspecialchars(mb_substr($r['label'], 0, 3)) . '</text>';
        }
        // judul sumbu X
        $svg .= '<text x="' . round($padL + $plotW / 2, 1) . '" y="' . ($base + 24) . '" font-size="8" text-anchor="middle" fill="#444" font-weight="bold">Bulan</text>';

        // legenda mata pelajaran
        $lx = $padL; $ly = $base + 38;
        foreach ($programs as $pi => $prog) {
            $svg .= '<rect x="' . round($lx, 1) . '" y="' . ($ly - 6) . '" width="8" height="8" fill="' . $colors[$pi % count($colors)] . '" rx="1"/>';
            $svg .= '<text x="' . round($lx + 11, 1) . '" y="' . $ly . '" font-size="7" fill="#444">' . htmlspecialchars($prog) . '</text>';
            $lx += 11 + mb_strlen($prog) * 4.0 + 12;
        }
        $svg .= '</svg>';
        return $svg;
    }

    /**
     * Grafik batang vertikal (SVG) untuk profil kemampuan.
     * Sumbu X = jenis kemampuan, sumbu Y = nilai (skala 0-100).
     */
    private function buildAbilityBarSvg(array $data): string
    {
        $w = 340; $h = 185; $padL = 26; $padB = 48; $padT = 18; $padR = 10; $max = 100;
        $plotW = $w - $padL - $padR; $plotH = $h - $padT - $padB;
        $labels = array_keys($data); $vals = array_values($data);
        $n = max(1, count($labels));
        $base = $padT + $plotH;
        $bw   = ($plotW / $n) * 0.55;
        $colors = ['#2C3E73', '#4299e1', '#16a34a', '#d97706', '#9333ea', '#dc2626'];

        $svg = '<svg width="' . $w . '" height="' . $h . '" viewBox="0 0 ' . $w . ' ' . $h . '" xmlns="http://www.w3.org/2000/svg">';
        // grid + skala sumbu Y
        for ($i = 0; $i <= $max; $i += 25) {
            $y = $base - ($i / $max) * $plotH;
            $svg .= '<line x1="' . $padL . '" y1="' . $y . '" x2="' . ($w - $padR) . '" y2="' . $y . '" stroke="#e5e7eb" stroke-width="1"/>';
            $svg .= '<text x="' . ($padL - 5) . '" y="' . ($y + 3) . '" font-size="8" text-anchor="end" fill="#666">' . $i . '</text>';
        }
        // sumbu
        $svg .= '<line x1="' . $padL . '" y1="' . $padT . '" x2="' . $padL . '" y2="' . $base . '" stroke="#888" stroke-width="1"/>';
        $svg .= '<line x1="' . $padL . '" y1="' . $base . '" x2="' . ($w - $padR) . '" y2="' . $base . '" stroke="#888" stroke-width="1"/>';
        $svg .= '<text x="4" y="10" font-size="7.5" fill="#444" font-weight="bold">Nilai</text>';

        // batang per kemampuan / mata pelajaran
        foreach ($labels as $idx => $label) {
            $v  = $vals[$idx];
            $cx = $padL + ($idx + 0.5) * ($plotW / $n);
            $x  = $cx - $bw / 2;
            $bh = $v === null ? 0 : ($v / $max) * $plotH;
            $y  = $base - $bh;
            if ($v !== null) {
                $svg .= '<rect x="' . round($x, 1) . '" y="' . round($y, 1) . '" width="' . round($bw, 1) . '" height="' . round($bh, 1) . '" fill="' . $colors[$idx % count($colors)] . '" rx="2"/>';
                $svg .= '<text x="' . round($cx, 1) . '" y="' . round($y - 3, 1) . '" font-size="8.5" text-anchor="middle" fill="#222" font-weight="bold">' . number_format($v, 0) . '</text>';
            }
            // label (sumbu X) — dipecah per kata agar muat
            $lines = explode("\n", wordwrap($label, 9, "\n", true));
            foreach ($lines as $li => $line) {
                $svg .= '<text x="' . round($cx, 1) . '" y="' . ($base + 11 + $li * 8) . '" font-size="7" text-anchor="middle" fill="#444">' . htmlspecialchars($line) . '</text>';
            }
        }
        // judul sumbu X
        $svg .= '<text x="' . round($padL + $plotW / 2, 1) . '" y="' . ($base + 40) . '" font-size="8" text-anchor="middle" fill="#444" font-weight="bold">Kemampuan / Mata Pelajaran</text>';
        $svg .= '</svg>';
        return $svg;
    }

    /** Hasilkan QR code sebagai data URI (PNG). Null bila gagal. */
    private function buildQrDataUri(string $data): ?string
    {
        try {
            $options = new \chillerlan\QRCode\QROptions([
                'outputInterface' => \chillerlan\QRCode\Output\QRGdImagePNG::class,
                'outputBase64'    => true,
                'scale'           => 4,
                'quietzoneSize'   => 2,
                'eccLevel'        => \chillerlan\QRCode\Common\EccLevel::M,
            ]);

            return (new \chillerlan\QRCode\QRCode($options))->render($data);
        } catch (\Throwable $e) {
            return null;
        }
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
            'materi_manual'      => 'nullable|string|max:255',
            'student_attendance' => 'required|in:hadir,izin,alfa',
            'post_test'          => 'nullable|integer|min:1|max:100',
            'pemahaman'          => 'nullable|integer|min:1|max:100',
            'kemampuan_analisa'  => 'nullable|integer|min:1|max:100',
            'kemampuan_hafalan'  => 'nullable|integer|min:1|max:100',
            'kepercayaan_diri'   => 'nullable|integer|min:1|max:100',
            'tutor_notes'        => 'nullable|string|max:1000',
        ]);

        // Silabus & materi manual saling eksklusif
        $validated = $this->normalizeMateri($validated);

        $evaluation = Evaluation::updateOrCreate(
            ['schedule_id' => $validated['schedule_id']],
            $validated
        );

        $this->syncQuota($evaluation);

        return response()->json(['success' => true, 'message' => 'Evaluasi berhasil disimpan.', 'data' => $evaluation]);
    }

    public function update(Request $request, Evaluation $evaluation)
    {
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

        $evaluation->update($validated);
        $this->syncQuota($evaluation);
        return response()->json(['success' => true, 'message' => 'Evaluasi berhasil diperbarui.']);
    }

    /** Pastikan silabus & materi manual saling eksklusif (silabus menang bila keduanya terisi). */
    public function publish(Evaluation $evaluation)
    {
        $evaluation->update(['is_published' => !$evaluation->is_published]);
        $label = $evaluation->is_published ? 'diterbitkan ke orang tua' : 'disembunyikan';
        return response()->json(['success' => true, 'message' => "Laporan evaluasi berhasil $label.", 'is_published' => $evaluation->is_published]);
    }
}
