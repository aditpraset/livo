<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ComputesTutorFee;
use App\Http\Controllers\Controller;
use App\Models\FeePeriod;
use App\Models\Tutor;
use App\Models\TutorFee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yajra\DataTables\Facades\DataTables;

/**
 * Fee Tutor (admin): pilih bulan → generate (hitung fee seluruh tutor) →
 * review detail → terbitkan. Tutor baru bisa melihat fee setelah periode
 * berstatus "published".
 */
class TutorFeeController extends Controller
{
    use ComputesTutorFee;

    /** Halaman utama: pilih bulan, lihat status periode & tabel review. */
    public function index(Request $request)
    {
        $month = $this->resolveMonth($request);
        $period = FeePeriod::where('month', $month->toDateString())->first();

        return view('admin.tutor-fees.index', compact('month', 'period'));
    }

    /** Data server-side tabel review (per tutor) untuk periode/bulan terpilih. */
    public function data(Request $request)
    {
        $month = $this->resolveMonth($request);
        $period = FeePeriod::where('month', $month->toDateString())->first();

        if (!$period) {
            return DataTables::of(collect())->with('grand_total', 0)->make(true);
        }

        $query = TutorFee::with('tutor')->where('fee_period_id', $period->id)->orderByDesc('total');

        // Total seluruh tutor bulan ini — dihitung terpisah dari query berpaginasi
        // di atas, supaya benar walau tabelnya dipaginasi server-side.
        $grandTotal = (float) TutorFee::where('fee_period_id', $period->id)->sum('total');

        $rp = fn ($v) => 'Rp ' . number_format($v, 0, ',', '.');
        $editable = !$period->isPublished();

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('tutor_name', fn ($tf) => e($tf->tutor->name ?? '-'))
            ->addColumn('kategori_label', function ($tf) {
                $kategori = $tf->tutor->kategori ?? 'freelance';
                $label = \App\Models\Tutor::KATEGORI_OPTIONS[$kategori] ?? '-';
                $badge = $kategori === 'tetap' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary';
                return '<span class="badge ' . $badge . '">' . e($label) . '</span>';
            })
            // Tutor tetap: '-' hanya bila memang tidak ada fee sesi. Paket "sesi saja"
            // (mis. TKA Visit) dibayar juga untuk tutor tetap, jadi nominalnya harus tampil.
            ->addColumn('session', fn ($tf) => ($tf->tutor?->kategori === 'tetap' && (float) $tf->fee_session == 0.0)
                ? '-'
                : ($tf->session_count . ' sesi<br><small class="text-muted">' . $rp($tf->fee_session) . '</small>'))
            ->addColumn('private', fn ($tf) => $tf->tutor?->kategori === 'tetap' ? '-' : ($tf->private_count . ' sesi<br><small class="text-muted">' . $rp($tf->fee_private) . '</small>'))
            ->addColumn('regular', fn ($tf) => $tf->tutor?->kategori === 'tetap' ? '-' : ($tf->regular_count . ' siswa<br><small class="text-muted">' . $rp($tf->fee_regular) . '</small>'))
            ->addColumn('transport', fn ($tf) => $tf->tutor?->kategori === 'tetap' ? '-' : ($tf->day_count . ' hari<br><small class="text-muted">' . $rp($tf->fee_transport) . '</small>'))
            ->addColumn('session_detail', function ($tf) use ($rp) {
                $rows = $tf->session_breakdown ?: [];
                if (empty($rows)) {
                    return '<span class="text-muted">-</span>';
                }
                return collect($rows)->map(fn ($r) => '<div class="small text-nowrap">'
                    . e($r['label'] ?? '-') . ': <strong>' . (int) ($r['count'] ?? 0) . '</strong> sesi × '
                    . $rp($r['rate'] ?? 0) . ' = ' . $rp($r['subtotal'] ?? 0) . '</div>')->implode('');
            })
            ->addColumn('pokok_tunjangan', fn ($tf) => $tf->tutor?->kategori === 'tetap'
                ? ($rp($tf->fee_pokok) . ' + ' . $rp($tf->fee_tunjangan) . '<br><small class="text-muted">gapok + tunjangan</small>')
                : '-')
            ->addColumn('extra_session', fn ($tf) => $tf->tutor?->kategori === 'tetap'
                ? ($tf->extra_session_count . ' sesi<br><small class="text-muted">' . $rp($tf->fee_extra_session) . '</small>')
                : '-')
            ->addColumn('insentif', fn ($tf) => (float) $tf->fee_insentif > 0
                ? '<span class="fw-semibold text-success">' . $rp($tf->fee_insentif) . '</span>'
                : '<span class="text-muted">-</span>')
            ->addColumn('total', fn ($tf) => '<strong>' . $rp($tf->total) . '</strong>')
            ->addColumn('action', function ($tf) use ($editable) {
                if (!$editable) return '<span class="text-muted small">Terkunci</span>';
                return '<button type="button" class="btn btn-sm btn-outline-warning btn-edit-fee"
                        data-id="' . $tf->id . '"
                        data-name="' . e($tf->tutor->name ?? '-') . '"
                        data-kategori="' . e($tf->tutor->kategori ?? 'freelance') . '"
                        data-private-count="' . $tf->private_count . '" data-fee-private="' . (0 + $tf->fee_private) . '"
                        data-regular-count="' . $tf->regular_count . '" data-fee-regular="' . (0 + $tf->fee_regular) . '"
                        data-session-count="' . $tf->session_count . '" data-fee-session="' . (0 + $tf->fee_session) . '"
                        data-day-count="' . $tf->day_count . '" data-fee-transport="' . (0 + $tf->fee_transport) . '"
                        data-fee-pokok="' . (0 + $tf->fee_pokok) . '" data-fee-tunjangan="' . (0 + $tf->fee_tunjangan) . '"
                        data-extra-session-count="' . $tf->extra_session_count . '" data-fee-extra-session="' . (0 + $tf->fee_extra_session) . '"
                        data-fee-insentif="' . (0 + $tf->fee_insentif) . '"
                        data-total="' . (0 + $tf->total) . '"
                        title="Edit Fee"><i class="bi bi-pencil"></i></button>';
            })
            ->rawColumns(['kategori_label', 'session', 'private', 'regular', 'transport', 'session_detail', 'pokok_tunjangan', 'extra_session', 'insentif', 'total', 'action'])
            ->with('grand_total', $grandTotal)
            ->make(true);
    }

    /**
     * Generate/hitung ulang fee seluruh tutor untuk bulan terpilih.
     * Hanya boleh dilakukan selama periode masih draft (atau belum ada sama sekali).
     */
    public function generate(Request $request)
    {
        $request->validate(['month' => 'required|date_format:Y-m']);
        $month = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();

        $period = FeePeriod::where('month', $month->toDateString())->first();
        if ($period && $period->isPublished()) {
            return response()->json(['success' => false, 'message' => 'Periode ini sudah diterbitkan. Batalkan penerbitan terlebih dahulu untuk menghitung ulang.'], 422);
        }

        DB::transaction(function () use ($month, &$period) {
            $period = FeePeriod::updateOrCreate(
                ['month' => $month->toDateString()],
                ['status' => 'draft', 'generated_at' => now(), 'generated_by' => auth()->id()]
            );

            // Insentif diisi MANUAL oleh admin, bukan dihitung sistem — simpan dulu
            // sebelum baris lama dihapus, lalu kembalikan agar tidak hilang saat hitung ulang.
            $insentif = TutorFee::where('fee_period_id', $period->id)
                ->pluck('fee_insentif', 'tutor_id');

            // Hitung ulang dari awal agar tidak ada sisa data tutor yang sudah tidak aktif.
            TutorFee::where('fee_period_id', $period->id)->delete();

            $tutors = Tutor::all();
            foreach ($tutors as $tutor) {
                $breakdown = $this->tutorFeeForMonth($tutor, $month);
                $feeInsentif = (float) ($insentif[$tutor->id] ?? 0);

                // Lewati tutor tanpa aktivitas & tanpa fee sama sekali bulan ini —
                // kecuali ia punya insentif manual, yang tetap harus dibayarkan.
                if ($breakdown['session_count'] == 0 && $breakdown['total'] == 0 && $feeInsentif == 0.0) {
                    continue;
                }

                $breakdown['fee_insentif'] = $feeInsentif;
                $breakdown['total'] = $breakdown['total'] + $feeInsentif;

                TutorFee::create(array_merge(
                    ['fee_period_id' => $period->id, 'tutor_id' => $tutor->id],
                    $breakdown
                ));
            }
        });

        $count = TutorFee::where('fee_period_id', $period->id)->count();

        return response()->json([
            'success' => true,
            'message' => "Fee berhasil digenerate untuk {$count} tutor pada " . $month->locale('id')->translatedFormat('F Y') . '.',
        ]);
    }

    /**
     * Edit manual komponen fee & total satu tutor pada suatu periode.
     * Hanya boleh dilakukan selama periode masih draft (belum diterbitkan).
     */
    public function updateRow(Request $request, TutorFee $tutorFee)
    {
        // Query langsung (bukan relasi) agar tidak membaca status basi dari cache Eloquent.
        $period = FeePeriod::find($tutorFee->fee_period_id);
        if (!$period || $period->isPublished()) {
            return response()->json(['success' => false, 'message' => 'Periode sudah diterbitkan. Batalkan penerbitan terlebih dahulu untuk mengedit fee.'], 422);
        }

        $validated = $request->validate([
            'private_count' => 'required|integer|min:0',
            'regular_count' => 'required|integer|min:0',
            'session_count' => 'required|integer|min:0',
            'day_count'     => 'required|integer|min:0',
            'fee_private'   => 'required|numeric|min:0',
            'fee_regular'   => 'required|numeric|min:0',
            'fee_session'   => 'required|numeric|min:0',
            'fee_transport' => 'required|numeric|min:0',
            'fee_pokok'            => 'required|numeric|min:0',
            'fee_tunjangan'        => 'required|numeric|min:0',
            'extra_session_count'  => 'required|integer|min:0',
            'fee_extra_session'    => 'required|numeric|min:0',
            'fee_insentif'         => 'required|numeric|min:0',
            'total'         => 'required|numeric|min:0',
        ]);

        $tutorFee->update($validated);

        return response()->json(['success' => true, 'message' => 'Fee ' . ($tutorFee->tutor->name ?? 'tutor') . ' berhasil diperbarui.']);
    }

    /** Terbitkan periode agar fee dapat dilihat oleh tutor. */
    public function publish(Request $request)
    {
        $request->validate(['month' => 'required|date_format:Y-m']);
        $month = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();

        $period = FeePeriod::where('month', $month->toDateString())->first();
        if (!$period) {
            return response()->json(['success' => false, 'message' => 'Belum ada data fee untuk bulan ini. Generate terlebih dahulu.'], 422);
        }
        if ($period->tutorFees()->count() === 0) {
            return response()->json(['success' => false, 'message' => 'Tidak ada data fee tutor untuk diterbitkan.'], 422);
        }

        $period->update([
            'status' => 'published',
            'published_at' => now(),
            'published_by' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Fee tutor bulan ' . $month->locale('id')->translatedFormat('F Y') . ' berhasil diterbitkan dan dapat dilihat oleh tutor.']);
    }

    /** Batalkan penerbitan (mis. untuk koreksi), kembali ke draft. */
    public function unpublish(Request $request)
    {
        $request->validate(['month' => 'required|date_format:Y-m']);
        $month = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();

        $period = FeePeriod::where('month', $month->toDateString())->first();
        if (!$period) {
            return response()->json(['success' => false, 'message' => 'Periode tidak ditemukan.'], 422);
        }

        $period->update(['status' => 'draft', 'published_at' => null, 'published_by' => null]);

        return response()->json(['success' => true, 'message' => 'Penerbitan dibatalkan. Periode kembali berstatus draft dan tidak terlihat oleh tutor.']);
    }

    /** Download rekap fee tutor bulan terpilih sebagai Excel (.xlsx). */
    public function exportExcel(Request $request)
    {
        $month = $this->resolveMonth($request);
        $period = FeePeriod::where('month', $month->toDateString())->first();

        $rows = $period
            ? TutorFee::with('tutor')->where('fee_period_id', $period->id)->orderByDesc('total')->get()
            : collect();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Fee Tutor');

        $monthLabel = $month->locale('id')->translatedFormat('F Y');
        $statusLabel = $period
            ? ($period->isPublished() ? 'Sudah Diterbitkan' : 'Draft (belum terbit)')
            : 'Belum Digenerate';

        $sheet->setCellValue('A1', 'Rekap Fee Tutor');
        $sheet->setCellValue('A2', 'Bulan');
        $sheet->setCellValue('B2', $monthLabel);
        $sheet->setCellValue('A3', 'Status');
        $sheet->setCellValue('B3', $statusLabel);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2:A3')->getFont()->setBold(true);

        $headers = [
            'No', 'Tutor', 'Kategori',
            'Sesi Paket Lain (Jumlah)', 'Fee Sesi Paket Lain (Rp)',
            'Sesi Privat (Jumlah)', 'Fee Sesi Privat (Rp)',
            'Total Siswa (Jumlah)', 'Fee Total Siswa (Rp)',
            'Transport (Hari)', 'Fee Transport (Rp)',
            'Gaji Pokok (Rp)', 'Tunjangan (Rp)',
            'Sesi Tambahan (Jumlah)', 'Fee Sesi Tambahan (Rp)',
            'Insentif (Rp)', 'Total Fee (Rp)',
        ];
        $headerRow = 5;
        $sheet->fromArray($headers, null, 'A' . $headerRow);

        $r = $headerRow + 1;
        $no = 1;
        $kategoriLabel = Tutor::KATEGORI_OPTIONS;
        foreach ($rows as $tf) {
            $sheet->fromArray([[
                $no++,
                $tf->tutor->name ?? '-',
                $kategoriLabel[$tf->tutor->kategori ?? 'freelance'] ?? '-',
                $tf->session_count, (float) $tf->fee_session,
                $tf->private_count, (float) $tf->fee_private,
                $tf->regular_count, (float) $tf->fee_regular,
                $tf->day_count, (float) $tf->fee_transport,
                (float) $tf->fee_pokok, (float) $tf->fee_tunjangan,
                $tf->extra_session_count, (float) $tf->fee_extra_session,
                (float) $tf->fee_insentif, (float) $tf->total,
            ]], null, 'A' . $r++);
        }

        if ($rows->isEmpty()) {
            $sheet->setCellValue('A' . $r, 'Belum ada data fee untuk bulan ini.');
            $r++;
        } else {
            // Baris total, sejajar kolom Total Fee — sama seperti tampilan di layar.
            $sheet->setCellValue('P' . $r, 'TOTAL');
            $sheet->setCellValue('Q' . $r, (float) $rows->sum('total'));
            $sheet->getStyle('P' . $r . ':Q' . $r)->getFont()->setBold(true);
            $r++;
        }

        $lastCol = $sheet->getHighestColumn();
        $sheet->getStyle('A' . $headerRow . ':' . $lastCol . $headerRow)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A' . $headerRow . ':' . $lastCol . $headerRow)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2C3E73');
        $sheet->getStyle('A' . $headerRow . ':' . $lastCol . $headerRow)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        for ($c = 1; $c <= Coordinate::columnIndexFromString($lastCol); $c++) {
            $sheet->getColumnDimensionByColumn($c)->setWidth(20);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'rekap-fee-tutor-' . $month->format('Y-m') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function resolveMonth(Request $request): Carbon
    {
        try {
            return $request->filled('month')
                ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
                : now()->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }
}
