<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;


use Illuminate\Http\Request;
use App\Models\StudentRegistration;
use Carbon\Carbon;

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
        $recentRegistrations = StudentRegistration::latest()->take(10)->get();

        return view('admin.index', compact(
            'totalRegistrations',
            'mathCount',
            'englishCount',
            'monthlyRegistrations',
            'recentRegistrations'
        ));
    }

    public function registrations()
    {
        $registrations = StudentRegistration::latest()->paginate(20);
        return view('admin.registrations', compact('registrations'));
    }
}
