<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class StudentController extends Controller
{
    public function index()
    {
        return view('admin.students.index');
    }

    public function dataStudents(Request $request)
    {
        $query = Student::latest();
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('full_name', function ($student) {
                return '<div class="fw-semibold">' . $student->full_name . '</div>
                        <small class="text-muted">' . ($student->nickname ?? '') . '</small>';
            })
            ->editColumn('status', function ($student) {
                $badgeClass = $student->status == 1 ? 'bg-success' : 'bg-danger';
                $statusText = $student->status == 1 ? 'Aktif' : 'Non-Aktif';
                return '<span class="badge ' . $badgeClass . '">' . $statusText . '</span>';
            })
            ->editColumn('program', function ($student) {
                return '<span class="badge bg-primary-subtle text-primary">' . ($student->program ?? '-') . '</span>';
            })
            ->addColumn('action', function ($student) {
                return '<div class="btn-group btn-group-sm">
                            <a href="' . route('admin.students.show', $student->id) . '" class="btn btn-outline-primary" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="' . route('admin.students.edit', $student->id) . '" class="btn btn-outline-warning" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" class="btn btn-outline-danger btn-delete" data-id="' . $student->id . '" data-name="' . $student->full_name . '" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>';
            })
            ->rawColumns(['full_name', 'status', 'program', 'action'])
            ->make(true);
    }

    public function show(Student $student)
    {
        $scheduleSessions = \App\Models\ScheduleSession::all();
        $tutors   = \App\Models\Tutor::orderBy('name')->get(['id', 'name']);
        $subjects = \App\Models\Subject::orderBy('subject_name')->get(['id', 'subject_name']);
        $schedules = $student->schedules()
            ->with(['tutor', 'subject', 'evaluation'])
            ->orderBy('class_date', 'desc')
            ->get();
        return view('admin.students.show', compact('student', 'scheduleSessions', 'tutors', 'subjects', 'schedules'));
    }

    public function edit(Student $student)
    {
        return view('admin.students.edit', compact('student'));
    }

    public function update(Request $request, Student $student)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'status' => 'required|in:1,2',
            'nis' => 'nullable|string|max:50',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'whatsapp' => 'nullable|string',
        ]);

        $student->update($request->all());

        return redirect()->route('admin.students.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy(Student $student)
    {
        $student->delete();
        return response()->json(['success' => true, 'message' => 'Data siswa berhasil dihapus.']);
    }
}
