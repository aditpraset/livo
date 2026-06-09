<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Package;
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
        $sessions = ScheduleSession::orderBy('time_start')->get();
        $subjects = Subject::orderBy('subject_name')->get();
        $packages = Package::orderBy('price')->get();
        return view('website.registration', compact('sessions', 'subjects', 'packages'));
    }

    public function checkPromo(Request $request)
    {
        $code      = strtoupper(trim($request->input('code', '')));
        $packageId = $request->input('package_id');

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

        $packagePrice = 0;
        if ($packageId) {
            $pkg = Package::find($packageId);
            $packagePrice = $pkg ? (float) $pkg->price : 0;
        }

        if ($promo->min_package_price && $packagePrice < $promo->min_package_price) {
            return response()->json([
                'valid'   => false,
                'message' => 'Promo ini hanya berlaku untuk paket dengan harga minimal Rp ' . number_format($promo->min_package_price, 0, ',', '.'),
            ]);
        }

        $discount   = $promo->calculateDiscount($packagePrice);
        $finalPrice = max(0, $packagePrice - $discount);

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
            'package_id'           => 'nullable|exists:packages,id',
            'program'              => 'nullable|array',
            'program.*'            => 'string|max:100',
            'selected_days'        => 'nullable|string|max:50',
            'schedule_session_id'  => 'nullable|exists:schedule_sessions,id',
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

        // Ambil nama paket untuk backward compat di kolom `package`
        $packageName = null;
        if (!empty($validated['package_id'])) {
            $pkg = Package::find($validated['package_id']);
            $packageName = $pkg?->package_name;
        }

        $data = array_merge($validated, [
            'status'            => 'Baru',
            'registration_code' => 'REG-' . strtoupper(str_replace(' ', '', substr($request->full_name, 0, 3))) . '-' . date('YmdHis'),
            'program'           => !empty($programNames) ? json_encode($programNames) : null,
            'package'           => $packageName,
            'promo_id'          => $promoId,
        ]);

        $registration = StudentRegistration::create($data);

        // Simpan ke tabel students (status 2 = Non Aktif)
        $studentData = array_merge($data, [
            'status'            => 2,
            'registration_date' => $registration->registration_date ?? date('Y-m-d'),
        ]);
        $student = Student::create($studentData);

        // Simpan jadwal awal ke ScheduleStudent
        if (!empty($validated['selected_days']) && !empty($validated['schedule_session_id'])) {
            ScheduleStudent::create([
                'student_id'          => $student->id,
                'schedule_session_id' => $validated['schedule_session_id'],
                'date'                => $validated['selected_days'],
                'notes'               => 'Jadwal Pendaftaran Awal',
            ]);
        }

        return redirect()->back()->with('success', 'Pendaftaran berhasil dikirim! Tim kami akan segera menghubungi Anda.');
    }
}
