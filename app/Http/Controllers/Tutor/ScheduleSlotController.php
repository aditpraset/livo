<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Concerns\MaterializesScheduleSlot;
use App\Models\ScheduleSlot;
use App\Models\ScheduleSlotApplication;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Jadwal untuk tutor: konfirmasi jadwal bawaan minggu lalu, dan melamar
 * slot yang masih dibuka.
 *
 *  - Perlu Dikonfirmasi : slot `pending_confirmation` milik tutor ini
 *                         (pola sudah terbentuk minggu-minggu sebelumnya).
 *                         Konfirmasi → jadwal asli langsung dibuat.
 *                         Izin       → HANYA minggu ini yang dikosongkan
 *                                      (status jadi `vacant`), pola tetap
 *                                      milik tutor ini untuk minggu berikutnya.
 *  - Tersedia           : slot `open` (belum pernah terisi) ATAU `vacant`
 *                         (kosong karena tutor rutinnya izin) yang mapelnya
 *                         cocok kualifikasi tutor. Tutor melamar, ADMIN yang
 *                         memutuskan/menetapkan pengganti.
 */
class ScheduleSlotController extends BaseTutorController
{
    use MaterializesScheduleSlot;

    public function index(Request $request)
    {
        $tutor = $this->tutor();

        $weekStart = $request->filled('week')
            ? Carbon::parse($request->input('week'))->startOfWeek(Carbon::MONDAY)
            : now()->startOfWeek(Carbon::MONDAY);

        // Siswa aktif ikut dimuat: kartu jadwal menampilkan siapa saja yang akan diajar.
        // Hanya yang berstatus aktif, karena itu yang nanti benar-benar dibuatkan jadwal.
        $relasi = [
            'session',
            'subject',
            'studentGroup',
            'studentGroup.students' => fn ($q) => $q->where('status', 1)->orderBy('full_name'),
        ];

        // 1) Menunggu konfirmasi tutor ini — semua minggu ke depan, bukan cuma minggu terpilih,
        //    supaya tidak ada yang terlewat kalau admin menyiapkan jauh-jauh hari.
        $perluKonfirmasi = ScheduleSlot::with($relasi)
            ->where('status', ScheduleSlot::STATUS_PENDING_CONFIRMATION)
            ->where('assigned_tutor_id', $tutor->id)
            ->orderBy('class_date')
            ->get();

        // 2) Slot terbuka yang cocok kualifikasi (specialization berisi nama mapel).
        $spesialisasi = is_array($tutor->specialization) ? $tutor->specialization : [];

        $tersedia = ScheduleSlot::with($relasi)
            ->whereIn('status', [ScheduleSlot::STATUS_OPEN, ScheduleSlot::STATUS_VACANT])
            ->whereDate('week_start', $weekStart->toDateString())
            ->whereHas('subject', fn ($q) => $q->whereIn('subject_name', $spesialisasi ?: ['__tidak_ada__']))
            // Slot yang kosong karena tutor ini sendiri izin tidak ikut ditawarkan ke dirinya sendiri.
            ->where(function ($q) use ($tutor) {
                $q->whereNull('assigned_tutor_id')->orWhere('assigned_tutor_id', '!=', $tutor->id);
            })
            ->orderBy('class_date')
            ->get();

        // Lamaran tutor ini pada slot-slot tsb.
        $lamaran = ScheduleSlotApplication::where('tutor_id', $tutor->id)
            ->whereIn('schedule_slot_id', $tersedia->pluck('id'))
            ->get()
            ->keyBy('schedule_slot_id');

        // Riwayat keputusan admin atas lamaran tutor ini (agar tutor tahu hasilnya).
        $keputusan = ScheduleSlotApplication::with(['slot.session', 'slot.subject', 'slot.studentGroup'])
            ->where('tutor_id', $tutor->id)
            ->whereIn('status', [ScheduleSlotApplication::STATUS_ACCEPTED, ScheduleSlotApplication::STATUS_REJECTED])
            ->whereHas('slot', fn ($q) => $q->whereDate('week_start', $weekStart->toDateString()))
            ->get();

        return view('tutor.schedule_slots.index', [
            'tutor'           => $tutor,
            'weekStart'       => $weekStart,
            'weekEnd'         => $weekStart->copy()->endOfWeek(Carbon::SUNDAY),
            'prevWeek'        => $weekStart->copy()->subWeek()->toDateString(),
            'nextWeek'        => $weekStart->copy()->addWeek()->toDateString(),
            'perluKonfirmasi' => $perluKonfirmasi,
            'tersedia'        => $tersedia,
            'lamaran'         => $lamaran,
            'keputusan'       => $keputusan,
            'spesialisasi'    => $spesialisasi,
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

        ScheduleSlotApplication::updateOrCreate(
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
            $pesan .= ", {$hasil['skipped']} siswa dilewati (kuota habis atau sudah ada jadwal)";
        }

        return response()->json(['success' => true, 'message' => $pesan . '.']);
    }

    /**
     * Tutor mengambil izin untuk jadwal bawaan minggu ini saja.
     * Slot menjadi `vacant` (butuh pengganti); `assigned_tutor_id` TIDAK
     * diubah, sehingga pola rutin tutor ini tetap berlanjut minggu berikutnya.
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
        ]);
    }
}
