<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\Evaluation;
use App\Models\Schedule;
use App\Models\ScheduleSession;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Syllabus;
use App\Models\Tutor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yajra\DataTables\Facades\DataTables;

class ScheduleController extends Controller
{
    public function index()
    {
        $students         = Student::orderBy('full_name')->get(['id', 'full_name', 'schedule_session_id']);
        $tutors           = Tutor::orderBy('name')->get(['id', 'name']);
        $subjects         = Subject::orderBy('subject_name')->get(['id', 'subject_name']);
        $scheduleSessions = ScheduleSession::orderBy('time_start')->get();

        return view('admin.schedules.index', compact('students', 'tutors', 'subjects', 'scheduleSessions'));
    }

    public function data()
    {
        $query = Schedule::with(['student', 'tutor', 'subject', 'evaluation'])->latest('class_date');

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('student_name', fn($s) => e($s->student->full_name ?? '-'))
            ->editColumn('tutor_name',   fn($s) => e($s->tutor->name ?? '-'))
            ->editColumn('subject_name', fn($s) => e($s->subject->subject_name ?? '-'))
            ->editColumn('class_date', fn($s) => \Carbon\Carbon::parse($s->class_date)->translatedFormat('d M Y'))
            ->editColumn('time', fn($s) => substr($s->start_time, 0, 5) . ' – ' . substr($s->end_time, 0, 5))
            ->editColumn('status_schedule', function ($s) {
                return match ($s->status_schedule) {
                    'scheduled' => '<span class="badge bg-primary">Dijadwalkan</span>',
                    'done'      => '<span class="badge bg-success">Selesai</span>',
                    'canceled'  => '<span class="badge bg-secondary">Dibatalkan</span>',
                };
            })
            ->editColumn('evaluation_status', function ($s) {
                if ($s->status_schedule !== 'done') return '-';
                return $s->evaluation
                    ? '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Sudah</span>'
                    : '<span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-clock me-1"></i>Belum</span>';
            })
            ->addColumn('action', function ($s) {
                $btn = '<div class="btn-group btn-group-sm">';

                if ($s->status_schedule === 'scheduled') {
                    $btn .= '<button class="btn btn-outline-success btn-done" data-id="' . $s->id . '" title="Tandai Selesai"><i class="bi bi-check-lg"></i></button>';
                    $btn .= '<button class="btn btn-outline-warning btn-edit" data-id="' . $s->id . '" title="Edit"><i class="bi bi-pencil"></i></button>';
                    $btn .= '<button class="btn btn-outline-secondary btn-cancel" data-id="' . $s->id . '" title="Batalkan"><i class="bi bi-x-circle"></i></button>';
                }

                if ($s->status_schedule === 'done') {
                    $evalTitle = $s->evaluation ? 'Edit Evaluasi' : 'Isi Evaluasi';
                    $btn .= '<button class="btn btn-outline-info btn-evaluate" data-id="' . $s->id . '" title="' . $evalTitle . '"><i class="bi bi-clipboard2-check"></i></button>';
                }

                $btn .= '<button class="btn btn-outline-danger btn-delete" data-id="' . $s->id . '" title="Hapus"><i class="bi bi-trash"></i></button>';
                $btn .= '</div>';
                return $btn;
            })
            ->rawColumns(['status_schedule', 'evaluation_status', 'action'])
            ->make(true);
    }

    public function events(Request $request)
    {
        $events = Schedule::with(['student', 'tutor', 'subject', 'evaluation'])
            ->when($request->start,         fn($q) => $q->where('class_date', '>=', substr($request->start, 0, 10)))
            ->when($request->end,           fn($q) => $q->where('class_date', '<=', substr($request->end,   0, 10)))
            ->when($request->filter_status, fn($q) => $q->where('status_schedule', $request->filter_status))
            ->when($request->filter_tutor,  fn($q) => $q->where('tutor_id', $request->filter_tutor))
            ->get()
            ->map(function ($s) {
                $color = match ($s->status_schedule) {
                    'scheduled' => '#4299e1',
                    'done'      => '#2fb344',
                    'canceled'  => '#9ca3af',
                };
                return [
                    'id'    => $s->id,
                    'title' => ($s->student->full_name ?? '?') . ' – ' . ($s->subject->subject_name ?? '?'),
                    'start' => $s->class_date->format('Y-m-d') . 'T' . $s->start_time,
                    'end'   => $s->class_date->format('Y-m-d') . 'T' . $s->end_time,
                    'color' => $color,
                    'extendedProps' => [
                        'student'  => $s->student->full_name ?? '-',
                        'tutor'    => $s->tutor->name ?? '-',
                        'subject'  => $s->subject->subject_name ?? '-',
                        'status'   => $s->status_schedule,
                        'has_eval' => (bool) $s->evaluation,
                    ],
                ];
            });

        return response()->json($events);
    }

    public function studentScheduleInfo(Student $student)
    {
        $session = $student->scheduleSession;
        $programNames = $student->program ? (json_decode($student->program, true) ?? []) : [];
        $subjects = Subject::whereIn('subject_name', $programNames)->get(['id', 'subject_name']);

        return response()->json([
            'selected_days' => $student->selected_days,
            'session'       => $session ? [
                'id'         => $session->id,
                'name'       => $session->name,
                'time_start' => substr($session->time_start, 0, 5),
                'time_end'   => substr($session->time_end, 0, 5),
            ] : null,
            'subjects'      => $subjects,
        ]);
    }

    public function generate(Request $request)
    {
        $request->validate(['week_date' => 'required|date']);

        $dayOffset = [
            'Senin'  => 0,
            'Selasa' => 1,
            'Rabu'   => 2,
            'Kamis'  => 3,
            'Jumat'  => 4,
            'Sabtu'  => 5,
            'Minggu' => 6,
        ];

        $weekStart = \Carbon\Carbon::parse($request->week_date)->startOfWeek(\Carbon\Carbon::MONDAY);

        $students = Student::with('scheduleSession')
            ->where('status', 1)
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($students as $student) {
            // Tentukan slot jadwal (hari + sesi) dari master jadwal yang dipilih saat pendaftaran.
            // Fallback ke field lama (selected_days + schedule_session_id) untuk data lama.
            $slots = collect();
            $scheduleIds = $student->class_schedule_ids ?? [];

            if (!empty($scheduleIds)) {
                $slots = ClassSchedule::with('session')
                    ->whereIn('id', $scheduleIds)
                    ->get()
                    ->map(fn($cs) => ['hari' => $cs->hari, 'session' => $cs->session]);
            } elseif ($student->selected_days && $student->schedule_session_id) {
                $slots = collect([['hari' => $student->selected_days, 'session' => $student->scheduleSession]]);
            }

            if ($slots->isEmpty()) {
                $skipped++;
                continue;
            }

            // Sisa kuota yang bisa dijadwalkan = kuota siswa - jadwal yang masih terjadwal (belum selesai/batal).
            // Kuota 0 → tidak digenerate; kuota 1 dengan 2 jadwal → hanya 1 yang digenerate.
            $pendingCount = Schedule::where('student_id', $student->id)
                ->where('status_schedule', 'scheduled')
                ->count();
            $remaining = (int) ($student->quota_sessions ?? 0) - $pendingCount;

            if ($remaining <= 0) {
                $skipped++;
                continue;
            }

            // Ambil subject pertama dari program siswa
            $subjectId = null;
            if ($student->program) {
                $programNames = json_decode($student->program, true) ?? [];
                if (!empty($programNames)) {
                    $subject = Subject::whereIn('subject_name', $programNames)->first();
                    $subjectId = $subject?->id;
                }
            }

            foreach ($slots as $slot) {
                if ($remaining <= 0) {
                    break; // kuota habis untuk siswa ini
                }

                if (!array_key_exists($slot['hari'], $dayOffset) || !$slot['session']) {
                    $skipped++;
                    continue;
                }

                $session   = $slot['session'];
                $classDate = $weekStart->copy()->addDays($dayOffset[$slot['hari']]);
                $startTime = substr($session->time_start, 0, 5);

                // Lewati jika sudah ada jadwal aktif di tanggal & jam mulai yang sama
                $exists = Schedule::where('student_id', $student->id)
                    ->where('class_date', $classDate->toDateString())
                    ->where('start_time', $startTime)
                    ->where('status_schedule', '!=', 'canceled')
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                Schedule::create([
                    'student_id'      => $student->id,
                    'tutor_id'        => null,
                    'subject_id'      => $subjectId,
                    'class_date'      => $classDate->toDateString(),
                    'start_time'      => $startTime,
                    'end_time'        => substr($session->time_end, 0, 5),
                    'status_schedule' => 'scheduled',
                ]);

                $created++;
                $remaining--;
            }
        }

        if ($created === 0 && $skipped === 0) {
            return response()->json(['success' => false, 'message' => 'Tidak ada siswa aktif yang memiliki data hari dan sesi belajar.'], 422);
        }

        $message = $created . ' jadwal berhasil digenerate';
        if ($skipped > 0) {
            $message .= ', ' . $skipped . ' siswa dilewati (jadwal sudah ada atau data tidak lengkap)';
        }
        $message .= '.';

        return response()->json(['success' => true, 'created' => $created, 'skipped' => $skipped, 'message' => $message]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'tutor_id'   => 'required|exists:tutors,id',
            'subject_id' => 'required|exists:subjects,id',
            'room'       => 'nullable|string|max:50',
            'class_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|after:start_time',
        ]);

        if ($this->hasConflict($validated['tutor_id'], $validated['class_date'], $validated['start_time'], $validated['end_time'])) {
            return response()->json(['success' => false, 'message' => 'Tutor ini sudah memiliki jadwal yang bentrok pada waktu tersebut.'], 422);
        }

        Schedule::create($validated + ['status_schedule' => 'scheduled']);
        return response()->json(['success' => true, 'message' => 'Jadwal berhasil ditambahkan.']);
    }

    public function show(Schedule $schedule)
    {
        $schedule->load(['student', 'tutor', 'evaluation']);

        // Silabus untuk dropdown evaluasi disaring sesuai kelas (grade) siswa.
        $grade = $schedule->student?->grade;
        $schedule->load(['subject.syllabi' => function ($query) use ($grade) {
            if ($grade) {
                $query->where('kelas', $grade);
            }
        }]);

        return response()->json($schedule);
    }

    public function update(Request $request, Schedule $schedule)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'tutor_id'   => 'required|exists:tutors,id',
            'subject_id' => 'required|exists:subjects,id',
            'room'       => 'nullable|string|max:50',
            'class_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|after:start_time',
        ]);

        if ($this->hasConflict($validated['tutor_id'], $validated['class_date'], $validated['start_time'], $validated['end_time'], $schedule->id)) {
            return response()->json(['success' => false, 'message' => 'Tutor ini sudah memiliki jadwal yang bentrok pada waktu tersebut.'], 422);
        }

        $schedule->update($validated);
        return response()->json(['success' => true, 'message' => 'Jadwal berhasil diperbarui.']);
    }

    public function updateStatus(Request $request, Schedule $schedule)
    {
        $request->validate(['status' => 'required|in:done,canceled']);

        $schedule->update(['status_schedule' => $request->status]);

        // Catatan: kuota sesi dipotong saat tutor mengevaluasi siswa dengan kehadiran "hadir"
        // (lihat EvaluationController@syncQuota), bukan saat status jadwal diubah.

        return response()->json(['success' => true, 'message' => 'Status jadwal berhasil diperbarui.']);
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();
        return response()->json(['success' => true, 'message' => 'Jadwal berhasil dihapus.']);
    }

    /**
     * Kolom sheet utama "Jadwal & Evaluasi".
     */
    private array $evalImportColumns = [
        'ID Siswa (Kelas)*', 'ID Tutor*', 'ID Mapel*', 'ID Sesi*', 'Tanggal (YYYY-MM-DD)*',
        'ID Silabus (opsional)', 'Kehadiran (hadir/izin/alfa)',
        'Pre Test (0-100)', 'Post Test (0-100)', 'Pemahaman (A+..D)', 'Poin (A+..D)',
        'Kemampuan Analisa (A+..D)', 'Kemampuan Hafalan (A+..D)', 'Kepercayaan Diri (A+..D)',
        'Catatan Tutor',
    ];

    /**
     * Unduh template Excel gabungan Jadwal + Evaluasi.
     * Sheet utama mengisi ID; data master ada di sheet terpisah (Kelas, Tutor, Mapel, Sesi).
     */
    public function evaluationTemplate()
    {
        $spreadsheet = new Spreadsheet();

        // ── Sheet 1: Jadwal & Evaluasi ──
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Jadwal & Evaluasi');
        $sheet->fromArray($this->evalImportColumns, null, 'A1');
        $sheet->fromArray([[
            1, 1, 1, 1, now()->format('Y-m-d'),
            '', 'hadir', 70, 85, 'A-', 'B+', 'A', 'B+', 'A-', 'Perkembangan baik',
        ]], null, 'A2');

        $lastCol = $sheet->getHighestColumn();
        $sheet->getStyle('A1:' . $lastCol . '1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:' . $lastCol . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2C3E73');
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setWidth(22);
        }

        $addMaster = function (string $title, array $headers, $rows) use ($spreadsheet) {
            $ws = $spreadsheet->createSheet();
            $ws->setTitle($title);
            $ws->fromArray($headers, null, 'A1');
            $r = 2;
            foreach ($rows as $row) {
                $ws->fromArray($row, null, 'A' . $r++);
            }
            $last = $ws->getHighestColumn();
            $ws->getStyle('A1:' . $last . '1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $ws->getStyle('A1:' . $last . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F7A4D');
            foreach (range('A', $last) as $col) {
                $ws->getColumnDimension($col)->setWidth(24);
            }
        };

        $addMaster('Master Kelas', ['ID', 'Nama Siswa', 'NIS', 'Kelas'],
            Student::orderBy('full_name')->get()->map(fn($s) => [$s->id, $s->full_name, $s->nis, $s->grade])->toArray());

        $addMaster('Master Tutor', ['ID', 'Nama Tutor'],
            Tutor::orderBy('name')->get()->map(fn($t) => [$t->id, $t->name])->toArray());

        $addMaster('Master Mapel', ['ID', 'Nama Mata Pelajaran'],
            Subject::orderBy('subject_name')->get()->map(fn($s) => [$s->id, $s->subject_name])->toArray());

        $addMaster('Master Silabus', ['ID', 'Mata Pelajaran', 'Pokok Bahasan', 'Sub Pokok Bahasan', 'Kelas', 'Jenis Kurikulum'],
            Syllabus::with('subject')->orderBy('subject_id')->orderBy('id')->get()
                ->map(fn($s) => [$s->id, $s->subject->subject_name ?? '-', $s->pokok_bahasan, $s->sub_pokok_bahasan, $s->kelas, $s->jenis_kurikulum])->toArray());

        $addMaster('Master Sesi', ['ID', 'Nama Sesi', 'Mulai', 'Selesai'],
            ScheduleSession::orderBy('time_start')->get()->map(fn($s) => [$s->id, $s->name, substr($s->time_start, 0, 5), substr($s->time_end, 0, 5)])->toArray());

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'template-jadwal-evaluasi.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import gabungan: buat Jadwal + Evaluasi sekaligus per baris.
     */
    public function importEvaluation(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120',
        ], [
            'file.mimes' => 'Format file harus .xlsx, .xls, atau .csv.',
            'file.max'   => 'Ukuran file maksimal 5 MB.',
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
            $sheet = $spreadsheet->getSheetByName('Jadwal & Evaluasi') ?? $spreadsheet->getSheet(0);
            $rows  = $sheet->toArray(null, true, true, false);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak dapat dibaca. Pastikan menggunakan template yang disediakan.',
            ], 422);
        }

        array_shift($rows); // header

        $inserted = 0;
        $skipped  = 0;
        $errors   = [];

        foreach ($rows as $i => $row) {
            $line = $i + 2;
            $get  = fn($idx) => isset($row[$idx]) ? trim((string) $row[$idx]) : '';

            // Lewati baris kosong
            if ($get(0) === '' && $get(1) === '' && $get(4) === '') {
                continue;
            }

            // Validasi master wajib
            if (!Student::whereKey($get(0))->exists()) { $skipped++; $errors[] = "Baris {$line}: ID Siswa '{$get(0)}' tidak ditemukan."; continue; }
            if (!Tutor::whereKey($get(1))->exists())   { $skipped++; $errors[] = "Baris {$line}: ID Tutor '{$get(1)}' tidak ditemukan."; continue; }
            if (!Subject::whereKey($get(2))->exists()) { $skipped++; $errors[] = "Baris {$line}: ID Mapel '{$get(2)}' tidak ditemukan."; continue; }

            $session = ScheduleSession::find($get(3));
            if (!$session) { $skipped++; $errors[] = "Baris {$line}: ID Sesi '{$get(3)}' tidak ditemukan."; continue; }

            $date = $get(4);
            if ($date === '' || !strtotime($date)) { $skipped++; $errors[] = "Baris {$line}: Tanggal tidak valid."; continue; }
            $date = date('Y-m-d', strtotime($date));

            // Silabus opsional
            $syllabusId = null;
            if ($get(5) !== '') {
                if (Syllabus::whereKey($get(5))->exists()) {
                    $syllabusId = (int) $get(5);
                } else {
                    $errors[] = "Baris {$line}: ID Silabus '{$get(5)}' tidak ditemukan (dilewati untuk kolom ini).";
                }
            }

            // Kehadiran
            $att = strtolower($get(6)) ?: 'hadir';
            if (!in_array($att, ['hadir', 'izin', 'alfa'], true)) {
                $skipped++; $errors[] = "Baris {$line}: Kehadiran '{$get(6)}' tidak valid (hadir/izin/alfa)."; continue;
            }

            $pre  = $get(7) !== '' && is_numeric($get(7)) ? max(0, min(100, (int) $get(7))) : null;
            $post = $get(8) !== '' && is_numeric($get(8)) ? max(0, min(100, (int) $get(8))) : null;
            $grade = fn($idx) => in_array($get($idx), Evaluation::GRADES, true) ? $get($idx) : null;
            $pemahaman  = $grade(9);
            $poin       = $grade(10);
            $analisa    = $grade(11);
            $hafalan    = $grade(12);
            $kepercayaan = $grade(13);

            // Buat jadwal + evaluasi secara paralel (transaksi per baris)
            try {
                DB::transaction(function () use ($get, $session, $date, $syllabusId, $att, $pre, $post, $pemahaman, $poin, $analisa, $hafalan, $kepercayaan) {
                    $schedule = Schedule::create([
                        'student_id'      => (int) $get(0),
                        'tutor_id'        => (int) $get(1),
                        'subject_id'      => (int) $get(2),
                        'class_date'      => $date,
                        'start_time'      => substr($session->time_start, 0, 5),
                        'end_time'        => substr($session->time_end, 0, 5),
                        'status_schedule' => 'done',
                    ]);

                    Evaluation::create([
                        'schedule_id'        => $schedule->id,
                        'syllabus_id'        => $syllabusId,
                        'student_attendance' => $att,
                        'pre_test'           => $pre,
                        'post_test'          => $post,
                        'pemahaman'          => $pemahaman,
                        'poin'               => $poin,
                        'kemampuan_analisa'  => $analisa,
                        'kemampuan_hafalan'  => $hafalan,
                        'kepercayaan_diri'   => $kepercayaan,
                        'tutor_notes'        => $get(14) ?: null,
                        'is_published'       => false,
                    ]);
                });
                $inserted++;
            } catch (\Throwable $e) {
                $skipped++;
                $errors[] = "Baris {$line}: gagal disimpan (" . $e->getMessage() . ').';
            }
        }

        if ($inserted === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data valid yang diimport.',
                'errors'  => $errors,
            ], 422);
        }

        $message = "{$inserted} jadwal + evaluasi berhasil dibuat.";
        if ($skipped > 0) {
            $message .= " {$skipped} baris dilewati.";
        }

        return response()->json([
            'success'  => true,
            'message'  => $message,
            'inserted' => $inserted,
            'skipped'  => $skipped,
            'errors'   => $errors,
        ]);
    }

    private function hasConflict(int $tutorId, string $date, string $start, string $end, ?int $excludeId = null): bool
    {
        return Schedule::where('tutor_id', $tutorId)
            ->where('class_date', $date)
            ->where('status_schedule', '!=', 'canceled')
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->exists();
    }
}
