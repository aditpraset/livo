<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScheduleSession;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ScheduleSessionController extends Controller
{
    public function index()
    {
        return view('admin.schedule_sessions.index');
    }

    public function data(Request $request)
    {
        $query = ScheduleSession::latest();
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('time_start', function ($session) {
                return date('H:i', strtotime($session->time_start));
            })
            ->editColumn('time_end', function ($session) {
                return date('H:i', strtotime($session->time_end));
            })
            ->addColumn('action', function ($session) {
                return '<div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-warning btn-edit" data-id="' . $session->id . '" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-delete" data-id="' . $session->id . '" data-name="' . $session->name . '" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'time_start' => 'required',
            'time_end' => 'required',
            'notes' => 'nullable|string',
        ]);

        ScheduleSession::create($request->all());

        return response()->json(['success' => true, 'message' => 'Sesi pembelajaran berhasil ditambahkan.']);
    }

    public function show(ScheduleSession $scheduleSession)
    {
        return response()->json($scheduleSession);
    }

    public function update(Request $request, ScheduleSession $scheduleSession)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'time_start' => 'required',
            'time_end' => 'required',
            'notes' => 'nullable|string',
        ]);

        $scheduleSession->update($request->all());

        return response()->json(['success' => true, 'message' => 'Sesi pembelajaran berhasil diperbarui.']);
    }

    public function destroy(ScheduleSession $scheduleSession)
    {
        $scheduleSession->delete();
        return response()->json(['success' => true, 'message' => 'Sesi pembelajaran berhasil dihapus.']);
    }
}
