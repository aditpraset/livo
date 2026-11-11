<?php

namespace App\Http\Controllers\Tutor;

use App\Models\Schedule;
use App\Models\ScheduleSession;
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

        // Jumlah sesi = slot mengajar unik (jam), bukan jumlah siswa.
        // Beberapa siswa pada slot jam yang sama dikelompokkan jadi satu sesi
        // (siswa-siswanya dilihat lewat modal, bukan baris terpisah per siswa).
        $slotKey = fn ($s) => $s->start_time . '|' . $s->end_time;

        // Peta "jam_mulai-jam_selesai" => id ScheduleSession, dipakai utk menentukan warna
        // label jam yang tetap & identik per sesi (sama seperti tab Grup Sesi di admin).
        $sessionsByRange = ScheduleSession::get()->mapWithKeys(fn ($s) => [
            substr($s->time_start, 0, 5) . '-' . substr($s->time_end, 0, 5) => $s->id,
        ]);

        $sessionsByDay = $byDay->map(fn ($items) => $items->groupBy($slotKey)
            ->map(function ($group) use ($sessionsByRange) {
                $first = $group->first();
                $range = substr($first->start_time, 0, 5) . '-' . substr($first->end_time, 0, 5);
                return [
                    'start_time' => $first->start_time,
                    'end_time'   => $first->end_time,
                    'session_id' => $sessionsByRange->get($range),
                    'room'       => $group->pluck('room')->filter()->unique()->values()->implode(', ') ?: '-',
                    'subject'    => $group->pluck('subject.subject_name')->filter()->unique()->values()->implode(', ') ?: '-',
                    'students'   => $group->map(fn ($s) => [
                        'id'                => $s->student_id,
                        'name'              => $s->student->full_name ?? '-',
                        'grade'             => $s->student->grade ?? '',
                        'subject'           => $s->subject->subject_name ?? '-',
                        'status_schedule'   => $s->status_schedule,
                        'pending_eval'      => $s->status_schedule === 'done' && !$s->evaluation,
                    ])->values(),
                    'count'      => $group->count(),
                    'done'       => $group->where('status_schedule', 'done')->count(),
                    'scheduled'  => $group->where('status_schedule', 'scheduled')->count(),
                    'canceled'   => $group->where('status_schedule', 'canceled')->count(),
                ];
            })
            ->sortBy('start_time')->values());

        $totalWeek = $sessionsByDay->sum->count();
        $sesiPerDay = $sessionsByDay->map->count();

        // Warna label jam tetap & identik per sesi (mis. Sesi 1 selalu biru, Sesi 2 selalu
        // kuning, dst) — urutan warna mengikuti urutan jam mulai sesi, sama seperti tab
        // Grup Sesi di admin, supaya konsisten di seluruh tampilan jadwal.
        $sesiWarnaPalet = ['primary', 'warning', 'success', 'info', 'danger', 'dark'];
        $sessionColorMap = ScheduleSession::orderBy('time_start')->get()->values()
            ->mapWithKeys(fn ($s, $i) => [$s->id => $sesiWarnaPalet[$i % count($sesiWarnaPalet)]]);

        return view('tutor.schedules.week', [
            'tutor' => $tutor,
            'days' => $days,
            'sessionsByDay' => $sessionsByDay,
            'sesiPerDay' => $sesiPerDay,
            'sessionColorMap' => $sessionColorMap,
            'start' => $start,
            'end' => $end,
            'prevWeek' => $start->copy()->subWeek()->toDateString(),
            'nextWeek' => $start->copy()->addWeek()->toDateString(),
            'totalWeek' => $totalWeek,
        ]);
    }

}
