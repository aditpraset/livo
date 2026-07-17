<?php

namespace App\Http\Controllers\Tutor;

use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ScheduleController extends BaseTutorController
{
    /** Jadwal per minggu, dikelompokkan per hari (kelas/ruang & sesi/jam). */
    public function week(Request $request)
    {
        $tutor = $this->tutor();

        $anchor = $request->filled('week')
            ? Carbon::parse($request->week)
            : now();
        $start = $anchor->copy()->startOfWeek(Carbon::MONDAY);
        $end = $anchor->copy()->endOfWeek(Carbon::SUNDAY);

        $schedules = Schedule::with(['student', 'subject', 'evaluation'])
            ->where('tutor_id', $tutor->id)
            ->whereDate('class_date', '>=', $start->toDateString())
            ->whereDate('class_date', '<=', $end->toDateString())
            ->orderBy('class_date')->orderBy('start_time')
            ->get();

        // Kelompokkan per tanggal (Y-m-d) agar mudah dirender per hari Senin–Minggu
        $byDay = $schedules->groupBy(fn ($s) => $s->class_date->toDateString());

        $days = collect(range(0, 6))->map(fn ($i) => $start->copy()->addDays($i));

        // Jumlah sesi = slot mengajar unik (tanggal + jam), bukan jumlah siswa.
        // Beberapa siswa pada slot yang sama dihitung satu sesi.
        $slotKey = fn ($s) => $s->class_date->toDateString() . '|' . $s->start_time . '|' . $s->end_time;
        $totalWeek = $schedules->unique($slotKey)->count();
        $sesiPerDay = $byDay->map(fn ($items) => $items->unique($slotKey)->count());

        return view('tutor.schedules.week', [
            'tutor' => $tutor,
            'days' => $days,
            'byDay' => $byDay,
            'sesiPerDay' => $sesiPerDay,
            'start' => $start,
            'end' => $end,
            'prevWeek' => $start->copy()->subWeek()->toDateString(),
            'nextWeek' => $start->copy()->addWeek()->toDateString(),
            'totalWeek' => $totalWeek,
        ]);
    }

}
