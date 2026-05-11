<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PaymentController extends Controller
{
    public function index()
    {
        return view('admin.payments.index');
    }

    public function dataPayments(Request $request)
    {
        $query = Payment::with('student')->latest();
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('student_id', function ($pay) {
                return '<div class="fw-semibold">' . ($pay->student->full_name ?? '-') . '</div>
                        <small class="text-muted">' . ($pay->student->nis ?? '-') . '</small>';
            })
            ->editColumn('category_payment', function ($pay) {
                $label = match ($pay->category_payment) {
                    1 => 'Registrasi',
                    2 => 'SPP',
                    3 => 'Kegiatan',
                    default => '-'
                };
                $badgeClass = match ($pay->category_payment) {
                    1 => 'bg-primary-subtle text-primary',
                    2 => 'bg-info-subtle text-info',
                    3 => 'bg-warning-subtle text-warning',
                    default => 'bg-secondary-subtle text-secondary'
                };
                return '<span class="badge ' . $badgeClass . '">' . $label . '</span>';
            })
            ->editColumn('amount', function ($pay) {
                return '<span class="fw-semibold">Rp ' . number_format($pay->amount, 0, ',', '.') . '</span>';
            })
            ->editColumn('payment_method', function ($pay) {
                $icon = $pay->payment_method === 'transfer' ? 'bi-bank' : 'bi-cash-stack';
                return '<i class="bi ' . $icon . ' me-1"></i>' . ucfirst($pay->payment_method);
            })
            ->editColumn('payment_date', function ($pay) {
                return '<small>' . \Carbon\Carbon::parse($pay->payment_date)->format('d M Y') . '</small>';
            })
            ->addColumn('action', function ($pay) {
                return '<div class="btn-group btn-group-sm">
                            <a href="' . route('admin.payments.show', $pay->id) . '" class="btn btn-outline-primary" title="Detail"><i class="bi bi-eye"></i></a>
                            <a href="' . route('admin.payments.edit', $pay->id) . '" class="btn btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                            <button type="button" class="btn btn-outline-danger btn-delete" data-id="' . $pay->id . '" data-name="' . $pay->no_payment . '" title="Hapus"><i class="bi bi-trash"></i></button>
                        </div>';
            })
            ->rawColumns(['student_id', 'category_payment', 'amount', 'payment_method', 'payment_date', 'action'])
            ->make(true);
    }

    public function create()
    {
        $students = Student::where('status', 1)->orderBy('full_name')->get();
        $today = now();
        $count = Payment::whereDate('created_at', $today->toDateString())->count() + 1;
        $noPayment = 'LVR-' . $today->format('ymd') . str_pad($count, 4, '0', STR_PAD_LEFT);

        return view('admin.payments.create', compact('students', 'noPayment'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'payment_date' => 'required|date',
            'expired_date' => 'nullable|date',
            'category_payment' => 'required|in:1,2,3',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,transfer',
            'from' => 'required|string',
            'receiver' => 'required|string',
            'quota' => 'nullable|integer',
        ]);

        $today = now();
        $count = Payment::whereDate('created_at', $today->toDateString())->count() + 1;
        $noPayment = 'LVR-' . $today->format('ymd') . str_pad($count, 4, '0', STR_PAD_LEFT);

        Payment::create([
            'student_id' => $request->student_id,
            'no_payment' => $noPayment,
            'payment_date' => $request->payment_date,
            'expired_date' => $request->expired_date,
            'category_payment' => $request->category_payment,
            'description' => $request->description,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'from' => $request->from,
            'receiver' => $request->receiver,
            'quota' => $request->quota,
        ]);

        return redirect()->route('admin.payments.index')->with('success', 'Pembayaran berhasil ditambahkan.');
    }

    public function show(Payment $payment)
    {
        $payment->load('student');
        return view('admin.payments.show', compact('payment'));
    }

    public function edit(Payment $payment)
    {
        $students = Student::where('status', 1)->orderBy('full_name')->get();
        return view('admin.payments.edit', compact('payment', 'students'));
    }

    public function update(Request $request, Payment $payment)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'payment_date' => 'required|date',
            'expired_date' => 'nullable|date',
            'category_payment' => 'required|in:1,2,3',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,transfer',
            'from' => 'required|string',
            'receiver' => 'required|string',
            'quota' => 'nullable|integer',
        ]);

        $payment->update([
            'student_id' => $request->student_id,
            'payment_date' => $request->payment_date,
            'expired_date' => $request->expired_date,
            'category_payment' => $request->category_payment,
            'description' => $request->description,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'from' => $request->from,
            'receiver' => $request->receiver,
            'quota' => $request->quota,
        ]);

        return redirect()->route('admin.payments.index')->with('success', 'Data pembayaran berhasil diperbarui.');
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();
        return response()->json(['success' => true, 'message' => 'Pembayaran berhasil dihapus.']);
    }

    public function printReceipt(Payment $payment)
    {
        $payment->load('student');
        $amount = $payment->amount;
        $terbilang = $this->terbilang($amount) . ' Rupiah';

        return view('admin.payments.receipt', compact('payment', 'amount', 'terbilang'));
    }

    private function terbilang($nilai)
    {
        $nilai = abs($nilai);
        $huruf = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
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
