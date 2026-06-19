<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClassSchedule;
use App\Models\Grade;
use App\Models\Package;
use App\Models\Program;
use App\Models\Promo;
use App\Models\ScheduleSession;
use App\Models\ScheduleStudent;
use App\Models\Student;
use App\Models\StudentRegistration;
use App\Models\Subject;

class HomeController extends Controller
{
    public function index()
    {
        return view('website.index');
    }

    public function registration()
    {
        $subjects = Subject::orderBy('subject_name')->get();
        $programs = Program::orderBy('program_name')->get(['id', 'program_name', 'duration']);
        $grades   = Grade::orderBy('grade_name')->get(['id', 'grade_name']);
        $packages = Package::orderBy('package_name')->get(['id', 'package_name']);

        // Master jadwal kelas → dipakai untuk filter jadwal (hari + sesi jadi satu) berdasarkan kelas
        $classSchedules = ClassSchedule::with('session')->get()->map(function ($c) {
            return [
                'id'           => $c->id,
                'kelas'        => $c->kelas,
                'hari_label'   => $c->hari,
                'session_id'   => $c->session_id,
                'session_name' => $c->session?->name,
                'session_time' => $c->session
                    ? date('H:i', strtotime($c->session->time_start)) . ' - ' . date('H:i', strtotime($c->session->time_end))
                    : null,
            ];
        });

        return view('website.registration', compact('subjects', 'programs', 'grades', 'packages', 'classSchedules'));
    }

    public function checkPromo(Request $request)
    {
        $code = strtoupper(trim($request->input('code', '')));

        if (!$code) {
            return response()->json(['valid' => false, 'message' => 'Masukkan kode promo.']);
        }

        $promo = Promo::where('code', $code)->first();

        if (!$promo) {
            return response()->json(['valid' => false, 'message' => 'Kode promo tidak ditemukan.']);
        }

        if (!$promo->isValid()) {
            return response()->json(['valid' => false, 'message' => 'Promo sudah tidak aktif atau kadaluarsa.']);
        }

        // Pendaftaran tidak mengecek harga → cukup validasi keabsahan kode promo
        $discount   = 0;
        $finalPrice = 0;

        return response()->json([
            'valid'           => true,
            'promo_id'        => $promo->id,
            'message'         => $promo->discount_label . ' berhasil diterapkan!',
            'discount_label'  => $promo->discount_label,
            'discount_amount' => $discount,
            'final_price'     => $finalPrice,
        ]);
    }

    public function storeRegistration(Request $request)
    {
        $validated = $request->validate([
            'full_name'            => 'required|string|max:255',
            'nickname'             => 'nullable|string|max:255',
            'nis'                  => 'nullable|string|max:50',
            'registration_date'    => 'nullable|date',
            'birth_date'           => 'nullable|date',
            'religion'             => 'nullable|string|max:50',
            'gender'               => 'nullable|string|max:20',
            'grade'                => 'nullable|string|max:50',
            'school_origin'        => 'nullable|string|max:255',
            'father_name'          => 'nullable|string|max:255',
            'mother_name'          => 'nullable|string|max:255',
            'guardian_name'        => 'nullable|string|max:255',
            'address'              => 'nullable|string',
            'email'                => 'nullable|email|max:255',
            'phone'                => 'nullable|string|max:20',
            'whatsapp'             => 'nullable|string|max:20',
            'class_type'           => 'nullable|string|max:50',
            'kbm_process'          => 'nullable|string|max:100',
            'program_id'           => 'nullable|exists:programs,id',
            'grade_id'             => 'nullable|exists:grades,id',
            'duration'             => 'nullable|integer|in:1,3,6,12',
            'package_id'           => 'nullable|exists:packages,id',
            'program'              => 'nullable|array',
            'program.*'            => 'string|max:100',
            'class_schedule_ids'   => 'nullable|array',
            'class_schedule_ids.*' => 'nullable|exists:class_schedules,id',
            'school_curriculum'    => 'nullable|string|max:100',
            'learning_material'    => 'nullable|string|max:255',
            'promo_code'           => 'nullable|string|max:50',
            'registration_info'    => 'nullable|string|max:100',
            'marketing_pic'        => 'nullable|string|max:100',
        ]);

        // Selesaikan data program (multi-select → JSON string)
        $programNames = [];
        if (!empty($validated['program'])) {
            $subjectIds = array_filter($validated['program'], 'is_numeric');
            $programNames = Subject::whereIn('id', $subjectIds)->pluck('subject_name')->toArray();
        }

        // Validasi & resolve promo
        $promoId = null;
        if (!empty($validated['promo_code'])) {
            $promo = Promo::where('code', strtoupper($validated['promo_code']))->first();
            if ($promo && $promo->isValid()) {
                $promoId = $promo->id;
            }
        }

        // Nama paket untuk kolom `package` (tanpa cek ke master harga)
        $packageName = null;
        if (!empty($validated['package_id'])) {
            $pkg  = Package::find($validated['package_id']);
            $prog = !empty($validated['program_id']) ? Program::find($validated['program_id']) : null;
            $packageName = trim(($pkg?->package_name ?? '') . ' - ' . ($prog?->program_name ?? ''), ' -');
        }

        // Resolve jadwal dari master jadwal yang dipilih (bisa lebih dari satu sesuai durasi program)
        $scheduleIds      = array_values(array_filter($validated['class_schedule_ids'] ?? []));
        $classSchedules   = ClassSchedule::with('session')->whereIn('id', $scheduleIds)->get();
        $selectedDays     = $classSchedules->pluck('hari')->implode(', ');
        $firstSessionId   = $classSchedules->first()?->session_id;

        $data = array_merge($validated, [
            'class_type'          => $validated['grade'] ?? null,
            'status'              => 'Baru',
            'registration_code'   => 'REG-' . strtoupper(str_replace(' ', '', substr($request->full_name, 0, 3))) . '-' . date('YmdHis'),
            'program'             => !empty($programNames) ? json_encode($programNames) : null,
            'package'             => $packageName,
            'promo_id'            => $promoId,
            'class_schedule_ids'  => !empty($scheduleIds) ? $scheduleIds : null,
            'selected_days'       => $selectedDays ?: null,
            'schedule_session_id' => $firstSessionId,
        ]);

        $registration = StudentRegistration::create($data);

        // Simpan ke tabel students (status 2 = Non Aktif)
        $studentData = array_merge($data, [
            'status'            => 2,
            'registration_date' => $registration->registration_date ?? date('Y-m-d'),
        ]);
        $student = Student::create($studentData);

        // Simpan setiap jadwal terpilih ke ScheduleStudent (satu baris per pertemuan)
        foreach ($classSchedules as $cs) {
            ScheduleStudent::create([
                'student_id'          => $student->id,
                'schedule_session_id' => $cs->session_id,
                'date'                => $cs->hari,
                'notes'               => 'Jadwal Pendaftaran Awal',
            ]);
        }

        return redirect()->back()->with('success', 'Pendaftaran berhasil dikirim! Tim kami akan segera menghubungi Anda.');
    }
}
