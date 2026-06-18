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
            ->addColumn('action', function ($p) {
                return '
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-warning btn-edit"
                            data-id="' . $p->id . '"
                            data-name="' . e($p->package_name) . '"
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
            'package_name' => 'required|string|max:100',
            'description'  => 'nullable|string|max:500',
        ]);

        // Harga & jumlah sesi disembunyikan dari form; isi nilai default.
        $validated['price'] = 0;
        $validated['total_sessions'] = 0;

        Package::create($validated);
        return response()->json(['success' => true, 'message' => 'Paket berhasil ditambahkan.']);
    }

    public function update(Request $request, Package $package)
    {
        $validated = $request->validate([
            'package_name' => 'required|string|max:100',
            'description'  => 'nullable|string|max:500',
        ]);

        // Harga & jumlah sesi tidak diubah dari form (disembunyikan).
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
