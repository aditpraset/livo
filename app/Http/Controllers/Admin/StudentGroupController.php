<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScheduleSession;
use App\Models\Student;
use App\Models\StudentGroup;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class StudentGroupController extends Controller
{
    public function index()
    {
        $scheduleSessions = ScheduleSession::orderBy('time_start')->get();
        $students = Student::where('status', 1)->orderBy('full_name')->get(['id', 'full_name', 'grade']);
        return view('admin.student_groups.index', compact('scheduleSessions', 'students'));
    }

    public function data(Request $request)
    {
        $query = StudentGroup::with(['session', 'students'])->latest();

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('session_name', fn ($g) => $g->session
                ? e($g->session->name) . ' (' . substr($g->session->time_start, 0, 5) . '–' . substr($g->session->time_end, 0, 5) . ')'
                : '-')
            ->addColumn('students_count', fn ($g) => $g->students->count() . ' siswa')
            ->addColumn('action', fn ($g) => '<div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-warning btn-edit" data-id="' . $g->id . '" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-delete" data-id="' . $g->id . '" data-name="' . e($g->name) . '" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>')
            ->rawColumns(['session_name', 'action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'session_id'   => 'required|exists:schedule_sessions,id',
            'hari'         => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'student_ids'   => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        $group = StudentGroup::create([
            'name'       => $validated['name'],
            'session_id' => $validated['session_id'],
            'hari'       => $validated['hari'],
        ]);
        $group->students()->sync($validated['student_ids'] ?? []);

        return response()->json(['success' => true, 'message' => 'Grouping siswa berhasil ditambahkan.']);
    }

    public function show(StudentGroup $studentGroup)
    {
        $data = $studentGroup->toArray();
        $data['student_ids'] = $studentGroup->students()->pluck('students.id');

        return response()->json($data);
    }

    public function update(Request $request, StudentGroup $studentGroup)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'session_id'   => 'required|exists:schedule_sessions,id',
            'hari'         => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'student_ids'   => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        $studentGroup->update([
            'name'       => $validated['name'],
            'session_id' => $validated['session_id'],
            'hari'       => $validated['hari'],
        ]);
        $studentGroup->students()->sync($validated['student_ids'] ?? []);

        return response()->json(['success' => true, 'message' => 'Grouping siswa berhasil diperbarui.']);
    }

    public function destroy(StudentGroup $studentGroup)
    {
        $studentGroup->delete();
        return response()->json(['success' => true, 'message' => 'Grouping siswa berhasil dihapus.']);
    }
}
