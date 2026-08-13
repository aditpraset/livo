<?php

namespace App\Http\Controllers\Siswa;

use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ScheduleController extends BaseSiswaController
{
    /** Jadwal belajar siswa per minggu, dikelompokkan per hari. */
    public function week(Request $request)
    {
        $student = $this->student();

        $anchor = $request->filled('week') ? Carbon::parse($request->week) : now();
        $start = $anchor->copy()->startOfWeek(Carbon::MONDAY);
        $end = $anchor->copy()->endOfWeek(Carbon::SUNDAY);

        $schedules = Schedule::with(['tutor', 'subject', 'evaluation'])
            ->where('student_id', $student->id)
            ->whereDate('class_date', '>=', $start->toDateString())
            ->whereDate('class_date', '<=', $end->toDateString())
            ->orderBy('class_date')->orderBy('start_time')
            ->get();

        $byDay = $schedules->groupBy(fn ($s) => $s->class_date->toDateString());
        $days = collect(range(0, 6))->map(fn ($i) => $start->copy()->addDays($i));

        return view('siswa.schedules.week', [
            'student' => $student,
            'days' => $days,
            'byDay' => $byDay,
            'start' => $start,
            'end' => $end,
            'prevWeek' => $start->copy()->subWeek()->toDateString(),
            'nextWeek' => $start->copy()->addWeek()->toDateString(),
            'totalWeek' => $schedules->count(),
        ]);
    }
}
