<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Syllabus;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yajra\DataTables\Facades\DataTables;

/**
 * Bank Soal — soal pilihan ganda (A–D) per silabus mata pelajaran.
 */
class QuestionController extends Controller
{
    public function index(Subject $subject, Syllabus $syllabus)
    {
        return view('admin.subjects.questions', compact('subject', 'syllabus'));
    }

    public function data(Subject $subject, Syllabus $syllabus)
    {
        return DataTables::of($syllabus->questions()->latest())
            ->addIndexColumn()
            ->editColumn('question', fn($q) => \Illuminate\Support\Str::limit($q->question, 100))
            ->addColumn('correct_answer_label', fn($q) => '<span class="badge bg-success-subtle text-success border border-success-subtle">'
                . strtoupper($q->correct_answer) . '. ' . e(\Illuminate\Support\Str::limit($q->options[$q->correct_answer] ?? '', 40)) . '</span>')
            ->addColumn('action', function ($q) {
                return '
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-warning btn-edit"
                            data-id="' . $q->id . '"
                            data-question="' . e($q->question) . '"
                            data-a="' . e($q->option_a) . '"
                            data-b="' . e($q->option_b) . '"
                            data-c="' . e($q->option_c) . '"
                            data-d="' . e($q->option_d) . '"
                            data-correct="' . e($q->correct_answer) . '">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-delete" data-id="' . $q->id . '">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>';
            })
            ->rawColumns(['correct_answer_label', 'action'])
            ->make(true);
    }

    public function store(Request $request, Subject $subject, Syllabus $syllabus)
    {
        $data = $this->validateData($request);
        $syllabus->questions()->create($data);

        return response()->json(['success' => true, 'message' => 'Soal berhasil ditambahkan.']);
    }

    public function update(Request $request, Subject $subject, Syllabus $syllabus, Question $question)
    {
        $data = $this->validateData($request);
        $question->update($data);

        return response()->json(['success' => true, 'message' => 'Soal berhasil diperbarui.']);
    }

    public function destroy(Subject $subject, Syllabus $syllabus, Question $question)
    {
        $question->delete();

        return response()->json(['success' => true, 'message' => 'Soal berhasil dihapus.']);
    }

    /** Download template Excel kosong (+ contoh baris) untuk import soal via Bank Soal. */
    public function template(Subject $subject, Syllabus $syllabus)
    {
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bank Soal');
        $sheet->fromArray(
            ['Pertanyaan', 'Pilihan A', 'Pilihan B', 'Pilihan C', 'Pilihan D', 'Jawaban Benar (A/B/C/D)'],
            null, 'A1'
        );
        $sheet->fromArray([[
            'Ibu kota Indonesia adalah?', 'Jakarta', 'Bandung', 'Surabaya', 'Medan', 'A',
        ]], null, 'A2');

        $lastCol = $sheet->getHighestColumn();
        $sheet->getStyle('A1:' . $lastCol . '1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:' . $lastCol . '1')->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2C3E73');
        for ($c = 1; $c <= Coordinate::columnIndexFromString($lastCol); $c++) {
            $sheet->getColumnDimensionByColumn($c)->setWidth($c === 1 ? 50 : 24);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'template-bank-soal-' . str()->slug($syllabus->pokok_bahasan) . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** Import soal dari file Excel sesuai template, untuk silabus ini. */
    public function import(Request $request, Subject $subject, Syllabus $syllabus)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:5120',
        ], [
            'file.mimes' => 'Format file harus .xlsx, .xls, atau .csv.',
            'file.max'   => 'Ukuran file maksimal 5 MB.',
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
            $sheet = $spreadsheet->getSheetByName('Bank Soal') ?? $spreadsheet->getSheet(0);
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

        foreach ($rows as $i => $row) {
            $line = $i + 2;
            $get  = fn ($idx) => isset($row[$idx]) ? trim((string) $row[$idx]) : '';

            $question = $get(0);
            $a = $get(1); $b = $get(2); $c = $get(3); $d = $get(4);
            $correct = strtolower($get(5));

            // Lewati baris yang seluruhnya kosong
            if ($question === '' && $a === '' && $b === '' && $c === '' && $d === '' && $correct === '') {
                continue;
            }

            if ($question === '' || $a === '' || $b === '' || $c === '' || $d === '') {
                $skipped++;
                $errors[] = "Baris {$line}: Pertanyaan dan keempat pilihan (A-D) wajib diisi.";
                continue;
            }

            if (!in_array($correct, ['a', 'b', 'c', 'd'], true)) {
                $skipped++;
                $errors[] = "Baris {$line}: Jawaban Benar '{$get(5)}' tidak valid (harus A/B/C/D).";
                continue;
            }

            $syllabus->questions()->create([
                'question'       => $question,
                'option_a'       => $a,
                'option_b'       => $b,
                'option_c'       => $c,
                'option_d'       => $d,
                'correct_answer' => $correct,
            ]);
            $inserted++;
        }

        if ($inserted === 0 && $skipped === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data pada file tsb.',
            ], 422);
        }

        if ($inserted === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada soal valid yang diimport.',
                'errors'  => $errors,
            ], 422);
        }

        $message = "{$inserted} soal berhasil diimport.";
        if ($skipped > 0) {
            $message .= " {$skipped} baris dilewati.";
        }

        return response()->json([
            'success'  => true,
            'message'  => $message,
            'inserted' => $inserted,
            'skipped'  => $skipped,
            'errors'   => $errors,
        ]);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'question'       => 'required|string|max:2000',
            'option_a'       => 'required|string|max:500',
            'option_b'       => 'required|string|max:500',
            'option_c'       => 'required|string|max:500',
            'option_d'       => 'required|string|max:500',
            'correct_answer' => 'required|in:a,b,c,d',
        ], [
            'required' => ':attribute wajib diisi.',
            'in'       => ':attribute wajib dipilih.',
        ], [
            'question'       => 'Pertanyaan',
            'option_a'       => 'Pilihan A',
            'option_b'       => 'Pilihan B',
            'option_c'       => 'Pilihan C',
            'option_d'       => 'Pilihan D',
            'correct_answer' => 'Jawaban benar',
        ]);
    }
}
