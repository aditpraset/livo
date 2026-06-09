<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\ScheduleSession;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Tutor;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ScheduleController extends Controller
{
    public function index()
    {
        $students         = Student::orderBy('full_name')->get(['id', 'full_name', 'schedule_session_id']);
        $tutors           = Tutor::orderBy('name')->get(['id', 'name']);
        $subjects         = Subject::orderBy('subject_name')->get(['id', 'subject_name']);
        $scheduleSessions = ScheduleSession::orderBy('time_start')->get();

        return view('admin.schedules.index', compact('students', 'tutors', 'subjects', 'scheduleSessions'));
    }

    public function data()
    {
        $query = Schedule::with(['student', 'tutor', 'subject', 'evaluation'])->latest('class_date');

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('student_name', fn($s) => e($s->student->full_name ?? '-'))
            ->editColumn('tutor_name',   fn($s) => e($s->tutor->name ?? '-'))
            ->editColumn('subject_name', fn($s) => e($s->subject->subject_name ?? '-'))
            ->editColumn('class_date', fn($s) => \Carbon\Carbon::parse($s->class_date)->translatedFormat('d M Y'))
            ->editColumn('time', fn($s) => substr($s->start_time, 0, 5) . ' – ' . substr($s->end_time, 0, 5))
            ->editColumn('status_schedule', function ($s) {
                return match ($s->status_schedule) {
                    'scheduled' => '<span class="badge bg-primary">Dijadwalkan</span>',
                    'done'      => '<span class="badge bg-success">Selesai</span>',
                    'canceled'  => '<span class="badge bg-secondary">Dibatalkan</span>',
                };
            })
            ->editColumn('evaluation_status', function ($s) {
                if ($s->status_schedule !== 'done') return '-';
                return $s->evaluation
                    ? '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Sudah</span>'
                    : '<span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-clock me-1"></i>Belum</span>';
            })
            ->addColumn('action', function ($s) {
                $btn = '<div class="btn-group btn-group-sm">';

                if ($s->status_schedule === 'scheduled') {
                    $btn .= '<button class="btn btn-outline-success btn-done" data-id="' . $s->id . '" title="Tandai Selesai"><i class="bi bi-check-lg"></i></button>';
                    $btn .= '<button class="btn btn-outline-warning btn-edit" data-id="' . $s->id . '" title="Edit"><i class="bi bi-pencil"></i></button>';
                    $btn .= '<button class="btn btn-outline-secondary btn-cancel" data-id="' . $s->id . '" title="Batalkan"><i class="bi bi-x-circle"></i></button>';
                }

                if ($s->status_schedule === 'done') {
                    $evalTitle = $s->evaluation ? 'Edit Evaluasi' : 'Isi Evaluasi';
                    $btn .= '<button class="btn btn-outline-info btn-evaluate" data-id="' . $s->id . '" title="' . $evalTitle . '"><i class="bi bi-clipboard2-check"></i></button>';
                }

                $btn .= '<button class="btn btn-outline-danger btn-delete" data-id="' . $s->id . '" title="Hapus"><i class="bi bi-trash"></i></button>';
                $btn .= '</div>';
                return $btn;
            })
            ->rawColumns(['status_schedule', 'evaluation_status', 'action'])
            ->make(true);
    }

    public function events(Request $request)
    {
        $events = Schedule::with(['student', 'tutor', 'subject'])
            ->when($request->start, fn($q) => $q->where('class_date', '>=', substr($request->start, 0, 10)))
            ->when($request->end,   fn($q) => $q->where('class_date', '<=', substr($request->end,   0, 10)))
            ->get()
            ->map(function ($s) {
                $color = match ($s->status_schedule) {
                    'scheduled' => '#4299e1',
                    'done'      => '#2fb344',
                    'canceled'  => '#9ca3af',
                };
                return [
                    'id'    => $s->id,
                    'title' => ($s->student->full_name ?? '?') . ' – ' . ($s->subject->subject_name ?? '?'),
                    'start' => $s->class_date->format('Y-m-d') . 'T' . $s->start_time,
                    'end'   => $s->class_date->format('Y-m-d') . 'T' . $s->end_time,
                    'color' => $color,
                    'extendedProps' => [
                        'student' => $s->student->full_name ?? '-',
                        'tutor'   => $s->tutor->name ?? '-',
                        'subject' => $s->subject->subject_name ?? '-',
                        'status'  => $s->status_schedule,
                    ],
                ];
            });

        return response()->json($events);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'tutor_id'   => 'required|exists:tutors,id',
            'subject_id' => 'required|exists:subjects,id',
            'class_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|after:start_time',
        ]);

        if ($this->hasConflict($validated['tutor_id'], $validated['class_date'], $validated['start_time'], $validated['end_time'])) {
            return response()->json(['success' => false, 'message' => 'Tutor ini sudah memiliki jadwal yang bentrok pada waktu tersebut.'], 422);
        }

        Schedule::create($validated + ['status_schedule' => 'scheduled']);
        return response()->json(['success' => true, 'message' => 'Jadwal berhasil ditambahkan.']);
    }

    public function show(Schedule $schedule)
    {
        return response()->json($schedule->load(['student', 'tutor', 'subject', 'evaluation']));
    }

    public function update(Request $request, Schedule $schedule)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'tutor_id'   => 'required|exists:tutors,id',
            'subject_id' => 'required|exists:subjects,id',
            'class_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|after:start_time',
        ]);

        if ($this->hasConflict($validated['tutor_id'], $validated['class_date'], $validated['start_time'], $validated['end_time'], $schedule->id)) {
            return response()->json(['success' => false, 'message' => 'Tutor ini sudah memiliki jadwal yang bentrok pada waktu tersebut.'], 422);
        }

        $schedule->update($validated);
        return response()->json(['success' => true, 'message' => 'Jadwal berhasil diperbarui.']);
    }

    public function updateStatus(Request $request, Schedule $schedule)
    {
        $request->validate(['status' => 'required|in:done,canceled']);

        $schedule->update(['status_schedule' => $request->status]);

        // Kurangi kuota sesi siswa ketika sesi ditandai selesai
        if ($request->status === 'done') {
            $schedule->student()->decrement('quota_sessions');
        }

        return response()->json(['success' => true, 'message' => 'Status jadwal berhasil diperbarui.']);
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();
        return response()->json(['success' => true, 'message' => 'Jadwal berhasil dihapus.']);
    }

    private function hasConflict(int $tutorId, string $date, string $start, string $end, ?int $excludeId = null): bool
    {
        return Schedule::where('tutor_id', $tutorId)
            ->where('class_date', $date)
            ->where('status_schedule', '!=', 'canceled')
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->exists();
    }
}
