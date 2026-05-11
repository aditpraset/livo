<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScheduleStudent;
use App\Models\Student;
use Illuminate\Http\Request;

class ScheduleStudentController extends Controller
{
    public function store(Request $request, Student $student)
    {
        $validated = $request->validate([
            'schedule_session_id' => 'required|exists:schedule_sessions,id',
            'date' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        $student->scheduleStudents()->create($validated);

        return redirect()->back()->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function show(ScheduleStudent $scheduleStudent)
    {
        return response()->json($scheduleStudent);
    }

    public function update(Request $request, ScheduleStudent $scheduleStudent)
    {
        $validated = $request->validate([
            'schedule_session_id' => 'required|exists:schedule_sessions,id',
            'date' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        $scheduleStudent->update($validated);

        return redirect()->back()->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(ScheduleStudent $scheduleStudent)
    {
        $scheduleStudent->delete();
        return redirect()->back()->with('success', 'Jadwal berhasil dihapus.');
    }
}
