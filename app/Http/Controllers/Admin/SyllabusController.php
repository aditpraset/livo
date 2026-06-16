<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\Syllabus;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yajra\DataTables\Facades\DataTables;

class SyllabusController extends Controller
{
    public function index(Subject $subject)
    {
        return view('admin.subjects.syllabi', compact('subject'));
    }

    public function data(Subject $subject)
    {
        return DataTables::of($subject->syllabi()->latest())
            ->addIndexColumn()
            ->editColumn('sub_pokok_bahasan', function ($syllabus) {
                return $syllabus->sub_pokok_bahasan ?: '<span class="text-muted">—</span>';
            })
            ->addColumn('action', function ($syllabus) {
                return '
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-warning btn-edit"
                            data-id="' . $syllabus->id . '"
                            data-pokok="' . e($syllabus->pokok_bahasan) . '"
                            data-sub="' . e($syllabus->sub_pokok_bahasan) . '"
                            data-kurikulum="' . e($syllabus->jenis_kurikulum) . '"
                            data-kelas="' . e($syllabus->kelas) . '">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-delete"
                            data-id="' . $syllabus->id . '">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>';
            })
            ->rawColumns(['sub_pokok_bahasan', 'action'])
            ->make(true);
    }

    public function store(Request $request, Subject $subject)
    {
        $data = $request->validate([
            'pokok_bahasan'     => 'required|string|max:255',
            'sub_pokok_bahasan' => 'nullable|string|max:10000',
            'jenis_kurikulum'   => 'required|string|max:100',
            'kelas'             => 'required|string|max:50',
        ]);

        $subject->syllabi()->create($data);

        return response()->json(['success' => true, 'message' => 'Silabus berhasil ditambahkan.']);
    }

    public function update(Request $request, Subject $subject, Syllabus $syllabus)
    {
        $data = $request->validate([
            'pokok_bahasan'     => 'required|string|max:255',
            'sub_pokok_bahasan' => 'nullable|string|max:10000',
            'jenis_kurikulum'   => 'required|string|max:100',
            'kelas'             => 'required|string|max:50',
        ]);

        $syllabus->update($data);

        return response()->json(['success' => true, 'message' => 'Silabus berhasil diperbarui.']);
    }

    public function destroy(Subject $subject, Syllabus $syllabus)
    {
        $syllabus->delete();

        return response()->json(['success' => true, 'message' => 'Silabus berhasil dihapus.']);
    }

    /**
     * Unduh template Excel (.xlsx) untuk import silabus.
     */
    public function template(Subject $subject)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Silabus');

        $headers = ['Pokok Bahasan', 'Sub Pokok Bahasan', 'Jenis Kurikulum', 'Kelas'];
        $sheet->fromArray($headers, null, 'A1');

        // Contoh baris agar format jelas
        $sheet->fromArray([
            ['Bilangan Bulat', 'Operasi penjumlahan & pengurangan', 'Kurikulum Merdeka', 'SD Kelas 4'],
            ['Pecahan', 'Mengurutkan pecahan', 'Kurikulum 2013', 'SD Kelas 5'],
        ], null, 'A2');

        // Styling header
        $sheet->getStyle('A1:D1')->getFont()->setBold(true);
        $sheet->getStyle('A1:D1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('2C3E73');
        $sheet->getStyle('A1:D1')->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:D1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setWidth(28);
        }

        $fileName = 'template-silabus-' . str()->slug($subject->subject_name) . '.xlsx';

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import silabus dari file Excel/CSV yang sudah diisi.
     */
    public function import(Request $request, Subject $subject)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120',
        ], [
            'file.mimes' => 'Format file harus .xlsx, .xls, atau .csv.',
            'file.max'   => 'Ukuran file maksimal 5 MB.',
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak dapat dibaca. Pastikan menggunakan template yang disediakan.',
            ], 422);
        }

        // Buang baris header
        array_shift($rows);

        $inserted = 0;
        $skipped  = 0;
        $errors   = [];

        foreach ($rows as $index => $row) {
            $pokok     = trim((string) ($row[0] ?? ''));
            $sub       = trim((string) ($row[1] ?? ''));
            $kurikulum = trim((string) ($row[2] ?? ''));
            $kelas     = trim((string) ($row[3] ?? ''));

            // Lewati baris kosong sepenuhnya
            if ($pokok === '' && $sub === '' && $kurikulum === '' && $kelas === '') {
                continue;
            }

            // Validasi kolom wajib
            if ($pokok === '' || $kurikulum === '' || $kelas === '') {
                $skipped++;
                $errors[] = 'Baris ' . ($index + 2) . ': Pokok Bahasan, Jenis Kurikulum, dan Kelas wajib diisi.';
                continue;
            }

            $subject->syllabi()->create([
                'pokok_bahasan'     => $pokok,
                'sub_pokok_bahasan' => $sub !== '' ? $sub : null,
                'jenis_kurikulum'   => $kurikulum,
                'kelas'             => $kelas,
            ]);
            $inserted++;
        }

        if ($inserted === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data valid yang diimport.',
                'errors'  => $errors,
            ], 422);
        }

        $message = $inserted . ' silabus berhasil diimport.';
        if ($skipped > 0) {
            $message .= ' ' . $skipped . ' baris dilewati.';
        }

        return response()->json([
            'success'  => true,
            'message'  => $message,
            'inserted' => $inserted,
            'skipped'  => $skipped,
            'errors'   => $errors,
        ]);
    }
}
