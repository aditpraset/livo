<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScheduleSession;
use App\Models\SchedulePattern;
use App\Models\SchedulePatternItem;
use App\Models\StudentGroup;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Pola jadwal mingguan (admin): template hari + sesi + mapel + group siswa
 * yang nanti disiapkan ulang tiap minggu lewat halaman Siapkan Jadwal.
 */
class SchedulePatternController extends Controller
{
    public function index(Request $request)
    {
        $patterns = SchedulePattern::orderByDesc('is_active')->orderBy('name')->get();

        $pattern = $request->filled('pattern')
            ? $patterns->firstWhere('id', (int) $request->input('pattern'))
            : ($patterns->firstWhere('is_active', true) ?? $patterns->first());

        $items = $pattern
            ? $pattern->items()->with(['session', 'subject', 'studentGroup'])->get()
            : collect();

        // Matriks tampilan: baris = sesi, kolom = hari.
        $matrix = $items->groupBy('session_id')->map(fn ($rows) => $rows->groupBy('hari'));

        return view('admin.schedule_patterns.index', [
            'patterns' => $patterns,
            'pattern'  => $pattern,
            'items'    => $items,
            'matrix'   => $matrix,
            'sessions' => ScheduleSession::orderBy('time_start')->get(),
            'subjects' => Subject::orderBy('subject_name')->get(['id', 'subject_name']),
            'groups'   => StudentGroup::with('session')->orderBy('name')->get(),
            'hariList' => SchedulePatternItem::HARI,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'notes'     => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ], [], ['name' => 'Nama Pola']);

        $pattern = SchedulePattern::create([
            'name'      => $data['name'],
            'notes'     => $data['notes'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pola jadwal berhasil dibuat.',
            'id'      => $pattern->id,
        ]);
    }

    public function update(Request $request, SchedulePattern $schedulePattern)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'notes'     => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ], [], ['name' => 'Nama Pola']);

        $schedulePattern->update([
            'name'      => $data['name'],
            'notes'     => $data['notes'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return response()->json(['success' => true, 'message' => 'Pola jadwal berhasil diperbarui.']);
    }

    public function destroy(SchedulePattern $schedulePattern)
    {
        // Slot yang sudah terbit tetap utuh (schedule_pattern_item_id jadi null).
        $schedulePattern->delete();

        return response()->json(['success' => true, 'message' => 'Pola jadwal berhasil dihapus.']);
    }

    /** Tambah satu baris pola: hari + sesi + mapel + group siswa. */
    public function storeItem(Request $request, SchedulePattern $schedulePattern)
    {
        $data = $request->validate([
            'hari'             => ['required', Rule::in(SchedulePatternItem::HARI)],
            'session_id'       => 'required|exists:schedule_sessions,id',
            'subject_id'       => 'required|exists:subjects,id',
            'student_group_id' => 'required|exists:student_groups,id',
        ], [], [
            'hari'             => 'Hari',
            'session_id'       => 'Sesi',
            'subject_id'       => 'Mata Pelajaran',
            'student_group_id' => 'Group Siswa',
        ]);

        $sudahAda = $schedulePattern->items()
            ->where('hari', $data['hari'])
            ->where('session_id', $data['session_id'])
            ->where('student_group_id', $data['student_group_id'])
            ->exists();

        if ($sudahAda) {
            return response()->json([
                'message' => 'Group ini sudah punya jadwal di hari & sesi tersebut pada pola ini.',
                'errors'  => ['student_group_id' => ['Group sudah terpakai di hari & sesi itu.']],
            ], 422);
        }

        $schedulePattern->items()->create($data);

        return response()->json(['success' => true, 'message' => 'Baris pola berhasil ditambahkan.']);
    }

    public function destroyItem(SchedulePatternItem $item)
    {
        $item->delete();

        return response()->json(['success' => true, 'message' => 'Baris pola berhasil dihapus.']);
    }
}
