<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tutor;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class TutorController extends Controller
{
    public function index()
    {
        return view('admin.tutors.index');
    }

    public function data()
    {
        return DataTables::of(Tutor::latest())
            ->addIndexColumn()
            ->editColumn('specialization', function ($tutor) {
                $specs = is_array($tutor->specialization) ? $tutor->specialization : [];
                return collect($specs)->map(fn($s) => '<span class="badge bg-primary-subtle text-primary me-1">' . e($s) . '</span>')->implode('');
            })
            ->addColumn('action', function ($tutor) {
                $specs = is_array($tutor->specialization) ? implode(', ', $tutor->specialization) : '';
                return '
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-warning btn-edit"
                            data-id="' . $tutor->id . '"
                            data-name="' . e($tutor->name) . '"
                            data-phone="' . e($tutor->phone) . '"
                            data-specialization="' . e($specs) . '">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-delete"
                            data-id="' . $tutor->id . '"
                            data-name="' . e($tutor->name) . '">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>';
            })
            ->rawColumns(['specialization', 'action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:100',
            'phone'          => 'required|string|max:20',
            'specialization' => 'required|string|max:500',
        ]);

        $validated['specialization'] = array_filter(array_map('trim', explode(',', $validated['specialization'])));

        Tutor::create($validated);
        return response()->json(['success' => true, 'message' => 'Tutor berhasil ditambahkan.']);
    }

    public function update(Request $request, Tutor $tutor)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:100',
            'phone'          => 'required|string|max:20',
            'specialization' => 'required|string|max:500',
        ]);

        $validated['specialization'] = array_filter(array_map('trim', explode(',', $validated['specialization'])));

        $tutor->update($validated);
        return response()->json(['success' => true, 'message' => 'Data tutor berhasil diperbarui.']);
    }

    public function destroy(Tutor $tutor)
    {
        try {
            $tutor->delete();
            return response()->json(['success' => true, 'message' => 'Tutor berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Tidak dapat dihapus karena terkait dengan jadwal aktif.'], 422);
        }
    }
}
