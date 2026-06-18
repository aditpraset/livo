<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;


use Illuminate\Http\Request;
use App\Models\StudentRegistration;
use App\Models\Student;
use App\Models\Payment;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalRegistrations = StudentRegistration::count();
        $totalStudents = Student::count();
        $totalRevenue = Payment::sum('amount');
        
        $monthlyRegistrations = StudentRegistration::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();
        
        $monthlyRevenue = Payment::whereMonth('payment_date', Carbon::now()->month)
            ->whereYear('payment_date', Carbon::now()->year)
            ->sum('amount');

        return view('admin.index', compact(
            'totalRegistrations',
            'totalStudents',
            'totalRevenue',
            'monthlyRegistrations',
            'monthlyRevenue'
        ));
    }

    public function registrations()
    {
        return view('admin.registrations');
    }

    public function dataDashboard(Request $request)
    {
        $query = StudentRegistration::latest()->take(10);
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('full_name', function ($reg) {
                return '<div class="d-flex align-items-center">
                    <span class="avatar avatar-sm me-3 rounded-circle bg-primary-subtle text-primary fw-bold">' . substr($reg->full_name, 0, 1) . '</span>
                    <div class="flex-fill">
                        <div class="fw-bold text-dark">' . $reg->full_name . '</div>
                        <div class="text-muted small">' . ($reg->nickname ?? '-') . '</div>
                    </div>
                </div>';
            })
            ->editColumn('created_at', function ($reg) {
                return '<div class="text-muted small">' . $reg->created_at->format('d M Y') . '</div>
                        <div class="text-muted extra-small" style="font-size: 0.7rem;">' . $reg->created_at->format('H:i') . '</div>';
            })
            ->editColumn('program', function ($reg) {
                $names = json_decode($reg->program ?? '', true) ?? [];
                if (empty($names)) return '<span class="text-muted">-</span>';
                return implode('', array_map(
                    fn($n) => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">' . e($n) . '</span>',
                    $names
                ));
            })
            ->rawColumns(['full_name', 'created_at', 'program'])
            ->make(true);
    }

    public function dataRegistrations(Request $request)
    {
        $query = StudentRegistration::latest();
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('full_name', function ($reg) {
                return '<div class="fw-semibold">' . $reg->full_name . '</div>
                        <small class="text-muted">' . ($reg->nickname ?? '') . '</small>';
            })
            ->editColumn('program', function ($reg) {
                $names = json_decode($reg->program ?? '', true) ?? [];
                if (empty($names)) return '<span class="text-muted">-</span>';
                return implode('', array_map(
                    fn($n) => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">' . e($n) . '</span>',
                    $names
                ));
            })
            ->editColumn('status', function ($reg) {
                $badgeClass = match ($reg->status) {
                    'Lunas' => 'bg-success',
                    'Belum Lunas' => 'bg-warning',
                    default => 'bg-secondary text-white'
                };
                return '<span class="badge ' . $badgeClass . '">' . ($reg->status ?? 'Baru') . '</span>';
            })
            ->editColumn('created_at', function ($reg) {
                return '<small>' . $reg->created_at->format('d M Y') . '</small>';
            })
            ->addColumn('action', function ($reg) {
                return '<a href="' . route('admin.registrations.show', $reg->id) . '" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>';
            })
            ->rawColumns(['full_name', 'program', 'status', 'created_at', 'action'])
            ->make(true);
    }

    public function showRegistration(StudentRegistration $registration)
    {
        if (request()->ajax()) {
            $registration->load('scheduleSession');
            $registration->append('program_label');
            return response()->json($registration);
        }
        return view('admin.registrations.show', compact('registration'));
    }

    public function storePayment(Request $request, StudentRegistration $registration)
    {
        $request->validate([
            'payment_date' => 'required|date',
            'expired_date' => 'nullable|date',
            'description' => 'required|string',
            'amount' => 'required|numeric',
            'payment_method' => 'required|in:cash,transfer',
            'from' => 'required|string',
            'receiver' => 'required|string',
            'quota' => 'nullable|integer',
        ]);

        // Create student if not exists
        $student = null;
        if ($registration->nis) {
            $student = \App\Models\Student::where('nis', $registration->nis)->first();
        }

        if (!$student) {
            $student = \App\Models\Student::where('registration_code', $registration->registration_code)->first();
        }

        if ($student) {
            $student->update(['status' => 1]);
        } else {
            $student = \App\Models\Student::create([
                'registration_code' => $registration->registration_code,
                'nis' => $registration->nis,
                'status' => 1, // aktif
                'registration_date' => $registration->registration_date ?? now(),
                'full_name' => $registration->full_name,
                'nickname' => $registration->nickname,
                'birth_date' => $registration->birth_date,
                'religion' => $registration->religion,
                'gender' => $registration->gender,
                'grade' => $registration->grade,
                'school_origin' => $registration->school_origin,
                'father_name' => $registration->father_name,
                'mother_name' => $registration->mother_name,
                'guardian_name' => $registration->guardian_name,
                'address' => $registration->address,
                'email' => $registration->email,
                'phone' => $registration->phone,
                'whatsapp' => $registration->whatsapp,
                'class_type' => $registration->class_type,
                'kbm_process' => $registration->kbm_process,
                'package' => $registration->package,
                'program' => $registration->program,
                'selected_days' => $registration->selected_days,
                'schedule_session_id' => $registration->schedule_session_id,
                'school_curriculum' => $registration->school_curriculum,
                'learning_material' => $registration->learning_material,
                'promo_code' => $registration->promo_code,
                'registration_info' => $registration->registration_info,
                'marketing_pic' => $registration->marketing_pic,
            ]);
        }

        // Generate no_payment securely on the server
        $today = now();
        $count = \App\Models\Payment::whereDate('created_at', $today->toDateString())->count() + 1;
        $no_payment = 'LVR-' . $today->format('ymd') . str_pad($count, 4, '0', STR_PAD_LEFT);

        // Create Payment
        \App\Models\Payment::create([
            'student_id' => $student->id,
            'no_payment' => $no_payment,
            'payment_date' => $request->payment_date,
            'expired_date' => $request->expired_date,
            'category_payment' => 1, // Registrasi
            'description' => $request->description,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'from' => $request->from,
            'receiver' => $request->receiver,
            'quota' => $request->quota,
        ]);

        // Tambahkan kuota sesi siswa sesuai nilai kuota pada pembayaran registrasi
        if ((int) $request->quota > 0) {
            $student->increment('quota_sessions', (int) $request->quota);
        }

        // Update registration status to Lunas if not already
        if ($registration->status !== 'Lunas') {
            $registration->update(['status' => 'Lunas']);
        }

        return redirect()->back()->with('success', 'Pembayaran berhasil disimpan dan status menjadi Lunas.');
    }

    public function updateStatus(Request $request, StudentRegistration $registration)
    {
        $request->validate([
            'status' => 'required|in:Baru,Belum Lunas,Lunas'
        ]);

        $oldStatus = $registration->status;
        $registration->update(['status' => $request->status]);

        // If status changed to Lunas, create Student and Payment
        if ($request->status === 'Lunas' && $oldStatus !== 'Lunas') {
            // 1. Create or update Student
            $student = null;
            if ($registration->nis) {
                $student = \App\Models\Student::where('nis', $registration->nis)->first();
            }

            if (!$student) {
                $student = \App\Models\Student::where('registration_code', $registration->registration_code)->first();
            }

            if ($student) {
                $student->update(['status' => 1]);
            } else {
                $student = \App\Models\Student::create([
                    'registration_code' => $registration->registration_code,
                    'nis' => $registration->nis,
                    'status' => 1, // aktif
                    'registration_date' => $registration->registration_date ?? now(),
                    'full_name' => $registration->full_name,
                    'nickname' => $registration->nickname,
                    'birth_date' => $registration->birth_date,
                    'religion' => $registration->religion,
                    'gender' => $registration->gender,
                    'grade' => $registration->grade,
                    'school_origin' => $registration->school_origin,
                    'father_name' => $registration->father_name,
                    'mother_name' => $registration->mother_name,
                    'guardian_name' => $registration->guardian_name,
                    'address' => $registration->address,
                    'email' => $registration->email,
                    'phone' => $registration->phone,
                    'whatsapp' => $registration->whatsapp,
                    'class_type' => $registration->class_type,
                    'kbm_process' => $registration->kbm_process,
                    'package' => $registration->package,
                    'program' => $registration->program,
                    'selected_days' => $registration->selected_days,
                    'schedule_session_id' => $registration->schedule_session_id,
                    'school_curriculum' => $registration->school_curriculum,
                    'learning_material' => $registration->learning_material,
                    'promo_code' => $registration->promo_code,
                    'registration_info' => $registration->registration_info,
                    'marketing_pic' => $registration->marketing_pic,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Status pendaftaran berhasil diperbarui.');
    }

    public function printReceipt(StudentRegistration $registration)
    {
        if ($registration->status !== 'Lunas') {
            return redirect()->back()->with('error', 'Kwitansi hanya tersedia untuk pendaftaran yang sudah Lunas.');
        }

        $student = \App\Models\Student::where('registration_code', $registration->registration_code)->first();
        $payment = null;

        if ($student) {
            $payment = \App\Models\Payment::where('student_id', $student->id)
                ->where('category_payment', 1)
                ->first();
        }

        // Use actual amount if payment exists, otherwise fallback to 200000
        $amount = $payment ? $payment->amount : 200000;
        $terbilang = $this->terbilang($amount) . ' Rupiah';

        return view('admin.registrations.receipt', compact('registration', 'amount', 'terbilang', 'payment'));
    }

    private function terbilang($nilai)
    {
        $nilai = abs($nilai);
        $huruf = array("", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas");
        $temp = "";
        if ($nilai < 12) {
            $temp = " " . $huruf[$nilai];
        } else if ($nilai < 20) {
            $temp = $this->terbilang($nilai - 10) . " Belas";
        } else if ($nilai < 100) {
            $temp = $this->terbilang($nilai / 10) . " Puluh" . $this->terbilang($nilai % 10);
        } else if ($nilai < 200) {
            $temp = " Seratus" . $this->terbilang($nilai - 100);
        } else if ($nilai < 1000) {
            $temp = $this->terbilang($nilai / 100) . " Ratus" . $this->terbilang($nilai % 100);
        } else if ($nilai < 2000) {
            $temp = " Seribu" . $this->terbilang($nilai - 1000);
        } else if ($nilai < 1000000) {
            $temp = $this->terbilang($nilai / 1000) . " Ribu" . $this->terbilang($nilai % 1000);
        } else if ($nilai < 1000000000) {
            $temp = $this->terbilang($nilai / 1000000) . " Juta" . $this->terbilang($nilai % 1000000);
        }
        return $temp;
    }
}
