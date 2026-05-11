<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudentRegistration;
use App\Models\Student;
use App\Models\ScheduleSession;
use App\Models\ScheduleStudent;

class HomeController extends Controller
{
    public function index()
    {
        return view('website.index');
    }

    public function registration()
    {
        $sessions = ScheduleSession::all();
        return view('website.registration', compact('sessions'));
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
            'package'              => 'nullable|string|max:100',
            'program'              => 'nullable|string|max:100',
            'selected_days'        => 'nullable|string|max:50',
            'schedule_session_id'  => 'nullable|exists:schedule_sessions,id',
            'school_curriculum'    => 'nullable|string|max:100',
            'learning_material'    => 'nullable|string|max:255',
            'promo_code'           => 'nullable|string|max:50',
            'registration_info'    => 'nullable|string|max:100',
            'marketing_pic'        => 'nullable|string|max:100',
        ]);

        $data = $validated;
        $data['status'] = 'Baru';
        $data['registration_code'] = 'REG-' . strtoupper(str_replace(' ', '', substr($request->full_name, 0, 3))) . '-' . date('YmdHis');

        // Save to Student Registration table
        $registration = StudentRegistration::create($data);

        // Save to Student table (status 2 = Non Aktif)
        $studentData = $data;
        $studentData['status'] = 2; // Non Aktif
        $studentData['registration_date'] = $registration->registration_date ?? date('Y-m-d');
        $student = Student::create($studentData);

        // Save to ScheduleStudent table
        if (isset($validated['selected_days']) && isset($validated['schedule_session_id'])) {
            ScheduleStudent::create([
                'student_id' => $student->id,
                'schedule_session_id' => $validated['schedule_session_id'],
                'date' => $validated['selected_days'],
                'notes' => 'Jadwal Pendaftaran Awal'
            ]);
        }

        return redirect()->back()->with('success', 'Pendaftaran berhasil dikirim! Tim kami akan segera menghubungi Anda.');
    }
}
