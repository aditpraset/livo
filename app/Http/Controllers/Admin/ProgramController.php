<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ProgramController extends Controller
{
    public function index()
    {
        return view('admin.programs.index');
    }

    public function data()
    {
        return DataTables::of(Program::latest())
            ->addIndexColumn()
            ->editColumn('duration', fn($program) => $program->duration . 'x per minggu')
            ->addColumn('action', function ($program) {
                return '
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-warning btn-edit"
                            data-id="' . $program->id . '"
                            data-name="' . e($program->program_name) . '"
                            data-kuota="' . $program->kuota . '"
                            data-duration="' . $program->duration . '">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-delete"
                            data-id="' . $program->id . '"
                            data-name="' . e($program->program_name) . '">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'program_name' => 'required|string|max:100|unique:programs,program_name',
            'kuota' => 'required|integer|min:0',
            'duration' => 'required|integer|min:0',
        ]);
        Program::create($request->only('program_name', 'kuota', 'duration'));
        return response()->json(['success' => true, 'message' => 'Program berhasil ditambahkan.']);
    }

    public function update(Request $request, Program $program)
    {
        $request->validate([
            'program_name' => 'required|string|max:100|unique:programs,program_name,' . $program->id,
            'kuota' => 'required|integer|min:0',
            'duration' => 'required|integer|min:0',
        ]);
        $program->update($request->only('program_name', 'kuota', 'duration'));
        return response()->json(['success' => true, 'message' => 'Program berhasil diperbarui.']);
    }

    public function destroy(Program $program)
    {
        try {
            $program->delete();
            return response()->json(['success' => true, 'message' => 'Program berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Tidak dapat dihapus karena masih digunakan.'], 422);
        }
    }
}
