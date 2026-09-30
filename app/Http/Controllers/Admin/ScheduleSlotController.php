<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ComputesTutorEvaluationPerformance;
use App\Http\Controllers\Concerns\MaterializesScheduleSlot;
use App\Http\Controllers\Controller;
use App\Models\SchedulePattern;
use App\Models\ScheduleSlot;
use App\Models\ScheduleSlotApplication;
use App\Models\Tutor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Siapkan & kelola slot jadwal mingguan (admin).
 *
 * "Siapkan Jadwal" mengerjakan dua hal sekaligus per baris pola:
 *  - Baris yang MINGGU SEBELUMNYA sudah ada tutornya → slot dibuat dengan tutor
 *    tsb, status `pending_confirmation` (TUTOR yang mengonfirmasi).
 *  - Baris yang belum pernah terisi → slot `open`, dilelang, ADMIN yang
 *    menetapkan satu dari para pelamar.
 *
 * Bila tutor rutin mengambil IZIN untuk satu minggu, slot menjadi `vacant`
 * tanpa mengubah `assigned_tutor_id` (lihat catatan di ScheduleSlot). Admin
 * menetapkan tutor PENGGANTI khusus minggu itu lewat assign() — pengganti
 * disimpan di `filled_by_tutor_id`, kepemilikan pola tidak berpindah.
 */
class ScheduleSlotController extends Controller
{
    use MaterializesScheduleSlot;
    use ComputesTutorEvaluationPerformance;

    /**
     * Bobot skor gabungan untuk mengurutkan pelamar: waktu mendaftar (siapa
     * lebih dulu) + rekam jejak kecepatan & ketepatan mengisi evaluasi.
     * Tutor yang agak telat melamar tapi rekam jejaknya jauh lebih baik bisa
     * tetap tampil di atas — bukan sekadar first-come-first-served.
     */
    private const BOBOT_WAKTU_DAFTAR      = 0.4;
    private const BOBOT_KECEPATAN_EVALUASI = 0.3;
    private const BOBOT_KETEPATAN_EVALUASI = 0.3;

    public function index(Request $request)
    {
        $weekStart = $this->resolveWeek($request);

        $patterns = SchedulePattern::orderByDesc('is_active')->orderBy('name')->get();

        $slots = ScheduleSlot::with([
                'session', 'subject', 'studentGroup', 'assignedTutor',
                'applications.tutor', 'patternItem',
            ])
            ->whereDate('week_start', $weekStart->toDateString())
            ->get()
            ->sortBy([
                fn ($a, $b) => array_search($a->hari, \App\Models\SchedulePatternItem::HARI)
                    <=> array_search($b->hari, \App\Models\SchedulePatternItem::HARI),
                fn ($a, $b) => ($a->session->time_start ?? '') <=> ($b->session->time_start ?? ''),
            ])
            ->values();

        $ringkasan = [
            'total'    => $slots->count(),
            'open'     => $slots->whereIn('status', [ScheduleSlot::STATUS_OPEN, ScheduleSlot::STATUS_VACANT])->count(),
            'konfirm'  => $slots->where('status', ScheduleSlot::STATUS_PENDING_CONFIRMATION)->count(),
            'terisi'   => $slots->where('status', ScheduleSlot::STATUS_ASSIGNED)->count(),
            'vacant'   => $slots->where('status', ScheduleSlot::STATUS_VACANT)->count(),
            'ditutup'  => $slots->where('status', ScheduleSlot::STATUS_CLOSED)->count(),
        ];

        return view('admin.schedule_slots.index', [
            'weekStart' => $weekStart,
            'weekEnd'   => $weekStart->copy()->endOfWeek(Carbon::SUNDAY),
            'prevWeek'  => $weekStart->copy()->subWeek()->toDateString(),
            'nextWeek'  => $weekStart->copy()->addWeek()->toDateString(),
            'slots'     => $slots,
            'patterns'  => $patterns,
            'ringkasan' => $ringkasan,
        ]);
    }

    /** Trigger mingguan: buat slot dari pola (carry-over atau dilelang). */
    public function prepare(Request $request)
    {
        $data = $request->validate([
            'schedule_pattern_id' => 'required|exists:schedule_patterns,id',
            'week_date'           => 'required|date',
        ], [], ['schedule_pattern_id' => 'Pola Jadwal', 'week_date' => 'Minggu']);

        $weekStart = Carbon::parse($data['week_date'])->startOfWeek(Carbon::MONDAY);
        $pattern   = SchedulePattern::with('items')->findOrFail($data['schedule_pattern_id']);

        if ($pattern->items->isEmpty()) {
            return response()->json([
                'message' => 'Pola ini belum punya baris jadwal. Isi polanya terlebih dahulu.',
            ], 422);
        }

        $dibawa = 0;
        $dibuka = 0;
        $lewati = 0;

        DB::transaction(function () use ($pattern, $weekStart, &$dibawa, &$dibuka, &$lewati) {
            foreach ($pattern->items as $item) {
                $sudahAda = ScheduleSlot::where('schedule_pattern_item_id', $item->id)
                    ->whereDate('week_start', $weekStart->toDateString())
                    ->exists();

                if ($sudahAda) {
                    $lewati++;
                    continue;
                }

                // Tutor petahana: slot terakhir baris ini yang sudah punya tutor.
                // Slot yang tutornya melepas (kembali `open`) tidak ikut, jadi
                // baris itu otomatis kembali dilelang.
                $petahana = ScheduleSlot::where('schedule_pattern_item_id', $item->id)
                    ->whereNotNull('assigned_tutor_id')
                    ->whereIn('status', [ScheduleSlot::STATUS_ASSIGNED, ScheduleSlot::STATUS_PENDING_CONFIRMATION])
                    ->whereDate('week_start', '<', $weekStart->toDateString())
                    ->orderByDesc('week_start')
                    ->first();

                ScheduleSlot::create([
                    'schedule_pattern_item_id' => $item->id,
                    'week_start'               => $weekStart->toDateString(),
                    'class_date'               => $this->classDateFor($weekStart, $item->hari)->toDateString(),
                    'hari'                     => $item->hari,
                    'session_id'               => $item->session_id,
                    'subject_id'               => $item->subject_id,
                    'student_group_id'         => $item->student_group_id,
                    'status'                   => $petahana
                        ? ScheduleSlot::STATUS_PENDING_CONFIRMATION
                        : ScheduleSlot::STATUS_OPEN,
                    'assigned_tutor_id'        => $petahana?->assigned_tutor_id,
                    'carried_from_slot_id'     => $petahana?->id,
                ]);

                $petahana ? $dibawa++ : $dibuka++;
            }
        });

        $pesan = [];
        if ($dibawa) $pesan[] = "{$dibawa} slot dibawa dari minggu sebelumnya (menunggu konfirmasi tutor)";
        if ($dibuka) $pesan[] = "{$dibuka} slot dibuka untuk dilamar tutor";
        if ($lewati) $pesan[] = "{$lewati} slot sudah disiapkan sebelumnya";

        return response()->json([
            'success' => true,
            'message' => $pesan ? ucfirst(implode(', ', $pesan)) . '.' : 'Tidak ada slot baru yang perlu disiapkan.',
        ]);
    }

    /**
     * Daftar tutor yang melamar sebuah slot (untuk modal admin), diurutkan
     * dari skor gabungan tertinggi: kecepatan mendaftar + rekam jejak
     * kecepatan & ketepatan mengisi evaluasi (lihat BOBOT_* di atas).
     */
    public function applicants(ScheduleSlot $scheduleSlot)
    {
        $applications = $scheduleSlot->applications()->with('tutor')->orderBy('applied_at')->get();

        $awal = $applications->min('applied_at');

        $rows = $applications->map(function ($a) use ($awal) {
            $performa = $a->tutor ? $this->tutorEvaluationPerformance($a->tutor) : [
                'kecepatan_jam' => null, 'ketepatan_persen' => null,
                'skor_kecepatan' => 50.0, 'skor_ketepatan' => 50.0,
            ];

            // Skor waktu daftar: 100 bagi yang paling awal, meluruh seiring jarak menit dari yang terawal.
            $menitSetelahAwal = $awal && $a->applied_at ? $awal->diffInMinutes($a->applied_at) : 0;
            $skorWaktu = 100 / (1 + $menitSetelahAwal / 60);

            $skor = self::BOBOT_WAKTU_DAFTAR * $skorWaktu
                + self::BOBOT_KECEPATAN_EVALUASI * $performa['skor_kecepatan']
                + self::BOBOT_KETEPATAN_EVALUASI * $performa['skor_ketepatan'];

            return [
                'id'               => $a->id,
                'tutor_id'         => $a->tutor_id,
                'tutor'            => $a->tutor->name ?? '-',
                'spesialis'        => implode(', ', is_array($a->tutor->specialization ?? null) ? $a->tutor->specialization : []),
                'status'           => $a->status,
                'status_label'     => $a->statusLabel(),
                'note'             => $a->note,
                'applied_at'       => $a->applied_at?->translatedFormat('d M Y H:i'),
                'kecepatan_evaluasi' => $performa['kecepatan_jam'] !== null ? $performa['kecepatan_jam'] . ' jam' : 'Belum ada data',
                'ketepatan_evaluasi' => $performa['ketepatan_persen'] !== null ? $performa['ketepatan_persen'] . '%' : 'Belum ada data',
                'skor'             => round($skor, 1),
            ];
        })
        ->sortByDesc('skor')
        ->values();

        return response()->json([
            'slot' => [
                'id'      => $scheduleSlot->id,
                'label'   => $scheduleSlot->hari . ', ' . $scheduleSlot->class_date->translatedFormat('d M Y')
                             . ' · ' . ($scheduleSlot->session->name ?? '-'),
                'mapel'   => $scheduleSlot->subject->subject_name ?? '-',
                'group'   => $scheduleSlot->studentGroup->name ?? '-',
                'status'  => $scheduleSlot->status,
            ],
            'applicants' => $rows,
        ]);
    }

    /** Admin menetapkan satu tutor untuk slot ini, lalu jadwal asli dibuat. */
    public function assign(Request $request, ScheduleSlot $scheduleSlot)
    {
        $data = $request->validate(['tutor_id' => 'required|exists:tutors,id'], [], ['tutor_id' => 'Tutor']);

        if ($scheduleSlot->isAssigned()) {
            return response()->json(['message' => 'Slot ini sudah terisi. Batalkan penetapan dulu bila ingin mengganti tutor.'], 422);
        }

        if ($scheduleSlot->status === ScheduleSlot::STATUS_CLOSED) {
            return response()->json(['message' => 'Slot ini sudah ditutup. Buka kembali sebelum menetapkan tutor.'], 422);
        }

        $hasil = DB::transaction(function () use ($scheduleSlot, $data) {
            // Slot `vacant`: tutor rutin tetap sama (assigned_tutor_id tidak diubah),
            // hanya dicatat siapa pengganti minggu ini. Slot `open`: baru pertama
            // kali terisi, tutor ini jadi pemilik rutin baris polanya.
            $update = $scheduleSlot->isVacant()
                ? ['filled_by_tutor_id' => $data['tutor_id']]
                : ['assigned_tutor_id' => $data['tutor_id'], 'filled_by_tutor_id' => null];

            $scheduleSlot->update(array_merge($update, [
                'status'      => ScheduleSlot::STATUS_ASSIGNED,
                'assigned_at' => now(),
                'assigned_by' => auth()->id(),
            ]));

            // Pelamar terpilih diterima, sisanya ditolak.
            $scheduleSlot->applications()
                ->where('tutor_id', $data['tutor_id'])
                ->update(['status' => ScheduleSlotApplication::STATUS_ACCEPTED]);

            $scheduleSlot->applications()
                ->where('tutor_id', '!=', $data['tutor_id'])
                ->where('status', ScheduleSlotApplication::STATUS_PENDING)
                ->update(['status' => ScheduleSlotApplication::STATUS_REJECTED]);

            return $this->materializeSlot($scheduleSlot->fresh());
        });

        $tutor = Tutor::find($data['tutor_id']);
        $pesan = "Slot ditetapkan ke {$tutor->name}. {$hasil['created']} jadwal dibuat";
        if ($hasil['skipped']) {
            $pesan .= ", {$hasil['skipped']} siswa dilewati (kuota habis: {$hasil['reasons']['kuota_habis']}, "
                    . "jadwal sudah ada: {$hasil['reasons']['jadwal_sudah_ada']})";
        }

        return response()->json(['success' => true, 'message' => $pesan . '.']);
    }

    /** Batalkan penetapan: jadwal ditarik, slot kembali dibuka (atau kembali `vacant` bila ini penggantian izin). */
    public function unassign(ScheduleSlot $scheduleSlot)
    {
        $hasil = DB::transaction(function () use ($scheduleSlot) {
            $hasil = $this->releaseSlotSchedules($scheduleSlot);

            // Kalau ini sekadar penggantian untuk slot yang tutor rutinnya izin,
            // kembalikan ke `vacant` (tutor rutin tetap tercatat, tinggal cari pengganti lain).
            // Selain itu, ini penetapan biasa yang dibatalkan → kembali `open` sepenuhnya.
            $wasVacantFill = (bool) $scheduleSlot->filled_by_tutor_id;

            $scheduleSlot->update([
                'assigned_tutor_id'  => $wasVacantFill ? $scheduleSlot->assigned_tutor_id : null,
                'filled_by_tutor_id' => null,
                'status'             => $wasVacantFill ? ScheduleSlot::STATUS_VACANT : ScheduleSlot::STATUS_OPEN,
                'assigned_at'        => null,
                'assigned_by'        => null,
                'confirmed_at'       => null,
            ]);

            // Semua lamaran dikembalikan agar bisa dipertimbangkan ulang.
            $scheduleSlot->applications()
                ->whereIn('status', [ScheduleSlotApplication::STATUS_ACCEPTED, ScheduleSlotApplication::STATUS_REJECTED])
                ->update(['status' => ScheduleSlotApplication::STATUS_PENDING]);

            return $hasil;
        });

        $pesan = "Penetapan dibatalkan, slot kembali dibuka. {$hasil['deleted']} jadwal ditarik";
        if ($hasil['kept']) {
            $pesan .= ", {$hasil['kept']} jadwal dipertahankan karena sudah dievaluasi";
        }

        return response()->json(['success' => true, 'message' => $pesan . '.']);
    }

    /** Tutup slot (tidak jadi dipakai minggu ini). */
    public function close(ScheduleSlot $scheduleSlot)
    {
        if ($scheduleSlot->isAssigned()) {
            return response()->json(['message' => 'Slot sudah terisi. Batalkan penetapan dulu sebelum menutup.'], 422);
        }

        $scheduleSlot->update([
            'status'    => ScheduleSlot::STATUS_CLOSED,
            'closed_at' => now(),
            'closed_by' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Slot ditutup.']);
    }

    public function reopen(ScheduleSlot $scheduleSlot)
    {
        $scheduleSlot->update([
            'status'    => $scheduleSlot->izin_at ? ScheduleSlot::STATUS_VACANT : ScheduleSlot::STATUS_OPEN,
            'closed_at' => null,
            'closed_by' => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Slot dibuka kembali.']);
    }

    /** Daftar tutor berkualifikasi untuk mata pelajaran slot ini (dipakai saat memilih pengganti). */
    public function qualifiedTutors(ScheduleSlot $scheduleSlot)
    {
        $tutors = Tutor::qualifiedFor($scheduleSlot->subject->subject_name ?? null)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['tutors' => $tutors]);
    }

    private function resolveWeek(Request $request): Carbon
    {
        try {
            return $request->filled('week')
                ? Carbon::parse($request->input('week'))->startOfWeek(Carbon::MONDAY)
                : now()->startOfWeek(Carbon::MONDAY);
        } catch (\Throwable) {
            return now()->startOfWeek(Carbon::MONDAY);
        }
    }
}
