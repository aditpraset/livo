<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\Tutor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class TutorController extends Controller
{
    public function index()
    {
        $subjects = Subject::orderBy('subject_name')->get(['id', 'subject_name']);
        return view('admin.tutors.index', compact('subjects'));
    }

    public function data()
    {
        return DataTables::of(Tutor::latest())
            ->addIndexColumn()
            ->addColumn('photo_thumb', function ($tutor) {
                if ($tutor->photo) {
                    return '<img src="' . e(asset('storage/' . $tutor->photo)) . '" class="rounded-circle" style="width:38px;height:38px;object-fit:cover;">';
                }
                return '<span class="rounded-circle bg-secondary-subtle text-secondary d-inline-flex align-items-center justify-content-center" style="width:38px;height:38px;"><i class="bi bi-person"></i></span>';
            })
            ->editColumn('specialization', function ($tutor) {
                $specs = is_array($tutor->specialization) ? $tutor->specialization : [];
                return collect($specs)->map(fn($s) => '<span class="badge bg-primary-subtle text-primary me-1">' . e($s) . '</span>')->implode('');
            })
            ->addColumn('action', function ($tutor) {
                $specs = is_array($tutor->specialization) ? $tutor->specialization : [];
                return '
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-warning btn-edit"
                            data-id="' . $tutor->id . '"
                            data-name="' . e($tutor->name) . '"
                            data-phone="' . e($tutor->phone) . '"
                            data-email="' . e($tutor->email ?? '') . '"
                            data-norek="' . e($tutor->no_rekening ?? '') . '"
                            data-photo="' . e($tutor->photo ? asset('storage/' . $tutor->photo) : '') . '"
                            data-specialization=\'' . e(json_encode($specs)) . '\'>
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-delete"
                            data-id="' . $tutor->id . '"
                            data-name="' . e($tutor->name) . '">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>';
            })
            ->rawColumns(['photo_thumb', 'specialization', 'action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);
        $validated['specialization'] = $request->input('specialization', []);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('tutors', 'public');
        }

        Tutor::create($validated);
        return response()->json(['success' => true, 'message' => 'Tutor berhasil ditambahkan.']);
    }

    public function update(Request $request, Tutor $tutor)
    {
        $validated = $this->validateData($request);
        $validated['specialization'] = $request->input('specialization', []);

        if ($request->hasFile('photo')) {
            if ($tutor->photo) {
                Storage::disk('public')->delete($tutor->photo);
            }
            $validated['photo'] = $request->file('photo')->store('tutors', 'public');
        }

        $tutor->update($validated);
        return response()->json(['success' => true, 'message' => 'Data tutor berhasil diperbarui.']);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name'             => 'required|string|max:100',
            'phone'            => 'required|string|max:20',
            'email'            => 'nullable|email|max:255',
            'no_rekening'      => 'nullable|string|max:50',
            'photo'            => 'nullable|image|max:5120', // semua tipe foto, maks 5 MB
            'specialization'   => 'required|array|min:1',
            'specialization.*' => 'string|max:100',
        ], [
            'specialization.required' => 'Pilih minimal satu spesialisasi.',
            'specialization.min'      => 'Pilih minimal satu spesialisasi.',
        ]);
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
