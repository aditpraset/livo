<?php

namespace App\Http\Controllers\Siswa;

use App\Models\Payment;
use Yajra\DataTables\Facades\DataTables;

class PaymentController extends BaseSiswaController
{
    /** Riwayat pembayaran milik siswa yang sedang login. */
    public function index()
    {
        $student = $this->student();

        $payments = Payment::where('student_id', $student->id)->get();

        $stats = [
            'total_paid' => $payments->sum('amount'),
            'count' => $payments->count(),
            'last_expired' => $payments->whereNotNull('expired_date')->max('expired_date'),
        ];

        return view('siswa.payments.index', compact('student', 'stats'));
    }

    /** Data server-side untuk tabel riwayat pembayaran. */
    public function data()
    {
        $student = $this->student();

        $query = Payment::where('student_id', $student->id)->latest('payment_date');

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('payment_date', fn ($p) => \Carbon\Carbon::parse($p->payment_date)->translatedFormat('d M Y'))
            ->editColumn('category_payment', function ($p) {
                $label = match ((int) $p->category_payment) {
                    1 => 'Registrasi', 2 => 'SPP', 3 => 'Kegiatan', 4 => 'Registrasi dan SPP', default => '-',
                };
                $cls = match ((int) $p->category_payment) {
                    1 => 'bg-primary', 2 => 'bg-info', 3 => 'bg-warning', 4 => 'bg-success', default => 'bg-secondary',
                };
                return '<span class="badge ' . $cls . '">' . $label . '</span>';
            })
            ->editColumn('amount', fn ($p) => '<span class="fw-semibold">Rp ' . number_format($p->amount, 0, ',', '.') . '</span>')
            ->editColumn('payment_method', fn ($p) => ucfirst($p->payment_method))
            ->addColumn('masa_aktif', function ($p) {
                if (!$p->expired_date) return '<span class="text-muted">—</span>';
                $expired = \Carbon\Carbon::parse($p->expired_date);
                $active = $p->active_date ? \Carbon\Carbon::parse($p->active_date) : null;
                $range = ($active ? $active->translatedFormat('d M Y') . ' – ' : 's/d ') . $expired->translatedFormat('d M Y');
                $badge = $expired->isPast()
                    ? '<span class="badge bg-danger ms-1">Kedaluwarsa</span>'
                    : '<span class="badge bg-success ms-1">Aktif</span>';
                return '<small>' . $range . '</small>' . $badge;
            })
            ->rawColumns(['category_payment', 'amount', 'masa_aktif'])
            ->make(true);
    }
}
