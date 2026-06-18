<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class GradeController extends Controller
{
    public function index()
    {
        return view('admin.grades.index');
    }

    public function data()
    {
        return DataTables::of(Grade::latest())
            ->addIndexColumn()
            ->addColumn('action', function ($grade) {
                return '
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-warning btn-edit"
                            data-id="' . $grade->id . '"
                            data-name="' . e($grade->grade_name) . '">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-delete"
                            data-id="' . $grade->id . '"
                            data-name="' . e($grade->grade_name) . '">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate(['grade_name' => 'required|string|max:100|unique:grades,grade_name']);
        Grade::create($request->only('grade_name'));
        return response()->json(['success' => true, 'message' => 'Jenjang berhasil ditambahkan.']);
    }

    public function update(Request $request, Grade $grade)
    {
        $request->validate(['grade_name' => 'required|string|max:100|unique:grades,grade_name,' . $grade->id]);
        $grade->update($request->only('grade_name'));
        return response()->json(['success' => true, 'message' => 'Jenjang berhasil diperbarui.']);
    }

    public function destroy(Grade $grade)
    {
        try {
            $grade->delete();
            return response()->json(['success' => true, 'message' => 'Jenjang berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Tidak dapat dihapus karena masih digunakan.'], 422);
        }
    }
}
