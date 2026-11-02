<?php

namespace App\Http\Controllers\Api\Tutor;

use App\Http\Controllers\Concerns\ChecksTutorScheduleConflict;
use App\Http\Controllers\Concerns\MaterializesScheduleSlot;
use App\Models\ScheduleSlot;
use App\Models\ScheduleSlotApplication;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Jadwal dari pola (API, untuk aplikasi mobile tutor): konfirmasi jadwal
 * bawaan minggu lalu, dan pilih slot baru yang masih dibuka. Versi JSON dari
 * Tutor\ScheduleSlotController (web) — logika bisnis sama persis, hanya
 * index() yang berbeda (JSON, bukan Blade).
 */
class ScheduleSlotController extends BaseApiTutorController
{
    use MaterializesScheduleSlot;
    use ChecksTutorScheduleConflict;

    /** Ringkasan satu minggu: jadwal perlu dikonfirmasi, jadwal tersedia, dan keputusan admin minggu itu. */
    public function index(Request $request)
    {
        $tutor = $this->tutor();

        $weekStart = $request->filled('week')
            ? Carbon::parse($request->input('week'))->startOfWeek(Carbon::MONDAY)
            : now()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $relasi = [
            'session',
            'subject',
            'studentGroup',
            'studentGroup.students' => fn ($q) => $q->where('status', 1)->orderBy('full_name'),
            'assignedTutor',
            'filledByTutor',
        ];

        // 1) Menunggu konfirmasi tutor ini — semua minggu ke depan, bukan cuma minggu
        //    terpilih, supaya tidak ada yang terlewat kalau admin menyiapkan jauh-jauh hari.
        $perluKonfirmasi = ScheduleSlot::with($relasi)
            ->where('status', ScheduleSlot::STATUS_PENDING_CONFIRMATION)
            ->where('assigned_tutor_id', $tutor->id)
            ->orderBy('class_date')
            ->get();

        // 2) Slot terbuka (termasuk butuh pengganti) yang cocok kualifikasi, minggu terpilih.
        $spesialisasi = is_array($tutor->specialization) ? $tutor->specialization : [];

        $tersedia = ScheduleSlot::with($relasi)
            ->whereIn('status', [ScheduleSlot::STATUS_OPEN, ScheduleSlot::STATUS_VACANT])
            ->whereDate('week_start', $weekStart->toDateString())
            ->whereHas('subject', fn ($q) => $q->whereIn('subject_name', $spesialisasi ?: ['__tidak_ada__']))
            ->where(function ($q) use ($tutor) {
                $q->whereNull('assigned_tutor_id')->orWhere('assigned_tutor_id', '!=', $tutor->id);
            })
            ->orderBy('class_date')
            ->get();

        $lamaran = ScheduleSlotApplication::where('tutor_id', $tutor->id)
            ->whereIn('schedule_slot_id', $tersedia->pluck('id'))
            ->get()
            ->keyBy('schedule_slot_id');

        // 3) Riwayat keputusan admin atas lamaran tutor ini pada minggu terpilih.
        $keputusan = ScheduleSlotApplication::with(['slot.session', 'slot.subject', 'slot.studentGroup'])
            ->where('tutor_id', $tutor->id)
            ->whereIn('status', [ScheduleSlotApplication::STATUS_ACCEPTED, ScheduleSlotApplication::STATUS_REJECTED])
            ->whereHas('slot', fn ($q) => $q->whereDate('week_start', $weekStart->toDateString()))
            ->get();

        return response()->json([
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekEnd->toDateString(),
            'prev_week' => $weekStart->copy()->subWeek()->toDateString(),
            'next_week' => $weekStart->copy()->addWeek()->toDateString(),
            'specialization' => array_values($spesialisasi),
            'needs_confirmation' => $perluKonfirmasi->map(fn ($s) => $this->slotArray($s))->values(),
            'available' => $tersedia->map(function ($s) use ($lamaran) {
                $app = $lamaran->get($s->id);
                return $this->slotArray($s) + [
                    'application' => $app ? $this->applicationArray($app) : null,
                ];
            })->values(),
            'decisions' => $keputusan->map(fn ($a) => $this->applicationArray($a, withSlot: true))->values(),
        ]);
    }

    /** Tutor melamar slot yang terbuka. */
    public function apply(Request $request, ScheduleSlot $scheduleSlot)
    {
        $tutor = $this->tutor();

        if (!$scheduleSlot->isOpenForApplication()) {
            return response()->json(['message' => 'Slot ini sudah tidak dibuka lagi.'], 422);
        }

        if (!$tutor->isQualifiedFor($scheduleSlot->subject)) {
            return response()->json(['message' => 'Mata pelajaran slot ini di luar kualifikasi Anda.'], 422);
        }

        if ($scheduleSlot->session) {
            $conflict = $this->findTutorScheduleConflict(
                $tutor->id,
                $scheduleSlot->class_date->toDateString(),
                $scheduleSlot->session->time_start,
                $scheduleSlot->session->time_end,
                [],
                [$scheduleSlot->id]
            );
            if ($conflict) {
                return response()->json(['message' => "Sesi ini tidak dapat dipilih karena Anda sudah mengajar {$conflict} pada jam yang sama."], 422);
            }
        }

        $lamaran = ScheduleSlotApplication::updateOrCreate(
            ['schedule_slot_id' => $scheduleSlot->id, 'tutor_id' => $tutor->id],
            [
                'status'     => ScheduleSlotApplication::STATUS_PENDING,
                'note'       => $request->input('note'),
                'applied_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Jadwal berhasil dipilih. Menunggu keputusan admin.',
            'application' => $this->applicationArray($lamaran),
        ]);
    }

    /** Tutor membatalkan lamarannya. */
    public function withdraw(ScheduleSlot $scheduleSlot)
    {
        $tutor = $this->tutor();

        $lamaran = ScheduleSlotApplication::where('schedule_slot_id', $scheduleSlot->id)
            ->where('tutor_id', $tutor->id)
            ->first();

        if (!$lamaran) {
            return response()->json(['message' => 'Anda belum memilih jadwal ini.'], 422);
        }

        if ($lamaran->status === ScheduleSlotApplication::STATUS_ACCEPTED) {
            return response()->json(['message' => 'Lamaran sudah disetujui admin. Hubungi admin untuk perubahan.'], 422);
        }

        $lamaran->update(['status' => ScheduleSlotApplication::STATUS_WITHDRAWN]);

        return response()->json(['success' => true, 'message' => 'Pilihan jadwal dibatalkan.']);
    }

    /** Tutor mengonfirmasi jadwal bawaan minggu sebelumnya → jadwal asli dibuat. */
    public function confirm(ScheduleSlot $scheduleSlot)
    {
        $tutor = $this->tutor();

        if (!$scheduleSlot->needsTutorConfirmation() || (int) $scheduleSlot->assigned_tutor_id !== (int) $tutor->id) {
            return response()->json(['message' => 'Jadwal ini bukan milik Anda atau sudah tidak perlu dikonfirmasi.'], 422);
        }

        // Jaga-jaga: pastikan belum ada komitmen lain yang bentrok di jam ini (mis. jadwal
        // manual baru dari admin) sejak slot ini terbentuk minggu lalu.
        if ($scheduleSlot->session) {
            $conflict = $this->findTutorScheduleConflict(
                $tutor->id,
                $scheduleSlot->class_date->toDateString(),
                $scheduleSlot->session->time_start,
                $scheduleSlot->session->time_end,
                [],
                [$scheduleSlot->id]
            );
            if ($conflict) {
                return response()->json(['message' => "Tidak dapat dikonfirmasi — Anda sudah mengajar {$conflict} pada jam yang sama. Hubungi admin."], 422);
            }
        }

        $hasil = DB::transaction(function () use ($scheduleSlot) {
            $scheduleSlot->update([
                'status'       => ScheduleSlot::STATUS_ASSIGNED,
                'confirmed_at' => now(),
                'assigned_at'  => now(),
            ]);

            return $this->materializeSlot($scheduleSlot->fresh());
        });

        $pesan = "Jadwal dikonfirmasi. {$hasil['created']} jadwal mengajar dibuat";
        if ($hasil['skipped']) {
            $pesan .= ", {$hasil['skipped']} siswa dilewati (sudah ada jadwal)";
        }

        return response()->json([
            'success' => true,
            'message' => $pesan . '.',
            'created' => $hasil['created'],
            'skipped' => $hasil['skipped'],
            'slot' => $this->slotArray($scheduleSlot->fresh(['session', 'subject', 'studentGroup', 'assignedTutor'])),
        ]);
    }

    /**
     * Tutor mengambil izin untuk jadwal bawaan minggu ini saja. Slot menjadi
     * `vacant` (butuh pengganti); assigned_tutor_id TIDAK diubah, sehingga pola
     * rutin tutor ini tetap berlanjut minggu berikutnya.
     */
    public function izin(ScheduleSlot $scheduleSlot)
    {
        $tutor = $this->tutor();

        if (!$scheduleSlot->needsTutorConfirmation() || (int) $scheduleSlot->assigned_tutor_id !== (int) $tutor->id) {
            return response()->json(['message' => 'Jadwal ini bukan milik Anda atau sudah tidak bisa diizinkan.'], 422);
        }

        $scheduleSlot->update([
            'status'  => ScheduleSlot::STATUS_VACANT,
            'izin_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Izin dicatat. Jadwal minggu ini dikosongkan menunggu pengganti dari admin; jadwal rutin Anda tetap berlanjut minggu depan.',
            'slot' => $this->slotArray($scheduleSlot->fresh(['session', 'subject', 'studentGroup', 'assignedTutor'])),
        ]);
    }

    private function slotArray(ScheduleSlot $s): array
    {
        return [
            'id' => $s->id,
            'hari' => $s->hari,
            'class_date' => $s->class_date->toDateString(),
            'week_start' => $s->week_start->toDateString(),
            'status' => $s->status,
            'status_label' => $s->statusLabel(),
            'carried_from_slot_id' => $s->carried_from_slot_id,
            'confirmed_at' => $s->confirmed_at?->toIso8601String(),
            'izin_at' => $s->izin_at?->toIso8601String(),
            'session' => $s->relationLoaded('session') && $s->session ? [
                'id' => $s->session->id,
                'name' => $s->session->name,
                'time_start' => substr($s->session->time_start, 0, 5),
                'time_end' => substr($s->session->time_end, 0, 5),
            ] : null,
            'subject' => $s->relationLoaded('subject') && $s->subject ? [
                'id' => $s->subject->id,
                'subject_name' => $s->subject->subject_name,
            ] : null,
            'student_group' => $s->relationLoaded('studentGroup') && $s->studentGroup ? [
                'id' => $s->studentGroup->id,
                'name' => $s->studentGroup->name,
                'students' => $s->studentGroup->relationLoaded('students')
                    ? $s->studentGroup->students->map(fn ($st) => [
                        'id' => $st->id,
                        'full_name' => $st->full_name,
                        'nis' => $st->nis,
                        'grade' => $st->grade,
                    ])->values()
                    : [],
            ] : null,
            'assigned_tutor' => $s->relationLoaded('assignedTutor') && $s->assignedTutor ? [
                'id' => $s->assignedTutor->id,
                'name' => $s->assignedTutor->name,
            ] : null,
            'filled_by_tutor' => $s->relationLoaded('filledByTutor') && $s->filledByTutor ? [
                'id' => $s->filledByTutor->id,
                'name' => $s->filledByTutor->name,
            ] : null,
        ];
    }

    private function applicationArray(ScheduleSlotApplication $a, bool $withSlot = false): array
    {
        return [
            'id' => $a->id,
            'schedule_slot_id' => $a->schedule_slot_id,
            'status' => $a->status,
            'status_label' => $a->statusLabel(),
            'note' => $a->note,
            'applied_at' => $a->applied_at?->toIso8601String(),
            'slot' => $withSlot && $a->relationLoaded('slot') && $a->slot ? $this->slotArray($a->slot) : null,
        ];
    }
}
