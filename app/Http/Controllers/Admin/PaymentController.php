<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yajra\DataTables\Facades\DataTables;

class PaymentController extends Controller
{
    /** Kolom template import pembayaran (urut sesuai sheet "Data Pembayaran"). */
    private array $importColumns = [
        'ID Siswa* (Master Siswa)', 'Tanggal Bayar (YYYY-MM-DD)*', 'Tanggal Expired (YYYY-MM-DD)',
        'Kategori (1/2/3)*', 'Deskripsi', 'Jumlah*', 'Metode (cash/transfer)',
        'Dari (Pembayar)', 'Penerima', 'Kuota',
    ];

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

    /** Unduh template Excel untuk import pembayaran (beserta sheet master & konstanta). */
    public function template()
    {
        $spreadsheet = new Spreadsheet();

        // ── Sheet 1: Data Pembayaran ──
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Pembayaran');
        $sheet->fromArray($this->importColumns, null, 'A1');
        $sheet->fromArray([[
            1, now()->format('Y-m-d'), now()->addMonth()->format('Y-m-d'),
            2, 'Pembayaran SPP Bulan ' . now()->translatedFormat('F'), 350000, 'transfer',
            'Bapak Budi', auth()->user()->name ?? 'Admin', 8,
        ]], null, 'A2');

        $lastCol = $sheet->getHighestColumn();
        $sheet->getStyle('A1:' . $lastCol . '1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:' . $lastCol . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2C3E73');
        for ($c = 1; $c <= Coordinate::columnIndexFromString($lastCol); $c++) {
            $sheet->getColumnDimensionByColumn($c)->setWidth(24);
        }

        // ── Helper sheet master / konstanta ──
        $addSheet = function (string $title, array $headers, array $rows) use ($spreadsheet) {
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
            for ($c = 1; $c <= Coordinate::columnIndexFromString($last); $c++) {
                $ws->getColumnDimensionByColumn($c)->setWidth(26);
            }
        };

        $addSheet('Master Siswa', ['ID', 'Nama Siswa', 'NIS', 'Kelas'],
            Student::orderBy('full_name')->get()->map(fn($s) => [$s->id, $s->full_name, $s->nis, $s->grade])->toArray());

        $addSheet('Kategori Pembayaran', ['Kode', 'Kategori'], [
            [1, 'Registrasi'],
            [2, 'SPP'],
            [3, 'Kegiatan'],
        ]);

        $addSheet('Metode Pembayaran', ['Kode'], [
            ['cash'],
            ['transfer'],
        ]);

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'template-import-pembayaran.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** Import pembayaran dari file Excel sesuai template. */
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
            $sheet = $spreadsheet->getSheetByName('Data Pembayaran') ?? $spreadsheet->getSheet(0);
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

        $today = now();
        $seq   = Payment::whereDate('created_at', $today->toDateString())->count();

        foreach ($rows as $i => $row) {
            $line = $i + 2;
            $get  = fn($idx) => isset($row[$idx]) ? trim((string) $row[$idx]) : '';

            // Lewati baris kosong
            if ($get(0) === '' && $get(1) === '' && $get(5) === '') {
                continue;
            }

            // Siswa (wajib)
            $student = $get(0) !== '' ? Student::find($get(0)) : null;
            if (!$student) {
                $skipped++;
                $errors[] = "Baris {$line}: ID Siswa '{$get(0)}' tidak ditemukan.";
                continue;
            }

            // Tanggal bayar (wajib)
            $paymentDate = $get(1);
            if ($paymentDate === '' || !strtotime($paymentDate)) {
                $skipped++;
                $errors[] = "Baris {$line}: Tanggal Bayar tidak valid.";
                continue;
            }
            $paymentDate = date('Y-m-d', strtotime($paymentDate));

            // Kategori (wajib, 1/2/3)
            $category = (int) $get(3);
            if (!in_array($category, [1, 2, 3], true)) {
                $skipped++;
                $errors[] = "Baris {$line}: Kategori '{$get(3)}' tidak valid (gunakan 1/2/3).";
                continue;
            }

            // Jumlah (wajib, numerik)
            $amountRaw = str_replace(['.', ',', ' ', 'Rp'], '', $get(5));
            if ($get(5) === '' || !is_numeric($amountRaw)) {
                $skipped++;
                $errors[] = "Baris {$line}: Jumlah tidak valid.";
                continue;
            }
            $amount = (float) $amountRaw;

            // Tanggal expired (opsional)
            $expiredDate = $get(2) !== '' && strtotime($get(2)) ? date('Y-m-d', strtotime($get(2))) : null;

            // Metode (default cash)
            $method = strtolower($get(6));
            if (!in_array($method, ['cash', 'transfer'], true)) {
                $method = 'cash';
            }

            $quota = $get(9) !== '' ? (int) $get(9) : null;

            $seq++;
            $noPayment = 'LVR-' . $today->format('ymd') . str_pad($seq, 4, '0', STR_PAD_LEFT);

            Payment::create([
                'student_id'       => $student->id,
                'no_payment'       => $noPayment,
                'payment_date'     => $paymentDate,
                'expired_date'     => $expiredDate,
                'category_payment' => $category,
                'description'      => $get(4) !== '' ? $get(4) : 'Pembayaran',
                'amount'           => $amount,
                'payment_method'   => $method,
                'from'             => $get(7) !== '' ? $get(7) : $student->full_name,
                'receiver'         => $get(8) !== '' ? $get(8) : (auth()->user()->name ?? 'Admin'),
                'quota'            => $quota,
            ]);

            // Tambah kuota sesi siswa untuk pembayaran registrasi yang menyertakan kuota
            if ($category === 1 && $quota > 0) {
                $student->increment('quota_sessions', $quota);
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

        $message = "{$inserted} pembayaran berhasil diimport";
        if ($skipped > 0) {
            $message .= ", {$skipped} baris dilewati";
        }
        $message .= '.';

        return response()->json(['success' => true, 'message' => $message, 'errors' => $errors]);
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
