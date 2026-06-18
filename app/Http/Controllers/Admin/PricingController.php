<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Package;
use App\Models\Pricing;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class PricingController extends Controller
{
    public function index()
    {
        return view('admin.pricings.index', [
            'packages' => Package::orderBy('package_name')->get(['id', 'package_name']),
            'programs' => Program::orderBy('program_name')->get(['id', 'program_name']),
            'grades'   => Grade::orderBy('grade_name')->get(['id', 'grade_name']),
        ]);
    }

    public function data()
    {
        $query = Pricing::with(['package', 'program', 'grade'])->latest();

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('package_name', fn($p) => $p->package?->package_name ?? '-')
            ->addColumn('program_name', fn($p) => $p->program?->program_name ?? '-')
            ->addColumn('grade_name', fn($p) => $p->grade?->grade_name ?? '-')
            ->editColumn('duration', fn($p) => $p->duration . ' bulan')
            ->editColumn('price', fn($p) => 'Rp ' . number_format($p->price, 0, ',', '.'))
            ->addColumn('action', function ($p) {
                return '
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-warning btn-edit"
                            data-id="' . $p->id . '"
                            data-package="' . $p->package_id . '"
                            data-program="' . $p->program_id . '"
                            data-grade="' . $p->grade_id . '"
                            data-duration="' . $p->duration . '"
                            data-price="' . $p->price . '">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-delete"
                            data-id="' . $p->id . '">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);
        Pricing::create($validated);
        return response()->json(['success' => true, 'message' => 'Harga berhasil ditambahkan.']);
    }

    public function update(Request $request, Pricing $pricing)
    {
        $validated = $this->validateData($request, $pricing->id);
        $pricing->update($validated);
        return response()->json(['success' => true, 'message' => 'Harga berhasil diperbarui.']);
    }

    public function destroy(Pricing $pricing)
    {
        try {
            $pricing->delete();
            return response()->json(['success' => true, 'message' => 'Harga berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Tidak dapat dihapus.'], 422);
        }
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        $unique = Rule::unique('pricings')
            ->where(fn($q) => $q
                ->where('package_id', $request->package_id)
                ->where('program_id', $request->program_id)
                ->where('grade_id', $request->grade_id)
                ->where('duration', $request->duration));

        if ($ignoreId) {
            $unique->ignore($ignoreId);
        }

        return $request->validate([
            'package_id' => 'required|exists:packages,id',
            'program_id' => 'required|exists:programs,id',
            'grade_id'   => 'required|exists:grades,id',
            'duration'   => ['required', 'integer', 'min:1', $unique],
            'price'      => 'required|numeric|min:0',
        ], [
            'duration.unique' => 'Kombinasi paket, program, jenjang, dan durasi ini sudah memiliki harga.',
        ]);
    }
}
