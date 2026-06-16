<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\ScheduleSession;
use App\Models\ScheduleStudent;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yajra\DataTables\Facades\DataTables;

class StudentController extends Controller
{
    public function index()
    {
        return view('admin.students.index');
    }

    public function dataStudents(Request $request)
    {
        $query = Student::latest();
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('full_name', function ($student) {
                return '<div class="fw-semibold">' . $student->full_name . '</div>
                        <small class="text-muted">' . ($student->nickname ?? '') . '</small>';
            })
            ->editColumn('status', function ($student) {
                $badgeClass = $student->status == 1 ? 'bg-success' : 'bg-danger';
                $statusText = $student->status == 1 ? 'Aktif' : 'Non-Aktif';
                return '<span class="badge ' . $badgeClass . '">' . $statusText . '</span>';
            })
            ->editColumn('program', function ($student) {
                $names = json_decode($student->program ?? '', true) ?? [];
                if (empty($names)) return '<span class="text-muted">-</span>';
                return implode('', array_map(
                    fn($n) => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">' . e($n) . '</span>',
                    $names
                ));
            })
            ->addColumn('action', function ($student) {
                return '<div class="btn-group btn-group-sm">
                            <a href="' . route('admin.students.show', $student->id) . '" class="btn btn-outline-primary" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="' . route('admin.students.edit', $student->id) . '" class="btn btn-outline-warning" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" class="btn btn-outline-danger btn-delete" data-id="' . $student->id . '" data-name="' . $student->full_name . '" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>';
            })
            ->rawColumns(['full_name', 'status', 'program', 'action'])
            ->make(true);
    }

    public function create()
    {
        $sessions = \App\Models\ScheduleSession::orderBy('time_start')->get();
        $subjects = \App\Models\Subject::orderBy('subject_name')->get();
        $packages = \App\Models\Package::orderBy('price')->get();

        return view('admin.students.create', compact('sessions', 'subjects', 'packages'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name'           => 'required|string|max:255',
            'nickname'            => 'nullable|string|max:255',
            'nis'                 => 'nullable|string|max:50|unique:students,nis',
            'registration_date'   => 'nullable|date',
            'birth_date'          => 'nullable|date',
            'religion'            => 'nullable|string|max:50',
            'gender'              => 'nullable|string|max:20',
            'grade'               => 'nullable|string|max:50',
            'school_origin'       => 'nullable|string|max:255',
            'father_name'         => 'nullable|string|max:255',
            'mother_name'         => 'nullable|string|max:255',
            'guardian_name'       => 'nullable|string|max:255',
            'address'             => 'nullable|string',
            'email'               => 'nullable|email|max:255',
            'phone'               => 'nullable|string|max:20',
            'whatsapp'            => 'nullable|string|max:20',
            'class_type'          => 'nullable|string|max:50',
            'kbm_process'         => 'nullable|string|max:100',
            'package_id'          => 'nullable|exists:packages,id',
            'program'             => 'nullable|array',
            'program.*'           => 'string|max:100',
            'selected_days'       => 'nullable|string|max:50',
            'schedule_session_id' => 'nullable|exists:schedule_sessions,id',
            'school_curriculum'   => 'nullable|string|max:100',
            'learning_material'   => 'nullable|string|max:255',
            'registration_info'   => 'nullable|string|max:100',
            'marketing_pic'       => 'nullable|string|max:100',
            'status'              => 'required|in:1,2',
        ]);

        // Program (multi-pilih ID mapel) → simpan sebagai JSON nama mapel
        $programNames = [];
        if (!empty($validated['program'])) {
            $subjectIds   = array_filter($validated['program'], 'is_numeric');
            $programNames = \App\Models\Subject::whereIn('id', $subjectIds)->pluck('subject_name')->toArray();
        }

        // Nama paket untuk kolom `package`
        $packageName = !empty($validated['package_id'])
            ? \App\Models\Package::find($validated['package_id'])?->package_name
            : null;

        $data = array_merge($validated, [
            'registration_code' => 'REG-' . strtoupper(str_replace(' ', '', substr($validated['full_name'], 0, 3))) . '-' . date('YmdHis'),
            'registration_date' => $validated['registration_date'] ?? now()->toDateString(),
            'program'           => !empty($programNames) ? json_encode($programNames) : null,
            'package'           => $packageName,
        ]);

        $student = Student::create($data);

        // Jadwal awal (jika hari & sesi dipilih)
        if (!empty($validated['selected_days']) && !empty($validated['schedule_session_id'])) {
            \App\Models\ScheduleStudent::create([
                'student_id'          => $student->id,
                'schedule_session_id' => $validated['schedule_session_id'],
                'date'                => $validated['selected_days'],
                'notes'               => 'Jadwal Pendaftaran Awal',
            ]);
        }

        return redirect()->route('admin.students.index')->with('success', 'Data siswa berhasil ditambahkan.');
    }

    /**
     * Urutan kolom sheet "Data Siswa" pada template import.
     */
    private array $importColumns = [
        'Nama Lengkap*', 'Nama Panggilan', 'NIS', 'Tanggal Lahir (YYYY-MM-DD)',
        'Agama', 'Jenis Kelamin', 'Kelas', 'Asal Sekolah',
        'Nama Ayah', 'Nama Ibu', 'Nama Wali', 'Alamat',
        'Email', 'No. Telp', 'No. WhatsApp',
        'Kelas/Jenjang', 'Proses KBM',
        'ID Paket', 'ID Program (pisah koma)', 'Pilihan Hari', 'ID Sesi',
        'Kurikulum Sekolah', 'Materi Pembelajaran',
        'Info Pendaftaran', 'PIC Marketing', 'Status (1=Aktif, 2=Non-Aktif)',
    ];

    /**
     * Unduh template Excel import siswa.
     * Data master (Paket, Mapel, Sesi) diletakkan di sheet terpisah,
     * dan kolom di sheet utama mengisi ID-nya.
     */
    public function template()
    {
        $spreadsheet = new Spreadsheet();

        // ── Sheet 1: Data Siswa ──
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Siswa');
        $sheet->fromArray($this->importColumns, null, 'A1');
        $sheet->fromArray([[
            'Budi Santoso', 'Budi', '12345', '2014-05-10',
            'Islam', 'Laki-laki', 'SD Kelas 4', 'SDN Contoh 01',
            'Bapak Budi', 'Ibu Ani', '', 'Jl. Mawar No. 1',
            'budi@email.com', '081200000001', '081200000001',
            'SD Kelas 4', 'Offline (Di Livo)',
            1, '1,2', 'Senin', 1,
            'Kurikulum Merdeka', 'Aljabar dasar',
            'Instagram', 'Marketing A', 1,
        ]], null, 'A2');

        $lastCol = $sheet->getHighestColumn();
        $sheet->getStyle('A1:' . $lastCol . '1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:' . $lastCol . '1')->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2C3E73');
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setWidth(22);
        }

        // ── Sheet master: helper untuk render ──
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
            $ws->getStyle('A1:' . $last . '1')->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F7A4D');
            foreach (range('A', $last) as $col) {
                $ws->getColumnDimension($col)->setWidth(24);
            }
        };

        $addMaster('Master Paket', ['ID', 'Nama Paket', 'Harga', 'Jumlah Sesi'],
            Package::orderBy('id')->get()->map(fn($p) => [$p->id, $p->package_name, $p->price, $p->total_sessions])->toArray());

        $addMaster('Master Mapel', ['ID', 'Nama Mata Pelajaran'],
            Subject::orderBy('id')->get()->map(fn($s) => [$s->id, $s->subject_name])->toArray());

        $addMaster('Master Sesi', ['ID', 'Nama Sesi', 'Mulai', 'Selesai'],
            ScheduleSession::orderBy('id')->get()->map(fn($s) => [$s->id, $s->name, substr($s->time_start, 0, 5), substr($s->time_end, 0, 5)])->toArray());

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'template-import-siswa.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import siswa dari file Excel sesuai template.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120',
        ], [
            'file.mimes' => 'Format file harus .xlsx, .xls, atau .csv.',
            'file.max'   => 'Ukuran file maksimal 5 MB.',
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
            $sheet = $spreadsheet->getSheetByName('Data Siswa') ?? $spreadsheet->getSheet(0);
            $rows  = $sheet->toArray(null, true, true, false);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak dapat dibaca. Pastikan menggunakan template yang disediakan.',
            ], 422);
        }

        array_shift($rows); // buang header

        $inserted = 0;
        $skipped  = 0;
        $errors   = [];

        foreach ($rows as $i => $row) {
            $line = $i + 2;
            $get  = fn($idx) => isset($row[$idx]) ? trim((string) $row[$idx]) : '';

            $fullName = $get(0);

            // Lewati baris kosong
            if ($fullName === '' && $get(2) === '' && $get(12) === '') {
                continue;
            }

            if ($fullName === '') {
                $skipped++;
                $errors[] = "Baris {$line}: Nama Lengkap wajib diisi.";
                continue;
            }

            // NIS unik
            $nis = $get(2);
            if ($nis !== '' && Student::where('nis', $nis)->exists()) {
                $skipped++;
                $errors[] = "Baris {$line}: NIS {$nis} sudah terdaftar.";
                continue;
            }

            // Master: Paket
            $packageName = null;
            if ($get(17) !== '') {
                $pkg = Package::find($get(17));
                if (!$pkg) {
                    $skipped++;
                    $errors[] = "Baris {$line}: ID Paket '{$get(17)}' tidak ditemukan.";
                    continue;
                }
                $packageName = $pkg->package_name;
            }

            // Master: Sesi
            $sessionId = null;
            if ($get(20) !== '') {
                if (!ScheduleSession::whereKey($get(20))->exists()) {
                    $skipped++;
                    $errors[] = "Baris {$line}: ID Sesi '{$get(20)}' tidak ditemukan.";
                    continue;
                }
                $sessionId = (int) $get(20);
            }

            // Master: Program (ID mapel, pisah koma) → JSON nama mapel
            $programJson = null;
            if ($get(18) !== '') {
                $ids   = array_filter(array_map('trim', explode(',', $get(18))), 'is_numeric');
                $names = Subject::whereIn('id', $ids)->pluck('subject_name')->toArray();
                $programJson = !empty($names) ? json_encode($names) : null;
            }

            $birth = $get(3);
            $birthDate = $birth !== '' && strtotime($birth) ? date('Y-m-d', strtotime($birth)) : null;

            $status = (int) $get(25);
            $status = in_array($status, [1, 2], true) ? $status : 2;

            $student = Student::create([
                'registration_code'   => 'REG-' . strtoupper(str_replace(' ', '', substr($fullName, 0, 3))) . '-' . date('YmdHis') . $line,
                'registration_date'   => now()->toDateString(),
                'full_name'           => $fullName,
                'nickname'            => $get(1) ?: null,
                'nis'                 => $nis ?: null,
                'birth_date'          => $birthDate,
                'religion'            => $get(4) ?: null,
                'gender'              => $get(5) ?: null,
                'grade'               => $get(6) ?: null,
                'school_origin'       => $get(7) ?: null,
                'father_name'         => $get(8) ?: null,
                'mother_name'         => $get(9) ?: null,
                'guardian_name'       => $get(10) ?: null,
                'address'             => $get(11) ?: null,
                'email'               => $get(12) ?: null,
                'phone'               => $get(13) ?: null,
                'whatsapp'            => $get(14) ?: null,
                'class_type'          => $get(15) ?: null,
                'kbm_process'         => $get(16) ?: null,
                'package'             => $packageName,
                'program'             => $programJson,
                'selected_days'       => $get(19) ?: null,
                'schedule_session_id' => $sessionId,
                'school_curriculum'   => $get(21) ?: null,
                'learning_material'   => $get(22) ?: null,
                'registration_info'   => $get(23) ?: null,
                'marketing_pic'       => $get(24) ?: null,
                'status'              => $status,
            ]);

            // Jadwal awal
            if ($get(19) !== '' && $sessionId) {
                ScheduleStudent::create([
                    'student_id'          => $student->id,
                    'schedule_session_id' => $sessionId,
                    'date'                => $get(19),
                    'notes'               => 'Jadwal Pendaftaran Awal (Import)',
                ]);
            }

            $inserted++;
        }

        if ($inserted === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data valid yang diimport.',
                'errors'  => $errors,
            ], 422);
        }

        $message = "{$inserted} siswa berhasil diimport.";
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

    public function show(Student $student)
    {
        $scheduleSessions = \App\Models\ScheduleSession::all();
        $tutors   = \App\Models\Tutor::orderBy('name')->get(['id', 'name']);
        $subjects = \App\Models\Subject::orderBy('subject_name')->get(['id', 'subject_name']);
        $schedules = $student->schedules()
            ->with(['tutor', 'subject', 'evaluation'])
            ->orderBy('class_date', 'desc')
            ->get();
        return view('admin.students.show', compact('student', 'scheduleSessions', 'tutors', 'subjects', 'schedules'));
    }

    public function edit(Student $student)
    {
        return view('admin.students.edit', compact('student'));
    }

    public function update(Request $request, Student $student)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'status' => 'required|in:1,2',
            'nis' => 'nullable|string|max:50',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'whatsapp' => 'nullable|string',
        ]);

        $student->update($request->all());

        return redirect()->route('admin.students.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Student $student)
    {
        $student->delete();
        return response()->json(['success' => true, 'message' => 'Data siswa berhasil dihapus.']);
    }
}
