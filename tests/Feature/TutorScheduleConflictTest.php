<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\SchedulePattern;
use App\Models\ScheduleSession;
use App\Models\ScheduleSlot;
use App\Models\Student;
use App\Models\StudentGroup;
use App\Models\Subject;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Satu tutor tidak boleh punya dua komitmen mengajar yang jamnya tumpang tindih
 * di tanggal yang sama — dicek di SELURUH alur penjadwalan: Tambah/Edit Jadwal
 * manual, Buat per Grouping, dan Jadwal dari Pola (admin menetapkan tutor,
 * tutor melamar/konfirmasi sendiri — baik web maupun API mobile).
 */
class TutorScheduleConflictTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Tutor $tutor;
    private Tutor $tutorLain;
    private Student $siswaA;
    private Student $siswaB;
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

        foreach (['admin', 'tutor', 'siswa'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $this->admin->syncRoles(['admin']);

        $this->subject = Subject::create(['subject_name' => 'Matematika']);
        $this->tutor = Tutor::create(['name' => 'Tutor Bentrok', 'phone' => '0811', 'specialization' => ['Matematika']]);
        $this->tutorLain = Tutor::create(['name' => 'Tutor Lain', 'phone' => '0812', 'specialization' => ['Matematika']]);

        $this->siswaA = Student::create(['full_name' => 'Siswa A', 'grade' => 'SMA 10', 'program' => json_encode(['Matematika']), 'quota_sessions' => 10, 'status' => 1]);
        $this->siswaB = Student::create(['full_name' => 'Siswa B', 'grade' => 'SMA 10', 'program' => json_encode(['Matematika']), 'quota_sessions' => 10, 'status' => 1]);
    }

    private function tutorUser(Tutor $tutor, string $email): User
    {
        $u = User::factory()->create(['email' => $email, 'role' => 'tutor', 'status' => 'aktif', 'tutor_id' => $tutor->id]);
        $u->syncRoles(['tutor']);

        return $u;
    }

    /* ============================================================
       1) Tambah/Edit Jadwal manual oleh admin
    ============================================================ */

    public function test_tambah_jadwal_manual_ditolak_bila_tutor_sudah_bentrok(): void
    {
        Schedule::create([
            'student_id' => $this->siswaA->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => '2027-01-04', 'start_time' => '10:00', 'end_time' => '11:30', 'status_schedule' => 'scheduled',
        ]);

        $resp = $this->actingAs($this->admin)->postJson('/admin/schedules', [
            'student_id' => $this->siswaB->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => '2027-01-04', 'start_time' => '10:30', 'end_time' => '12:00', // overlap 10:30-11:30
        ]);

        $resp->assertStatus(422);
        $this->assertSame(1, Schedule::count(), 'Jadwal kedua yang bentrok tidak boleh dibuat');
    }

    public function test_tambah_jadwal_manual_diterima_bila_jam_tidak_overlap(): void
    {
        Schedule::create([
            'student_id' => $this->siswaA->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => '2027-01-04', 'start_time' => '10:00', 'end_time' => '11:30', 'status_schedule' => 'scheduled',
        ]);

        $resp = $this->actingAs($this->admin)->postJson('/admin/schedules', [
            'student_id' => $this->siswaB->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => '2027-01-04', 'start_time' => '13:00', 'end_time' => '14:30', // tidak overlap
        ]);

        $resp->assertOk()->assertJson(['success' => true]);
        $this->assertSame(2, Schedule::count());
    }

    public function test_edit_jadwal_tidak_salah_anggap_bentrok_dengan_dirinya_sendiri(): void
    {
        $schedule = Schedule::create([
            'student_id' => $this->siswaA->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => '2027-01-04', 'start_time' => '10:00', 'end_time' => '11:30', 'status_schedule' => 'scheduled',
        ]);

        // Edit tanpa mengubah jam/tutor — tidak boleh dianggap bentrok dengan dirinya sendiri.
        $resp = $this->actingAs($this->admin)->putJson("/admin/schedules/{$schedule->id}", [
            'student_id' => $this->siswaA->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'room' => 'Ruang B', 'class_date' => '2027-01-04', 'start_time' => '10:00', 'end_time' => '11:30',
        ]);

        $resp->assertOk()->assertJson(['success' => true]);
        $this->assertSame('Ruang B', $schedule->fresh()->room);
    }

    public function test_edit_jadwal_ditolak_bila_digeser_ke_jam_yang_bentrok_dengan_jadwal_lain(): void
    {
        Schedule::create([
            'student_id' => $this->siswaA->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => '2027-01-04', 'start_time' => '10:00', 'end_time' => '11:30', 'status_schedule' => 'scheduled',
        ]);
        $schedule2 = Schedule::create([
            'student_id' => $this->siswaB->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => '2027-01-04', 'start_time' => '13:00', 'end_time' => '14:30', 'status_schedule' => 'scheduled',
        ]);

        $resp = $this->actingAs($this->admin)->putJson("/admin/schedules/{$schedule2->id}", [
            'student_id' => $this->siswaB->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => '2027-01-04', 'start_time' => '10:30', 'end_time' => '12:00', // digeser, kini overlap jadwal pertama
        ]);

        $resp->assertStatus(422);
        $this->assertSame('13:00', substr($schedule2->fresh()->start_time, 0, 5), 'Jam seharusnya tidak berubah karena ditolak');
    }

    public function test_jadwal_yang_dibatalkan_tidak_dihitung_bentrok(): void
    {
        Schedule::create([
            'student_id' => $this->siswaA->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => '2027-01-04', 'start_time' => '10:00', 'end_time' => '11:30', 'status_schedule' => 'canceled',
        ]);

        $resp = $this->actingAs($this->admin)->postJson('/admin/schedules', [
            'student_id' => $this->siswaB->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => '2027-01-04', 'start_time' => '10:30', 'end_time' => '12:00',
        ]);

        $resp->assertOk()->assertJson(['success' => true]);
    }

    /* ============================================================
       2) Buat Jadwal per Grouping
    ============================================================ */

    public function test_buat_per_grouping_ditolak_bila_tutor_bentrok(): void
    {
        Schedule::create([
            'student_id' => $this->siswaA->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => '2027-01-04', 'start_time' => '10:00', 'end_time' => '11:30', 'status_schedule' => 'scheduled',
        ]);

        $session = ScheduleSession::create(['name' => 'Sesi Pagi', 'time_start' => '10:00', 'time_end' => '11:30']);
        $group = StudentGroup::create(['name' => 'Group Grouping', 'session_id' => $session->id, 'hari' => 'Senin']);
        $group->students()->attach($this->siswaB->id);

        $resp = $this->actingAs($this->admin)->postJson('/admin/schedules/generate-by-group', [
            'student_group_id' => $group->id, 'session_id' => $session->id, 'hari' => 'Senin',
            'subject_id' => $this->subject->id, 'tutor_id' => $this->tutor->id, 'week_date' => '2027-01-04',
        ]);

        $resp->assertStatus(422);
        $this->assertSame(1, Schedule::count(), 'Tidak ada jadwal baru yang boleh terbentuk untuk grouping ini');
    }

    /* ============================================================
       3) Jadwal dari Pola — admin menetapkan tutor (assign)
    ============================================================ */

    public function test_admin_assign_slot_ditolak_bila_tutor_sudah_punya_komitmen_lain_di_jam_sama(): void
    {
        $session = ScheduleSession::create(['name' => 'Sesi Siang', 'time_start' => '13:00', 'time_end' => '14:30']);

        // Tutor sudah punya Schedule manual di jam yang sama (tanggal yang sama).
        Schedule::create([
            'student_id' => $this->siswaA->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => '2027-01-04', 'start_time' => '13:00', 'end_time' => '14:30', 'status_schedule' => 'scheduled',
        ]);

        $group = StudentGroup::create(['name' => 'Group Slot', 'session_id' => $session->id, 'hari' => 'Senin']);
        $group->students()->attach($this->siswaB->id);
        $pattern = SchedulePattern::create(['name' => 'Pola Uji Bentrok', 'is_active' => true]);
        $item = $pattern->items()->create(['hari' => 'Senin', 'session_id' => $session->id, 'subject_id' => $this->subject->id, 'student_group_id' => $group->id]);

        $slot = ScheduleSlot::create([
            'schedule_pattern_item_id' => $item->id, 'week_start' => '2027-01-04', 'class_date' => '2027-01-04',
            'hari' => 'Senin', 'session_id' => $session->id, 'subject_id' => $this->subject->id,
            'student_group_id' => $group->id, 'status' => ScheduleSlot::STATUS_OPEN,
        ]);

        $resp = $this->actingAs($this->admin)->putJson("/admin/schedule-slots/{$slot->id}/assign", ['tutor_id' => $this->tutor->id]);

        $resp->assertStatus(422);
        $slot->refresh();
        $this->assertSame(ScheduleSlot::STATUS_OPEN, $slot->status, 'Slot harus tetap open, tidak jadi assigned');
    }

    public function test_daftar_tutor_pengganti_tidak_menampilkan_tutor_yang_sedang_bentrok(): void
    {
        $session = ScheduleSession::create(['name' => 'Sesi Siang', 'time_start' => '13:00', 'time_end' => '14:30']);

        Schedule::create([
            'student_id' => $this->siswaA->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => '2027-01-04', 'start_time' => '13:00', 'end_time' => '14:30', 'status_schedule' => 'scheduled',
        ]);

        $group = StudentGroup::create(['name' => 'Group Slot', 'session_id' => $session->id, 'hari' => 'Senin']);
        $group->students()->attach($this->siswaB->id);
        $pattern = SchedulePattern::create(['name' => 'Pola Uji Pengganti', 'is_active' => true]);
        $item = $pattern->items()->create(['hari' => 'Senin', 'session_id' => $session->id, 'subject_id' => $this->subject->id, 'student_group_id' => $group->id]);

        $slot = ScheduleSlot::create([
            'schedule_pattern_item_id' => $item->id, 'week_start' => '2027-01-04', 'class_date' => '2027-01-04',
            'hari' => 'Senin', 'session_id' => $session->id, 'subject_id' => $this->subject->id,
            'student_group_id' => $group->id, 'status' => ScheduleSlot::STATUS_VACANT,
        ]);

        $resp = $this->actingAs($this->admin)->getJson("/admin/schedule-slots/{$slot->id}/tutor-pengganti");

        $resp->assertOk();
        $ids = collect($resp->json('tutors'))->pluck('id');
        $this->assertFalse($ids->contains($this->tutor->id), 'Tutor yang sedang bentrok tidak boleh tampil sebagai kandidat pengganti');
        $this->assertTrue($ids->contains($this->tutorLain->id));
    }

    /* ============================================================
       4) Jadwal dari Pola — tutor melamar/konfirmasi sendiri (web)
    ============================================================ */

    public function test_tutor_tidak_bisa_melamar_slot_yang_bentrok_dengan_jadwalnya_sendiri(): void
    {
        $session = ScheduleSession::create(['name' => 'Sesi Siang', 'time_start' => '13:00', 'time_end' => '14:30']);
        $tutorUser = $this->tutorUser($this->tutor, 'tutor-conflict@test.com');

        Schedule::create([
            'student_id' => $this->siswaA->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => '2027-01-04', 'start_time' => '13:00', 'end_time' => '14:30', 'status_schedule' => 'scheduled',
        ]);

        $group = StudentGroup::create(['name' => 'Group Lamar', 'session_id' => $session->id, 'hari' => 'Senin']);
        $group->students()->attach($this->siswaB->id);
        $pattern = SchedulePattern::create(['name' => 'Pola Uji Lamar', 'is_active' => true]);
        $item = $pattern->items()->create(['hari' => 'Senin', 'session_id' => $session->id, 'subject_id' => $this->subject->id, 'student_group_id' => $group->id]);

        $slot = ScheduleSlot::create([
            'schedule_pattern_item_id' => $item->id, 'week_start' => '2027-01-04', 'class_date' => '2027-01-04',
            'hari' => 'Senin', 'session_id' => $session->id, 'subject_id' => $this->subject->id,
            'student_group_id' => $group->id, 'status' => ScheduleSlot::STATUS_OPEN,
        ]);

        $resp = $this->actingAs($tutorUser)->postJson("/tutor/jadwal-tersedia/{$slot->id}/pilih");

        $resp->assertStatus(422);
        $this->assertSame(0, $slot->applications()->count());
    }

    public function test_tutor_tidak_bisa_konfirmasi_slot_bila_muncul_bentrok_baru(): void
    {
        $session = ScheduleSession::create(['name' => 'Sesi Siang', 'time_start' => '13:00', 'time_end' => '14:30']);
        $tutorUser = $this->tutorUser($this->tutor, 'tutor-confirm@test.com');

        $group = StudentGroup::create(['name' => 'Group Konfirmasi', 'session_id' => $session->id, 'hari' => 'Senin']);
        $group->students()->attach($this->siswaB->id);
        $pattern = SchedulePattern::create(['name' => 'Pola Uji Konfirmasi', 'is_active' => true]);
        $item = $pattern->items()->create(['hari' => 'Senin', 'session_id' => $session->id, 'subject_id' => $this->subject->id, 'student_group_id' => $group->id]);

        $slot = ScheduleSlot::create([
            'schedule_pattern_item_id' => $item->id, 'week_start' => '2027-01-04', 'class_date' => '2027-01-04',
            'hari' => 'Senin', 'session_id' => $session->id, 'subject_id' => $this->subject->id,
            'student_group_id' => $group->id, 'status' => ScheduleSlot::STATUS_PENDING_CONFIRMATION,
            'assigned_tutor_id' => $this->tutor->id,
        ]);

        // Admin secara manual menambahkan jadwal lain untuk tutor ini di jam yang sama,
        // SETELAH slot pending_confirmation ini terbentuk.
        Schedule::create([
            'student_id' => $this->siswaA->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => '2027-01-04', 'start_time' => '13:00', 'end_time' => '14:30', 'status_schedule' => 'scheduled',
        ]);

        $resp = $this->actingAs($tutorUser)->putJson("/tutor/jadwal-tersedia/{$slot->id}/konfirmasi");

        $resp->assertStatus(422);
        $this->assertSame(ScheduleSlot::STATUS_PENDING_CONFIRMATION, $slot->fresh()->status);
    }

    /* ============================================================
       5) API mobile tutor — apply/confirm
    ============================================================ */

    public function test_api_tutor_tidak_bisa_melamar_slot_yang_bentrok(): void
    {
        $session = ScheduleSession::create(['name' => 'Sesi Siang', 'time_start' => '13:00', 'time_end' => '14:30']);
        $tutorUser = $this->tutorUser($this->tutor, 'api-tutor-conflict@test.com');
        $token = $tutorUser->createToken('test')->plainTextToken;
        $headers = ['Authorization' => 'Bearer ' . $token];

        Schedule::create([
            'student_id' => $this->siswaA->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => '2027-01-04', 'start_time' => '13:00', 'end_time' => '14:30', 'status_schedule' => 'scheduled',
        ]);

        $group = StudentGroup::create(['name' => 'Group API', 'session_id' => $session->id, 'hari' => 'Senin']);
        $group->students()->attach($this->siswaB->id);
        $pattern = SchedulePattern::create(['name' => 'Pola Uji API', 'is_active' => true]);
        $item = $pattern->items()->create(['hari' => 'Senin', 'session_id' => $session->id, 'subject_id' => $this->subject->id, 'student_group_id' => $group->id]);

        $slot = ScheduleSlot::create([
            'schedule_pattern_item_id' => $item->id, 'week_start' => '2027-01-04', 'class_date' => '2027-01-04',
            'hari' => 'Senin', 'session_id' => $session->id, 'subject_id' => $this->subject->id,
            'student_group_id' => $group->id, 'status' => ScheduleSlot::STATUS_OPEN,
        ]);

        $resp = $this->postJson("/api/tutor/schedule-slots/{$slot->id}/apply", [], $headers);

        $resp->assertStatus(422);
        $this->assertSame(0, $slot->applications()->count());
    }
}
