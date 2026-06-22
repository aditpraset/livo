<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Subject;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SubjectController extends Controller
{
    public function index()
    {
        $grades = Grade::orderBy('grade_name')->get(['id', 'grade_name']);
        return view('admin.subjects.index', compact('grades'));
    }

    public function data()
    {
        return DataTables::of(Subject::latest())
            ->addIndexColumn()
            ->addColumn('jenjang', function ($subject) {
                $names = $subject->grade_names;
                if (empty($names)) {
                    return '<span class="text-muted">—</span>';
                }
                return collect($names)
                    ->map(fn($n) => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">' . e($n) . '</span>')
                    ->implode('');
            })
            ->addColumn('action', function ($subject) {
                return '
                    <div class="btn-group btn-group-sm">
                        <a class="btn btn-outline-primary"
                            href="' . route('admin.subjects.syllabi.index', $subject->id) . '"
                            title="Kelola Silabus">
                            <i class="bi bi-journal-text"></i>
                        </a>
                        <button class="btn btn-outline-warning btn-edit"
                            data-id="' . $subject->id . '"
                            data-name="' . e($subject->subject_name) . '"
                            data-grades=\'' . e(json_encode($subject->grade_ids ?? [])) . '\'>
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-delete"
                            data-id="' . $subject->id . '"
                            data-name="' . e($subject->subject_name) . '">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>';
            })
            ->rawColumns(['jenjang', 'action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        Subject::create($data);
        return response()->json(['success' => true, 'message' => 'Mata pelajaran berhasil ditambahkan.']);
    }

    public function update(Request $request, Subject $subject)
    {
        $data = $this->validateData($request, $subject->id);
        $subject->update($data);
        return response()->json(['success' => true, 'message' => 'Mata pelajaran berhasil diperbarui.']);
    }

    /** Validasi & normalisasi input mata pelajaran (termasuk daftar jenjang). */
    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        $request->validate([
            'subject_name' => 'required|string|max:100|unique:subjects,subject_name' . ($ignoreId ? ',' . $ignoreId : ''),
            'grade_ids'    => 'nullable|array',
            'grade_ids.*'  => 'integer|exists:grades,id',
        ]);

        $gradeIds = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('grade_ids', [])))));

        return [
            'subject_name' => $request->subject_name,
            'grade_ids'    => $gradeIds ?: null,
        ];
    }

    public function destroy(Subject $subject)
    {
        try {
            $subject->delete();
            return response()->json(['success' => true, 'message' => 'Mata pelajaran berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Tidak dapat dihapus karena terkait dengan jadwal aktif.'], 422);
        }
    }
}
