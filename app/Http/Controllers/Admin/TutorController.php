<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ChecksTutorScheduleConflict;
use App\Http\Controllers\Concerns\MaterializesScheduleSlot;
use App\Http\Controllers\Controller;
use App\Models\SchedulePattern;
use App\Models\SchedulePatternItem;
use App\Models\ScheduleSession;
use App\Models\ScheduleSlot;
use App\Models\ScheduleSlotApplication;
use App\Models\Student;
use App\Models\StudentGroup;
use App\Models\Subject;
use App\Models\Tutor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class TutorController extends Controller
{
    use MaterializesScheduleSlot;
    use ChecksTutorScheduleConflict;

    public function index()
    {
        $subjects = Subject::orderBy('subject_name')->get(['id', 'subject_name']);
        return view('admin.tutors.index', compact('subjects'));
    }

    /**
     * Detail tutor — profil + kelola jadwal mengajarnya (tambah/ubah/hapus), dan kelola
     * baris Pola Jadwal mingguan di mana tutor ini sedang jadi pemegang slot (tambah/ubah/
     * hapus/lepas kepemilikan) — tanpa menambah field tutor ke SchedulePatternItem,
     * kepemilikan tetap ditentukan dinamis lewat ScheduleSlot seperti alur yang sudah ada.
     *
     * `?minggu=ini` (default) atau `?minggu=depan` menentukan minggu yang ditampilkan &
     * yang jadi sasaran aksi (Tambah Pola / Tetapkan ke Tutor Ini) pada bagian Pola Jadwal.
     */
    public function show(Request $request, Tutor $tutor)
    {
        $students = Student::where('status', 1)->orderBy('full_name')->get(['id', 'full_name', 'nis']);

        // Dropdown Mata Pelajaran dibatasi ke spesialisasi tutor ini — supaya jadwal yang
        // dibuat dari halaman ini selalu sesuai kualifikasinya.
        $allSubjects = Subject::orderBy('subject_name')->get(['id', 'subject_name']);
        $specs = is_array($tutor->specialization) ? $tutor->specialization : [];
        $subjects = !empty($specs) ? $allSubjects->whereIn('subject_name', $specs)->values() : $allSubjects;

        $scheduleSessions = ScheduleSession::orderBy('time_start')->get();

        // Daftar jadwal mengajar ditampilkan via DataTables server-side (lihat scheduleData()) —
        // tidak perlu dimuat di sini, cukup hitung totalnya saja utk kartu profil.
        $schedulesCount = $tutor->schedules()->count();

        $patterns = SchedulePattern::orderByDesc('is_active')->orderBy('name')->get();
        $studentGroups = StudentGroup::with('session')->orderBy('name')->get();

        $mingguPilihan = $request->input('minggu') === 'depan' ? 'depan' : 'ini';
        $weekStart = now()->startOfWeek(Carbon::MONDAY);
        if ($mingguPilihan === 'depan') {
            $weekStart = $weekStart->copy()->addWeek();
        }

        // Pattern item yang SAAT INI dipegang tutor ini (lepas dari minggu yang dipilih):
        // untuk tiap pattern item, ambil slot TERBARU-nya (berdasar week_start), lalu saring
        // yang tutor pemegangnya tutor ini — ini dipakai utk tahu "mana baris pola milik saya",
        // supaya tetap kelihatan walau belum ada slot utk minggu yang sedang dilihat.
        $allPatternSlots = ScheduleSlot::whereNotNull('schedule_pattern_item_id')->get();
        $ownedItemIds = $allPatternSlots->groupBy('schedule_pattern_item_id')
            ->map(fn ($group) => $group->sortByDesc('week_start')->first())
            ->filter(fn ($slot) => (int) $slot->assigned_tutor_id === $tutor->id
                && in_array($slot->status, [ScheduleSlot::STATUS_ASSIGNED, ScheduleSlot::STATUS_PENDING_CONFIRMATION, ScheduleSlot::STATUS_VACANT], true))
            ->keys();

        $ownedItems = SchedulePatternItem::with(['pattern', 'session', 'subject', 'studentGroup'])
            ->whereIn('id', $ownedItemIds)
            ->get();

        // Slot aktual (sudah terbentuk lewat "Siapkan Jadwal") utk pattern item di atas, KHUSUS
        // minggu yang dipilih — null berarti belum disiapkan utk minggu itu.
        $slotMingguTerpilih = ScheduleSlot::whereIn('schedule_pattern_item_id', $ownedItemIds)
            ->whereDate('week_start', $weekStart->toDateString())
            ->get()
            ->keyBy('schedule_pattern_item_id');

        $polaSaya = $ownedItems->map(fn ($item) => [
            'item' => $item,
            'slot' => $slotMingguTerpilih->get($item->id),
        ])->sortBy([
            fn ($a, $b) => array_search($a['item']->hari, SchedulePatternItem::HARI) <=> array_search($b['item']->hari, SchedulePatternItem::HARI),
            fn ($a, $b) => ($a['item']->session->time_start ?? '') <=> ($b['item']->session->time_start ?? ''),
        ])->values();

        return view('admin.tutors.show', compact(
            'tutor', 'students', 'subjects', 'scheduleSessions', 'schedulesCount',
            'patterns', 'studentGroups', 'polaSaya', 'mingguPilihan', 'weekStart'
        ));
    }

    /** DataTables server-side untuk tabel "Jadwal Mengajar" di halaman Detail Tutor. */
    public function scheduleData(Tutor $tutor)
    {
        // Peta "tanggal => [jam_mulai => indeks]" dipakai utk memberi warna label jam yang
        // berbeda-beda bila tutor ini punya lebih dari 1 sesi pada hari yang sama.
        $sesiIndexPerTanggal = $tutor->schedules()
            ->select('class_date', 'start_time')
            ->get()
            ->groupBy(fn ($s) => \Carbon\Carbon::parse($s->class_date)->toDateString())
            ->map(fn ($group) => $group->pluck('start_time')
                ->map(fn ($t) => substr($t, 0, 5))
                ->unique()
                ->sort()
                ->values()
                ->flip());
        $sesiWarna = ['primary', 'success', 'warning', 'info', 'danger', 'dark'];

        $query = $tutor->schedules()->with(['student', 'subject', 'evaluation'])
            ->orderBy('class_date', 'desc')->orderBy('start_time', 'desc');

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('class_date', function ($s) {
                return '<div class="fw-semibold">' . \Carbon\Carbon::parse($s->class_date)->translatedFormat('d M Y') . '</div>'
                    . '<small class="text-muted">' . \Carbon\Carbon::parse($s->class_date)->translatedFormat('l') . '</small>';
            })
            ->editColumn('student_name', fn ($s) => e($s->student->full_name ?? '-'))
            ->editColumn('subject_name', function ($s) {
                return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">' . e($s->subject->subject_name ?? '-') . '</span>';
            })
            ->editColumn('room', fn ($s) => e($s->room ?? '-'))
            ->editColumn('time', function ($s) use ($sesiIndexPerTanggal, $sesiWarna) {
                $start = substr($s->start_time, 0, 5);
                $end = substr($s->end_time, 0, 5);
                $sesiHariIni = $sesiIndexPerTanggal->get(\Carbon\Carbon::parse($s->class_date)->toDateString(), collect());

                if ($sesiHariIni->count() > 1) {
                    $warna = $sesiWarna[$sesiHariIni->get($start, 0) % count($sesiWarna)];
                    return '<span class="badge bg-' . $warna . '-subtle text-' . $warna . ' border border-' . $warna . '-subtle">' . $start . ' – ' . $end . '</span>';
                }

                return '<span class="badge bg-light text-dark border">' . $start . ' – ' . $end . '</span>';
            })
            ->editColumn('status_schedule', function ($s) {
                return match ($s->status_schedule) {
                    'scheduled' => '<span class="badge bg-primary">Dijadwalkan</span>',
                    'done'      => '<span class="badge bg-success">Selesai</span>',
                    'canceled'  => '<span class="badge bg-secondary">Dibatalkan</span>',
                };
            })
            ->addColumn('evaluasi', function ($s) {
                if ($s->status_schedule !== 'done') {
                    return '<span class="text-muted small">—</span>';
                }
                if ($s->evaluation) {
                    return '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Sudah</span>';
                }
                return '<button class="btn btn-sm btn-outline-info btn-eval-tutor" data-id="' . $s->id . '"><i class="bi bi-clipboard2-check me-1"></i>Isi</button>';
            })
            ->addColumn('action', function ($s) {
                $btn = '<div class="btn-group btn-group-sm">';
                if ($s->status_schedule === 'scheduled') {
                    $btn .= '<button class="btn btn-outline-success btn-done-tutor" data-id="' . $s->id . '" title="Tandai Selesai"><i class="bi bi-check-lg"></i></button>';
                    $btn .= '<button class="btn btn-outline-warning btn-edit-tutor" data-id="' . $s->id . '" title="Edit"><i class="bi bi-pencil"></i></button>';
                    $btn .= '<button class="btn btn-outline-secondary btn-cancel-tutor" data-id="' . $s->id . '" title="Batalkan"><i class="bi bi-x-circle"></i></button>';
                }
                if ($s->status_schedule === 'done' && $s->evaluation) {
                    $btn .= '<button class="btn btn-outline-info btn-eval-tutor" data-id="' . $s->id . '" title="Edit Evaluasi"><i class="bi bi-clipboard2-check"></i></button>';
                }
                $btn .= '<button class="btn btn-outline-danger btn-delete-tutor" data-id="' . $s->id . '" title="Hapus"><i class="bi bi-trash"></i></button>';
                $btn .= '</div>';
                return $btn;
            })
            ->rawColumns(['class_date', 'subject_name', 'time', 'status_schedule', 'evaluasi', 'action'])
            ->make(true);
    }

    /**
     * Tambah baris Pola Jadwal baru lalu langsung dijadikan milik tutor ini untuk minggu
     * yang dipilih (`week_start`, Senin minggu tsb) — slot dibuat berstatus `assigned` +
     * jadwal asli minggu itu langsung dimaterialisasi, supaya baris ini otomatis "dibawa"
     * sebagai petahana tutor ini di `prepare()` minggu-minggu berikutnya.
     */
    public function storePatternItem(Request $request, Tutor $tutor)
    {
        $data = $request->validate([
            'schedule_pattern_id' => 'required|exists:schedule_patterns,id',
            'hari'                => ['required', Rule::in(SchedulePatternItem::HARI)],
            'session_id'          => 'required|exists:schedule_sessions,id',
            'subject_id'          => 'required|exists:subjects,id',
            'student_group_id'    => 'required|exists:student_groups,id',
            'week_start'          => 'required|date',
        ], [], [
            'schedule_pattern_id' => 'Pola Jadwal',
            'hari'                => 'Hari',
            'session_id'          => 'Sesi',
            'subject_id'          => 'Mata Pelajaran',
            'student_group_id'    => 'Group Siswa',
        ]);

        $pattern = SchedulePattern::findOrFail($data['schedule_pattern_id']);

        $sudahAda = $pattern->items()
            ->where('hari', $data['hari'])
            ->where('session_id', $data['session_id'])
            ->where('student_group_id', $data['student_group_id'])
            ->exists();

        if ($sudahAda) {
            return response()->json([
                'message' => 'Group ini sudah punya jadwal di hari & sesi tersebut pada pola ini.',
                'errors'  => ['student_group_id' => ['Group sudah terpakai di hari & sesi itu.']],
            ], 422);
        }

        $session = ScheduleSession::findOrFail($data['session_id']);
        $weekStart = Carbon::parse($data['week_start'])->startOfWeek(Carbon::MONDAY);
        $classDate = $this->classDateFor($weekStart, $data['hari']);

        if ($conflict = $this->findTutorScheduleConflict($tutor->id, $classDate->toDateString(), $session->time_start, $session->time_end)) {
            $error = "Tutor ini sudah mengajar {$conflict} pada jam yang sama. Pilih hari/sesi lain.";
            return response()->json(['message' => $error, 'errors' => ['session_id' => [$error]]], 422);
        }

        $hasil = DB::transaction(function () use ($pattern, $data, $tutor, $weekStart, $classDate) {
            $item = $pattern->items()->create([
                'hari'             => $data['hari'],
                'session_id'       => $data['session_id'],
                'subject_id'       => $data['subject_id'],
                'student_group_id' => $data['student_group_id'],
            ]);

            $slot = ScheduleSlot::create([
                'schedule_pattern_item_id' => $item->id,
                'week_start'               => $weekStart->toDateString(),
                'class_date'               => $classDate->toDateString(),
                'hari'                     => $data['hari'],
                'session_id'               => $data['session_id'],
                'subject_id'               => $data['subject_id'],
                'student_group_id'         => $data['student_group_id'],
                'status'                   => ScheduleSlot::STATUS_ASSIGNED,
                'assigned_tutor_id'        => $tutor->id,
                'assigned_at'              => now(),
                'assigned_by'              => auth()->id(),
            ]);

            return $this->materializeSlot($slot);
        });

        $pesan = "Pola jadwal baru ditambahkan untuk {$tutor->name} mulai minggu {$weekStart->translatedFormat('d M Y')}. {$hasil['created']} jadwal mengajar dibuat";
        if ($hasil['skipped']) {
            $pesan .= ", {$hasil['skipped']} siswa dilewati (jadwal sudah ada)";
        }
        $pesan .= '.';

        return response()->json(['success' => true, 'message' => $pesan]);
    }

    /**
     * Tetapkan tutor ini ke baris pola tertentu untuk satu minggu spesifik — membuat slot
     * minggu itu bila belum ada (belum "Siapkan Jadwal"), atau mengisi slot yang sudah ada
     * bila statusnya masih `open`/`vacant`. Ditolak bila slot minggu itu sudah terisi tutor
     * LAIN (harus dibatalkan dulu lewat halaman Siapkan Jadwal) atau bentrok jam lain.
     */
    public function assignWeek(Request $request, Tutor $tutor, SchedulePatternItem $item)
    {
        $data = $request->validate(['week_start' => 'required|date']);

        if (!$item->session) {
            return response()->json(['message' => 'Baris pola ini belum punya sesi/jam yang valid.'], 422);
        }

        $weekStart = Carbon::parse($data['week_start'])->startOfWeek(Carbon::MONDAY);
        $classDate = $this->classDateFor($weekStart, $item->hari);

        $slot = ScheduleSlot::where('schedule_pattern_item_id', $item->id)
            ->whereDate('week_start', $weekStart->toDateString())
            ->first();

        if ($slot && (int) $slot->effectiveTutorId() === $tutor->id && $slot->isAssigned()) {
            return response()->json(['message' => 'Slot minggu ini sudah milik tutor ini.'], 422);
        }

        if ($slot && $slot->isAssigned()) {
            return response()->json(['message' => 'Slot minggu ini sudah terisi tutor lain. Batalkan penetapannya dulu di halaman Siapkan Jadwal.'], 422);
        }

        $conflict = $this->findTutorScheduleConflict(
            $tutor->id,
            $classDate->toDateString(),
            $item->session->time_start,
            $item->session->time_end,
            [],
            $slot ? [$slot->id] : []
        );
        if ($conflict) {
            return response()->json(['message' => "Tutor ini sudah mengajar {$conflict} pada jam yang sama."], 422);
        }

        $hasil = DB::transaction(function () use ($item, $slot, $weekStart, $classDate, $tutor) {
            if (!$slot) {
                $slot = ScheduleSlot::create([
                    'schedule_pattern_item_id' => $item->id,
                    'week_start'               => $weekStart->toDateString(),
                    'class_date'               => $classDate->toDateString(),
                    'hari'                     => $item->hari,
                    'session_id'               => $item->session_id,
                    'subject_id'               => $item->subject_id,
                    'student_group_id'         => $item->student_group_id,
                    'status'                   => ScheduleSlot::STATUS_ASSIGNED,
                    'assigned_tutor_id'        => $tutor->id,
                    'assigned_at'              => now(),
                    'assigned_by'              => auth()->id(),
                ]);
            } else {
                $update = $slot->isVacant()
                    ? ['filled_by_tutor_id' => $tutor->id]
                    : ['assigned_tutor_id' => $tutor->id, 'filled_by_tutor_id' => null];

                $slot->update(array_merge($update, [
                    'status'      => ScheduleSlot::STATUS_ASSIGNED,
                    'assigned_at' => now(),
                    'assigned_by' => auth()->id(),
                ]));

                $slot->applications()->where('tutor_id', $tutor->id)->update(['status' => ScheduleSlotApplication::STATUS_ACCEPTED]);
                $slot->applications()->where('tutor_id', '!=', $tutor->id)
                    ->where('status', ScheduleSlotApplication::STATUS_PENDING)
                    ->update(['status' => ScheduleSlotApplication::STATUS_REJECTED]);
            }

            return $this->materializeSlot($slot->fresh());
        });

        $pesan = "{$tutor->name} ditetapkan untuk minggu {$weekStart->translatedFormat('d M Y')}. {$hasil['created']} jadwal mengajar dibuat";
        if ($hasil['skipped']) {
            $pesan .= ", {$hasil['skipped']} siswa dilewati (jadwal sudah ada)";
        }
        $pesan .= '.';

        return response()->json(['success' => true, 'message' => $pesan]);
    }

    public function data()
    {
        return DataTables::of(Tutor::latest())
            ->addIndexColumn()
            ->addColumn('photo_thumb', function ($tutor) {
                if ($tutor->photo) {
                    return '<img src="' . e(asset('storage/' . $tutor->photo)) . '" class="rounded-circle" style="width:38px;height:38px;object-fit:cover;">';
                }
                return '<span class="rounded-circle bg-secondary-subtle text-secondary d-inline-flex align-items-center justify-content-center" style="width:38px;height:38px;"><i class="bi bi-person"></i></span>';
            })
            ->editColumn('specialization', function ($tutor) {
                $specs = is_array($tutor->specialization) ? $tutor->specialization : [];
                return collect($specs)->map(fn($s) => '<span class="badge bg-primary-subtle text-primary me-1">' . e($s) . '</span>')->implode('');
            })
            ->addColumn('kategori_label', function ($tutor) {
                $label = Tutor::KATEGORI_OPTIONS[$tutor->kategori] ?? '-';
                $badge = $tutor->kategori === 'tetap' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary';
                return '<span class="badge ' . $badge . '">' . e($label) . '</span>';
            })
            ->addColumn('action', function ($tutor) {
                $specs = is_array($tutor->specialization) ? $tutor->specialization : [];
                return '
                    <div class="btn-group btn-group-sm">
                        <a class="btn btn-outline-primary" href="' . route('admin.tutors.show', $tutor->id) . '" title="Detail & Jadwal">
                            <i class="bi bi-eye"></i>
                        </a>
                        <button class="btn btn-outline-warning btn-edit"
                            data-id="' . $tutor->id . '"
                            data-name="' . e($tutor->name) . '"
                            data-phone="' . e($tutor->phone) . '"
                            data-email="' . e($tutor->email ?? '') . '"
                            data-norek="' . e($tutor->no_rekening ?? '') . '"
                            data-fee="' . e($tutor->fee_per_session !== null ? (0 + $tutor->fee_per_session) : '') . '"
                            data-fee-private="' . e($tutor->fee_per_student_private !== null ? (0 + $tutor->fee_per_student_private) : '') . '"
                            data-fee-student="' . e($tutor->fee_per_student !== null ? (0 + $tutor->fee_per_student) : '') . '"
                            data-fee-transport="' . e($tutor->fee_transport_per_day !== null ? (0 + $tutor->fee_transport_per_day) : '') . '"
                            data-fee-private-khusus="' . e($tutor->fee_private_khusus !== null ? (0 + $tutor->fee_private_khusus) : '') . '"
                            data-fee-tka-regular="' . e($tutor->fee_tka_regular !== null ? (0 + $tutor->fee_tka_regular) : '') . '"
                            data-fee-private-sma="' . e($tutor->fee_private_sma !== null ? (0 + $tutor->fee_private_sma) : '') . '"
                            data-fee-tka-visit="' . e($tutor->fee_tka_visit !== null ? (0 + $tutor->fee_tka_visit) : '') . '"
                            data-kategori="' . e($tutor->kategori ?? 'freelance') . '"
                            data-gaji-pokok="' . e($tutor->gaji_pokok !== null ? (0 + $tutor->gaji_pokok) : '') . '"
                            data-tunjangan="' . e($tutor->tunjangan_per_bulan !== null ? (0 + $tutor->tunjangan_per_bulan) : '') . '"
                            data-maks-sesi-tunjangan="' . e($tutor->maks_sesi_tunjangan_per_bulan ?? '') . '"
                            data-photo="' . e($tutor->photo ? asset('storage/' . $tutor->photo) : '') . '"
                            data-specialization=\'' . e(json_encode($specs)) . '\'>
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-delete"
                            data-id="' . $tutor->id . '"
                            data-name="' . e($tutor->name) . '">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>';
            })
            ->rawColumns(['photo_thumb', 'specialization', 'kategori_label', 'action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);
        $validated['specialization'] = $request->input('specialization', []);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('tutors', 'public');
        }

        Tutor::create($validated);
        return response()->json(['success' => true, 'message' => 'Tutor berhasil ditambahkan.']);
    }

    public function update(Request $request, Tutor $tutor)
    {
        $validated = $this->validateData($request);
        $validated['specialization'] = $request->input('specialization', []);

        if ($request->hasFile('photo')) {
            if ($tutor->photo) {
                Storage::disk('public')->delete($tutor->photo);
            }
            $validated['photo'] = $request->file('photo')->store('tutors', 'public');
        }

        $tutor->update($validated);
        return response()->json(['success' => true, 'message' => 'Data tutor berhasil diperbarui.']);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name'             => 'required|string|max:100',
            'phone'            => 'required|string|max:20',
            'email'            => 'nullable|email|max:255',
            'no_rekening'      => 'nullable|string|max:50',
            'fee_per_session'         => 'nullable|numeric|min:0',
            'fee_per_student_private' => 'nullable|numeric|min:0',
            'fee_per_student'         => 'nullable|numeric|min:0',
            'fee_transport_per_day'   => 'nullable|numeric|min:0',
            'fee_private_khusus'      => 'nullable|numeric|min:0',
            'fee_tka_regular'         => 'nullable|numeric|min:0',
            'fee_private_sma'         => 'nullable|numeric|min:0',
            'fee_tka_visit'           => 'nullable|numeric|min:0',
            'kategori'                      => 'required|in:freelance,tetap',
            'gaji_pokok'                    => 'nullable|numeric|min:0',
            'tunjangan_per_bulan'           => 'nullable|numeric|min:0',
            'maks_sesi_tunjangan_per_bulan' => 'nullable|integer|min:0',
            'photo'            => 'nullable|image|max:5120', // semua tipe foto, maks 5 MB
            'specialization'   => 'required|array|min:1',
            'specialization.*' => 'string|max:100',
        ], [
            'specialization.required' => 'Pilih minimal satu spesialisasi.',
            'specialization.min'      => 'Pilih minimal satu spesialisasi.',
            'kategori.required'       => 'Pilih kategori tutor.',
            'kategori.in'             => 'Kategori tutor tidak valid.',
        ]);
    }

    public function destroy(Tutor $tutor)
    {
        try {
            $tutor->delete();
            return response()->json(['success' => true, 'message' => 'Tutor berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Tidak dapat dihapus karena terkait dengan jadwal aktif.'], 422);
        }
    }
}
