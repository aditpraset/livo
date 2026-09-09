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
            ->addColumn('kategori_label', function ($tutor) {
                $label = Tutor::KATEGORI_OPTIONS[$tutor->kategori] ?? '-';
                $badge = $tutor->kategori === 'tetap' ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary';
                return '<span class="badge ' . $badge . '">' . e($label) . '</span>';
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
                            data-fee="' . e($tutor->fee_per_session !== null ? (0 + $tutor->fee_per_session) : '') . '"
                            data-fee-private="' . e($tutor->fee_per_student_private !== null ? (0 + $tutor->fee_per_student_private) : '') . '"
                            data-fee-student="' . e($tutor->fee_per_student !== null ? (0 + $tutor->fee_per_student) : '') . '"
                            data-fee-transport="' . e($tutor->fee_transport_per_day !== null ? (0 + $tutor->fee_transport_per_day) : '') . '"
                            data-fee-private-khusus="' . e($tutor->fee_private_khusus !== null ? (0 + $tutor->fee_private_khusus) : '') . '"
                            data-fee-tka-regular="' . e($tutor->fee_tka_regular !== null ? (0 + $tutor->fee_tka_regular) : '') . '"
                            data-fee-private-sma="' . e($tutor->fee_private_sma !== null ? (0 + $tutor->fee_private_sma) : '') . '"
                            data-fee-tka-visit="' . e($tutor->fee_tka_visit !== null ? (0 + $tutor->fee_tka_visit) : '') . '"
                            data-kategori="' . e($tutor->kategori ?? 'freelance') . '"
                            data-gaji-pokok="' . e($tutor->gaji_pokok !== null ? (0 + $tutor->gaji_pokok) : '') . '"
                            data-tunjangan="' . e($tutor->tunjangan_per_bulan !== null ? (0 + $tutor->tunjangan_per_bulan) : '') . '"
                            data-maks-sesi-tunjangan="' . e($tutor->maks_sesi_tunjangan_per_bulan ?? '') . '"
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
            ->rawColumns(['photo_thumb', 'specialization', 'kategori_label', 'action'])
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
            'fee_per_session'         => 'nullable|numeric|min:0',
            'fee_per_student_private' => 'nullable|numeric|min:0',
            'fee_per_student'         => 'nullable|numeric|min:0',
            'fee_transport_per_day'   => 'nullable|numeric|min:0',
            'fee_private_khusus'      => 'nullable|numeric|min:0',
            'fee_tka_regular'         => 'nullable|numeric|min:0',
            'fee_private_sma'         => 'nullable|numeric|min:0',
            'fee_tka_visit'           => 'nullable|numeric|min:0',
            'kategori'                      => 'required|in:freelance,tetap',
            'gaji_pokok'                    => 'nullable|numeric|min:0',
            'tunjangan_per_bulan'           => 'nullable|numeric|min:0',
            'maks_sesi_tunjangan_per_bulan' => 'nullable|integer|min:0',
            'photo'            => 'nullable|image|max:5120', // semua tipe foto, maks 5 MB
            'specialization'   => 'required|array|min:1',
            'specialization.*' => 'string|max:100',
        ], [
            'specialization.required' => 'Pilih minimal satu spesialisasi.',
            'specialization.min'      => 'Pilih minimal satu spesialisasi.',
            'kategori.required'       => 'Pilih kategori tutor.',
            'kategori.in'             => 'Kategori tutor tidak valid.',
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
