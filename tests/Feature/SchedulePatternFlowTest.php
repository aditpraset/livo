<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\SchedulePattern;
use App\Models\ScheduleSession;
use App\Models\ScheduleSlot;
use App\Models\ScheduleSlotApplication;
use App\Models\Student;
use App\Models\StudentGroup;
use App\Models\Subject;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Alur penuh pola jadwal mingguan:
 *   pola → siapkan minggu 1 (dilelang) → tutor melamar → admin menetapkan
 *        → siapkan minggu 2 (carry-over, tutor yang konfirmasi)
 *        → tutor mengambil izin di minggu 3 → slot jadi vacant, admin
 *          menetapkan pengganti → siapkan minggu 4 tetap carry-over ke
 *          tutor RUTIN (bukan pengganti).
 */
class SchedulePatternFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $userTutorA;
    private User $userTutorB;
    private Tutor $tutorA;
    private Tutor $tutorB;
    private Tutor $tutorLain;     // kualifikasi berbeda
    private StudentGroup $group;
    private Subject $subject;
    private SchedulePattern $pattern;
    private int $itemId;

    private const MINGGU_1 = '2026-10-05'; // Senin
    private const MINGGU_2 = '2026-10-12';
    private const MINGGU_3 = '2026-10-19';
    private const MINGGU_4 = '2026-10-26';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

        foreach (['admin', 'tutor', 'siswa'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->admin = User::factory()->create(['email' => 'admin@pola.test', 'role' => 'admin', 'status' => 'aktif']);
        $this->admin->syncRoles(['admin']);

        $this->subject = Subject::create(['subject_name' => 'Matematika']);
        Subject::create(['subject_name' => 'Biologi']);

        $this->tutorA = Tutor::create(['name' => 'Tutor A', 'phone' => '0811', 'specialization' => ['Matematika']]);
        $this->tutorB = Tutor::create(['name' => 'Tutor B', 'phone' => '0812', 'specialization' => ['Matematika']]);
        $this->tutorLain = Tutor::create(['name' => 'Tutor Biologi', 'phone' => '0813', 'specialization' => ['Biologi']]);

        $this->userTutorA = $this->buatUserTutor($this->tutorA, 'a@pola.test');
        $this->userTutorB = $this->buatUserTutor($this->tutorB, 'b@pola.test');

        $session = ScheduleSession::create(['name' => 'Sesi 1', 'time_start' => '13:00', 'time_end' => '14:30']);

        $this->group = StudentGroup::create(['name' => 'Group Uji', 'session_id' => $session->id, 'hari' => 'Senin']);
        foreach (['Siswa 1', 'Siswa 2'] as $nama) {
            $siswa = Student::create(['full_name' => $nama, 'grade' => 'SMA Kelas 10', 'program' => 'Matematika',
                'quota_sessions' => 10, 'status' => 1]);
            $this->group->students()->attach($siswa->id);
        }

        $this->pattern = SchedulePattern::create(['name' => 'Pola Uji', 'is_active' => true]);
        $this->itemId = $this->pattern->items()->create([
            'hari' => 'Senin', 'session_id' => $session->id,
            'subject_id' => $this->subject->id, 'student_group_id' => $this->group->id,
        ])->id;
    }

    private function buatUserTutor(Tutor $tutor, string $email): User
    {
        $u = User::factory()->create(['email' => $email, 'role' => 'tutor', 'status' => 'aktif', 'tutor_id' => $tutor->id]);
        $u->syncRoles(['tutor']);

        return $u;
    }

    private function siapkan(string $minggu)
    {
        return $this->actingAs($this->admin)->post('/admin/schedule-slots/prepare', [
            'schedule_pattern_id' => $this->pattern->id,
            'week_date'           => $minggu,
        ]);
    }

    private function slotMinggu(string $minggu): ScheduleSlot
    {
        return ScheduleSlot::where('schedule_pattern_item_id', $this->itemId)
            ->whereDate('week_start', $minggu)->firstOrFail();
    }

    public function test_minggu_pertama_slot_dibuka_untuk_dilelang(): void
    {
        $this->siapkan(self::MINGGU_1)->assertOk()->assertJson(['success' => true]);

        $slot = $this->slotMinggu(self::MINGGU_1);
        $this->assertSame(ScheduleSlot::STATUS_OPEN, $slot->status, 'Belum ada tutor sebelumnya → harus dilelang');
        $this->assertNull($slot->assigned_tutor_id);
        $this->assertSame('2026-10-05', $slot->class_date->toDateString(), 'Senin di minggu itu');
        $this->assertSame(0, Schedule::count(), 'Jadwal asli belum boleh dibuat sebelum ada tutor');
    }

    public function test_siapkan_dua_kali_tidak_membuat_slot_dobel(): void
    {
        $this->siapkan(self::MINGGU_1)->assertOk();
        $this->siapkan(self::MINGGU_1)->assertOk();

        $this->assertSame(1, ScheduleSlot::whereDate('week_start', self::MINGGU_1)->count());
    }

    public function test_tutor_hanya_melihat_slot_sesuai_kualifikasi(): void
    {
        $this->siapkan(self::MINGGU_1)->assertOk();

        // Tutor Matematika melihat slot
        $this->actingAs($this->userTutorA)
            ->get('/tutor/jadwal-tersedia?week=' . self::MINGGU_1)
            ->assertOk()->assertSee('Group Uji');

        // Tutor Biologi tidak boleh melamar slot Matematika
        $userLain = $this->buatUserTutor($this->tutorLain, 'lain@pola.test');
        $this->actingAs($userLain)
            ->postJson('/tutor/jadwal-tersedia/' . $this->slotMinggu(self::MINGGU_1)->id . '/pilih')
            ->assertStatus(422);
    }

    public function test_alur_penuh_lelang_lalu_carry_over_dan_izin(): void
    {
        /* ── Minggu 1: dilelang, dua tutor melamar ── */
        $this->siapkan(self::MINGGU_1)->assertOk();
        $slot1 = $this->slotMinggu(self::MINGGU_1);

        $this->actingAs($this->userTutorA)->post("/tutor/jadwal-tersedia/{$slot1->id}/pilih")->assertOk();
        $this->actingAs($this->userTutorB)->post("/tutor/jadwal-tersedia/{$slot1->id}/pilih")->assertOk();
        $this->assertSame(2, $slot1->applications()->count());

        /* ── Admin menetapkan Tutor A ── */
        $this->actingAs($this->admin)
            ->post("/admin/schedule-slots/{$slot1->id}/assign", ['_method' => 'PUT', 'tutor_id' => $this->tutorA->id])
            ->assertOk();

        $slot1->refresh();
        $this->assertSame(ScheduleSlot::STATUS_ASSIGNED, $slot1->status);
        $this->assertSame($this->tutorA->id, $slot1->assigned_tutor_id);

        $this->assertSame(ScheduleSlotApplication::STATUS_ACCEPTED,
            $slot1->applications()->where('tutor_id', $this->tutorA->id)->first()->status);
        $this->assertSame(ScheduleSlotApplication::STATUS_REJECTED,
            $slot1->applications()->where('tutor_id', $this->tutorB->id)->first()->status,
            'Pelamar yang tidak terpilih harus ditolak otomatis');

        // Jadwal asli untuk 2 siswa di group
        $this->assertSame(2, Schedule::where('schedule_slot_id', $slot1->id)->count());
        $this->assertTrue(Schedule::where('schedule_slot_id', $slot1->id)
            ->get()->every(fn ($s) => $s->status_schedule === 'scheduled' && $s->tutor_id === $this->tutorA->id));

        /* ── Minggu 2: BUKAN lelang lagi, tapi konfirmasi ── */
        $this->siapkan(self::MINGGU_2)->assertOk();
        $slot2 = $this->slotMinggu(self::MINGGU_2);

        $this->assertSame(ScheduleSlot::STATUS_PENDING_CONFIRMATION, $slot2->status,
            'Pola sudah terbentuk → minggu berikutnya harus menunggu konfirmasi, bukan dilelang');
        $this->assertSame($this->tutorA->id, $slot2->assigned_tutor_id, 'Tutor minggu lalu dibawa serta');
        $this->assertSame($slot1->id, $slot2->carried_from_slot_id);
        $this->assertSame(2, Schedule::count(), 'Jadwal belum bertambah sebelum tutor konfirmasi');

        /* ── Tutor A konfirmasi ── */
        $this->actingAs($this->userTutorA)
            ->post("/tutor/jadwal-tersedia/{$slot2->id}/konfirmasi", ['_method' => 'PUT'])
            ->assertOk();

        $slot2->refresh();
        $this->assertSame(ScheduleSlot::STATUS_ASSIGNED, $slot2->status);
        $this->assertNotNull($slot2->confirmed_at);
        $this->assertSame(2, Schedule::where('schedule_slot_id', $slot2->id)->count());

        /* ── Minggu 3: carry-over lagi, tapi tutor A mengambil izin ── */
        $this->siapkan(self::MINGGU_3)->assertOk();
        $slot3 = $this->slotMinggu(self::MINGGU_3);
        $this->assertSame(ScheduleSlot::STATUS_PENDING_CONFIRMATION, $slot3->status);

        $this->actingAs($this->userTutorA)
            ->post("/tutor/jadwal-tersedia/{$slot3->id}/izin", ['_method' => 'PUT'])
            ->assertOk();

        $slot3->refresh();
        $this->assertSame(ScheduleSlot::STATUS_VACANT, $slot3->status, 'Izin → butuh pengganti minggu ini saja');
        $this->assertSame($this->tutorA->id, $slot3->assigned_tutor_id,
            'Izin TIDAK melepas kepemilikan pola — tutor rutin tetap tercatat');
        $this->assertNotNull($slot3->izin_at);

        /* ── Tutor B melamar untuk mengisi kekosongan minggu ini ── */
        $this->actingAs($this->userTutorB)->post("/tutor/jadwal-tersedia/{$slot3->id}/pilih")->assertOk();
        $this->assertSame(1, $slot3->applications()->where('status', ScheduleSlotApplication::STATUS_PENDING)->count());

        /* ── Admin menetapkan Tutor B sebagai PENGGANTI khusus minggu ini ── */
        $this->actingAs($this->admin)
            ->post("/admin/schedule-slots/{$slot3->id}/assign", ['_method' => 'PUT', 'tutor_id' => $this->tutorB->id])
            ->assertOk();

        $slot3->refresh();
        $this->assertSame(ScheduleSlot::STATUS_ASSIGNED, $slot3->status);
        $this->assertSame($this->tutorA->id, $slot3->assigned_tutor_id, 'Pemilik pola tidak berubah karena ini sekadar pengganti');
        $this->assertSame($this->tutorB->id, $slot3->filled_by_tutor_id);
        $this->assertTrue(Schedule::where('schedule_slot_id', $slot3->id)
            ->get()->every(fn ($s) => $s->tutor_id === $this->tutorB->id), 'Jadwal asli minggu ini memakai tutor PENGGANTI');

        /* ── Minggu 4: carry-over harus kembali ke tutor RUTIN (A), bukan pengganti (B) ── */
        $this->siapkan(self::MINGGU_4)->assertOk();
        $slot4 = $this->slotMinggu(self::MINGGU_4);

        $this->assertSame(ScheduleSlot::STATUS_PENDING_CONFIRMATION, $slot4->status);
        $this->assertSame($this->tutorA->id, $slot4->assigned_tutor_id,
            'Minggu berikutnya tetap milik tutor rutin, bukan pengganti sesaat');
    }

    public function test_batal_penetapan_menarik_jadwal_dan_membuka_slot(): void
    {
        $this->siapkan(self::MINGGU_1)->assertOk();
        $slot = $this->slotMinggu(self::MINGGU_1);

        $this->actingAs($this->userTutorA)->post("/tutor/jadwal-tersedia/{$slot->id}/pilih")->assertOk();
        $this->actingAs($this->admin)
            ->post("/admin/schedule-slots/{$slot->id}/assign", ['_method' => 'PUT', 'tutor_id' => $this->tutorA->id])
            ->assertOk();
        $this->assertSame(2, Schedule::count());

        $this->actingAs($this->admin)
            ->post("/admin/schedule-slots/{$slot->id}/unassign", ['_method' => 'PUT'])
            ->assertOk();

        $slot->refresh();
        $this->assertSame(ScheduleSlot::STATUS_OPEN, $slot->status);
        $this->assertNull($slot->assigned_tutor_id);
        $this->assertSame(0, Schedule::count(), 'Jadwal ditarik kembali');
        $this->assertSame(ScheduleSlotApplication::STATUS_PENDING,
            $slot->applications()->first()->status, 'Lamaran dikembalikan agar bisa dipilih ulang');
    }

    public function test_kartu_jadwal_menampilkan_siswa_aktif_saja(): void
    {
        // Satu siswa non-aktif ikut jadi anggota group — tidak boleh tampil di kartu.
        $nonAktif = Student::create(['full_name' => 'Siswa Cuti', 'grade' => 'SMA Kelas 10',
            'program' => 'Matematika', 'quota_sessions' => 10, 'status' => 2]);
        $this->group->students()->attach($nonAktif->id);

        $this->siapkan(self::MINGGU_1)->assertOk();

        $res = $this->actingAs($this->userTutorA)->get('/tutor/jadwal-tersedia?week=' . self::MINGGU_1);

        $res->assertOk()
            ->assertSee('row-cards', false)          // tampilan grid card, bukan tabel
            ->assertSee('slot-card', false)
            ->assertSee('btn-lihat-siswa', false)    // kartu hanya menampilkan jumlah + tombol
            ->assertSee('modal-siswa', false)        // popup daftar nama tersedia
            ->assertSee('Siswa 1')                   // nama dibawa ke popup lewat data-attribute
            ->assertSee('Siswa 2')
            ->assertDontSee('Siswa Cuti');           // anggota non-aktif tidak ikut sama sekali
    }
}
