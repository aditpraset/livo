<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SubjectController extends Controller
{
    public function index()
    {
        return view('admin.subjects.index');
    }

    public function data()
    {
        return DataTables::of(Subject::latest())
            ->addIndexColumn()
            ->addColumn('action', function ($subject) {
                return '
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-warning btn-edit"
                            data-id="' . $subject->id . '"
                            data-name="' . e($subject->subject_name) . '">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-delete"
                            data-id="' . $subject->id . '"
                            data-name="' . e($subject->subject_name) . '">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate(['subject_name' => 'required|string|max:100|unique:subjects,subject_name']);
        Subject::create($request->only('subject_name'));
        return response()->json(['success' => true, 'message' => 'Mata pelajaran berhasil ditambahkan.']);
    }

    public function update(Request $request, Subject $subject)
    {
        $request->validate(['subject_name' => 'required|string|max:100|unique:subjects,subject_name,' . $subject->id]);
        $subject->update($request->only('subject_name'));
        return response()->json(['success' => true, 'message' => 'Mata pelajaran berhasil diperbarui.']);
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
