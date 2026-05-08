<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;


use Illuminate\Http\Request;
use App\Models\StudentRegistration;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class AdminController extends Controller
{
    public function dashboard()
    {
        $totalRegistrations = StudentRegistration::count();
        $mathCount = StudentRegistration::where('program', 'like', '%Matematika%')->count();
        $englishCount = StudentRegistration::where('program', 'like', '%Inggris%')->count();
        $monthlyRegistrations = StudentRegistration::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();
        
        return view('admin.index', compact(
            'totalRegistrations',
            'mathCount',
            'englishCount',
            'monthlyRegistrations'
        ));
    }

    public function registrations()
    {
        return view('admin.registrations');
    }

    public function dataDashboard(Request $request)
    {
        $query = StudentRegistration::latest()->take(10);
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('full_name', function($reg) {
                return '<div class="d-flex align-items-center">
                    <span class="avatar avatar-sm me-3 rounded-circle bg-primary-subtle text-primary fw-bold">'.substr($reg->full_name, 0, 1).'</span>
                    <div class="flex-fill">
                        <div class="fw-bold text-dark">'.$reg->full_name.'</div>
                        <div class="text-muted small">'.($reg->nickname ?? '-').'</div>
                    </div>
                </div>';
            })
            ->editColumn('created_at', function($reg) {
                return '<div class="text-muted small">'.$reg->created_at->format('d M Y').'</div>
                        <div class="text-muted extra-small" style="font-size: 0.7rem;">'.$reg->created_at->format('H:i').'</div>';
            })
            ->editColumn('program', function($reg) {
                $badgeClass = match($reg->program) {
                    'Matematika' => 'bg-success-subtle text-success',
                    'B. Inggris' => 'bg-info-subtle text-info',
                    default => 'bg-primary-subtle text-primary'
                };
                return '<span class="badge '.$badgeClass.' border-0 px-2 py-1">'.($reg->program ?? '-').'</span>';
            })
            ->rawColumns(['full_name', 'created_at', 'program'])
            ->make(true);
    }

    public function dataRegistrations(Request $request)
    {
        $query = StudentRegistration::latest();
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('full_name', function($reg) {
                return '<div class="fw-semibold">'.$reg->full_name.'</div>
                        <small class="text-muted">'.($reg->nickname ?? '').'</small>';
            })
            ->editColumn('program', function($reg) {
                return '<span class="badge bg-primary-subtle text-primary">'.($reg->program ?? '-').'</span>';
            })
            ->editColumn('created_at', function($reg) {
                return '<small>'.$reg->created_at->format('d M Y').'</small>';
            })
            ->addColumn('action', function($reg) {
                return '<button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#detailModal'.$reg->id.'">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-eye" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0" /><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6" /></svg>
                        </button>';
            })
            ->rawColumns(['full_name', 'program', 'created_at', 'action'])
            ->make(true);
    }

    public function showRegistration(StudentRegistration $registration)
    {
        return response()->json($registration);
    }
}
