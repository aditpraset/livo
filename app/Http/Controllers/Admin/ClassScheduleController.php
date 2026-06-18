<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\Program;
use App\Models\ScheduleSession;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ClassScheduleController extends Controller
{
    public const HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    public const KELAS = [
        'TK',
        'SD Kelas 1', 'SD Kelas 2', 'SD Kelas 3', 'SD Kelas 4', 'SD Kelas 5', 'SD Kelas 6',
        'SMP Kelas 7', 'SMP Kelas 8', 'SMP Kelas 9',
        'SMA Kelas 10', 'SMA Kelas 11', 'SMA Kelas 12',
    ];

    public function index()
    {
        return view('admin.class_schedules.index', [
            'sessions'  => ScheduleSession::orderBy('time_start')->get(['id', 'name', 'time_start', 'time_end']),
            'programs'  => Program::orderBy('program_name')->get(['id', 'program_name']),
            'hariList'  => self::HARI,
            'kelasList' => self::KELAS,
        ]);
    }

    public function data()
    {
        $query = ClassSchedule::with(['session', 'program'])->latest();

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('session_name', fn($c) => $c->session?->name ?? '-')
            ->addColumn('program_name', fn($c) => $c->program?->program_name ?? '-')
            ->addColumn('action', function ($c) {
                return '
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-warning btn-edit"
                            data-id="' . $c->id . '"
                            data-session="' . $c->session_id . '"
                            data-program="' . $c->program_id . '"
                            data-hari="' . e($c->hari) . '"
                            data-kelas="' . e($c->kelas) . '">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-delete"
                            data-id="' . $c->id . '"
                            data-kelas="' . e($c->kelas) . '">
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
        ClassSchedule::create($validated);
        return response()->json(['success' => true, 'message' => 'Jadwal berhasil ditambahkan.']);
    }

    public function update(Request $request, ClassSchedule $classSchedule)
    {
        $validated = $this->validateData($request);
        $classSchedule->update($validated);
        return response()->json(['success' => true, 'message' => 'Jadwal berhasil diperbarui.']);
    }

    public function destroy(ClassSchedule $classSchedule)
    {
        try {
            $classSchedule->delete();
            return response()->json(['success' => true, 'message' => 'Jadwal berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Tidak dapat dihapus.'], 422);
        }
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'session_id' => 'required|exists:schedule_sessions,id',
            'program_id' => 'required|exists:programs,id',
            'hari'       => 'required|string|in:' . implode(',', self::HARI),
            'kelas'      => 'required|string|in:' . implode(',', self::KELAS),
        ], [
            'hari.required' => 'Pilih hari.',
            'hari.in'       => 'Hari tidak valid.',
        ]);
    }
}
