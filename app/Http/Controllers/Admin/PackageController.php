<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PackageController extends Controller
{
    public function index()
    {
        return view('admin.packages.index');
    }

    public function data()
    {
        return DataTables::of(Package::latest())
            ->addIndexColumn()
            ->editColumn('price', fn($p) => 'Rp ' . number_format($p->price, 0, ',', '.'))
            ->editColumn('total_sessions', fn($p) => $p->total_sessions . ' sesi')
            ->addColumn('action', function ($p) {
                return '
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-warning btn-edit"
                            data-id="' . $p->id . '"
                            data-name="' . e($p->package_name) . '"
                            data-price="' . $p->price . '"
                            data-sessions="' . $p->total_sessions . '"
                            data-desc="' . e($p->description ?? '') . '">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-delete"
                            data-id="' . $p->id . '"
                            data-name="' . e($p->package_name) . '">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'package_name'   => 'required|string|max:100',
            'price'          => 'required|numeric|min:0',
            'total_sessions' => 'required|integer|min:1',
            'description'    => 'nullable|string|max:500',
        ]);

        Package::create($validated);
        return response()->json(['success' => true, 'message' => 'Paket berhasil ditambahkan.']);
    }

    public function update(Request $request, Package $package)
    {
        $validated = $request->validate([
            'package_name'   => 'required|string|max:100',
            'price'          => 'required|numeric|min:0',
            'total_sessions' => 'required|integer|min:1',
            'description'    => 'nullable|string|max:500',
        ]);

        $package->update($validated);
        return response()->json(['success' => true, 'message' => 'Paket berhasil diperbarui.']);
    }

    public function destroy(Package $package)
    {
        try {
            $package->delete();
            return response()->json(['success' => true, 'message' => 'Paket berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Tidak dapat dihapus karena terkait data pendaftaran.'], 422);
        }
    }
}
