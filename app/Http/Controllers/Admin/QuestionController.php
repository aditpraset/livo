<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\Subject;
use App\Models\Syllabus;
use Illuminate\Http\Request;
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
