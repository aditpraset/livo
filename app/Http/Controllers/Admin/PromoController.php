<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promo;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PromoController extends Controller
{
    public function index()
    {
        return view('admin.promos.index');
    }

    public function data()
    {
        return DataTables::of(Promo::latest())
            ->addIndexColumn()
            ->editColumn('discount_value', function ($p) {
                return $p->discount_type === 'percentage'
                    ? $p->discount_value . '%'
                    : 'Rp ' . number_format($p->discount_value, 0, ',', '.');
            })
            ->editColumn('is_active', function ($p) {
                return $p->is_active
                    ? '<span class="badge bg-success">Aktif</span>'
                    : '<span class="badge bg-secondary">Nonaktif</span>';
            })
            ->editColumn('validity', function ($p) {
                $from  = $p->valid_from  ? $p->valid_from->format('d/m/Y')  : '∞';
                $until = $p->valid_until ? $p->valid_until->format('d/m/Y') : '∞';
                return $from . ' – ' . $until;
            })
            ->addColumn('action', function ($p) {
                return '
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-warning btn-edit"
                            data-id="' . $p->id . '"
                            data-name="' . e($p->name) . '"
                            data-code="' . e($p->code) . '"
                            data-type="' . $p->discount_type . '"
                            data-value="' . $p->discount_value . '"
                            data-min="' . ($p->min_package_price ?? '') . '"
                            data-active="' . ($p->is_active ? 1 : 0) . '"
                            data-from="' . ($p->valid_from?->format('Y-m-d') ?? '') . '"
                            data-until="' . ($p->valid_until?->format('Y-m-d') ?? '') . '"
                            data-desc="' . e($p->description ?? '') . '">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-delete"
                            data-id="' . $p->id . '"
                            data-name="' . e($p->name) . '">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>';
            })
            ->rawColumns(['is_active', 'action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:100',
            'code'              => 'required|string|max:50|unique:promos,code',
            'discount_type'     => 'required|in:percentage,amount',
            'discount_value'    => 'required|numeric|min:0',
            'min_package_price' => 'nullable|numeric|min:0',
            'is_active'         => 'boolean',
            'valid_from'        => 'nullable|date',
            'valid_until'       => 'nullable|date|after_or_equal:valid_from',
            'description'       => 'nullable|string|max:500',
        ]);

        $validated['code']      = strtoupper($validated['code']);
        $validated['is_active'] = $request->boolean('is_active', true);

        Promo::create($validated);
        return response()->json(['success' => true, 'message' => 'Promo berhasil ditambahkan.']);
    }

    public function update(Request $request, Promo $promo)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:100',
            'code'              => 'required|string|max:50|unique:promos,code,' . $promo->id,
            'discount_type'     => 'required|in:percentage,amount',
            'discount_value'    => 'required|numeric|min:0',
            'min_package_price' => 'nullable|numeric|min:0',
            'is_active'         => 'boolean',
            'valid_from'        => 'nullable|date',
            'valid_until'       => 'nullable|date|after_or_equal:valid_from',
            'description'       => 'nullable|string|max:500',
        ]);

        $validated['code']      = strtoupper($validated['code']);
        $validated['is_active'] = $request->boolean('is_active');

        $promo->update($validated);
        return response()->json(['success' => true, 'message' => 'Promo berhasil diperbarui.']);
    }

    public function destroy(Promo $promo)
    {
        $promo->delete();
        return response()->json(['success' => true, 'message' => 'Promo berhasil dihapus.']);
    }
}
