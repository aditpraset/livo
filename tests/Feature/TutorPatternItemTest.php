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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Halaman Detail Tutor: kelola baris Pola Jadwal mingguan (tambah/ubah/hapus/lepas
 * kepemilikan) — tanpa field tutor di SchedulePatternItem, kepemilikan tetap dinamis
 * lewat ScheduleSlot (sama seperti alur Siapkan Jadwal yang sudah ada).
 */
class TutorPatternItemTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Tutor $tutor;
    private Tutor $tutorLain;
    private Subject $subject;
    private ScheduleSession $session;
    private StudentGroup $group;
    private SchedulePattern $pattern;

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
        $this->tutor = Tutor::create(['name' => 'Tutor Pola Uji', 'phone' => '0811', 'specialization' => ['Matematika']]);
        $this->tutorLain = Tutor::create(['name' => 'Tutor Pola Lain', 'phone' => '0812', 'specialization' => ['Matematika']]);

        $this->session = ScheduleSession::create(['name' => 'Sesi Pagi', 'time_start' => '08:00', 'time_end' => '09:30']);
        $this->group = StudentGroup::create(['name' => 'Group Pola Uji', 'session_id' => $this->session->id, 'hari' => 'Senin']);
        foreach (['Siswa Pola 1', 'Siswa Pola 2'] as $nama) {
            $s = Student::create(['full_name' => $nama, 'grade' => 'SD Kelas 3', 'program' => json_encode(['Matematika']), 'quota_sessions' => 10, 'status' => 1]);
            $this->group->students()->attach($s->id);
        }

        $this->pattern = SchedulePattern::create(['name' => 'Pola Uji Tutor', 'is_active' => true]);
    }

    public function test_tambah_pola_baru_langsung_jadi_milik_tutor_minggu_ini(): void
    {
        $resp = $this->actingAs($this->admin)->postJson("/admin/tutors/{$this->tutor->id}/pattern-items", [
            'schedule_pattern_id' => $this->pattern->id,
            'hari' => 'Senin',
            'session_id' => $this->session->id,
            'subject_id' => $this->subject->id,
            'student_group_id' => $this->group->id,
            'week_start' => now()->startOfWeek(Carbon::MONDAY)->toDateString(),
        ]);

        $resp->assertOk()->assertJson(['success' => true]);

        $item = $this->pattern->items()->first();
        $this->assertNotNull($item);
        $this->assertSame('Senin', $item->hari);

        $slot = ScheduleSlot::where('schedule_pattern_item_id', $item->id)->first();
        $this->assertNotNull($slot);
        $this->assertSame(ScheduleSlot::STATUS_ASSIGNED, $slot->status);
        $this->assertSame($this->tutor->id, $slot->assigned_tutor_id);

        // Jadwal mengajar minggu ini langsung dibuat untuk 2 siswa di group.
        $this->assertSame(2, Schedule::where('schedule_slot_id', $slot->id)->count());

        // Tampil di halaman detail tutor sebagai pola miliknya.
        $this->actingAs($this->admin)->get(route('admin.tutors.show', $this->tutor))
            ->assertOk()
            ->assertSee('Pola Uji Tutor')
            ->assertSee('Group Pola Uji');
    }

    public function test_tambah_pola_ditolak_bila_tutor_bentrok_jam_lain(): void
    {
        $weekStart = now()->startOfWeek(Carbon::MONDAY);
        $siswaLain = Student::create(['full_name' => 'Siswa Bentrok', 'grade' => 'SD Kelas 3', 'program' => json_encode(['Matematika']), 'quota_sessions' => 10, 'status' => 1]);

        Schedule::create([
            'student_id' => $siswaLain->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => $weekStart->toDateString(), 'start_time' => '08:00', 'end_time' => '09:30', 'status_schedule' => 'scheduled',
        ]);

        $resp = $this->actingAs($this->admin)->postJson("/admin/tutors/{$this->tutor->id}/pattern-items", [
            'schedule_pattern_id' => $this->pattern->id,
            'hari' => 'Senin',
            'session_id' => $this->session->id,
            'subject_id' => $this->subject->id,
            'student_group_id' => $this->group->id,
            'week_start' => $weekStart->toDateString(),
        ]);

        $resp->assertStatus(422);
        $this->assertSame(0, $this->pattern->items()->count(), 'Baris pola tidak boleh terbentuk karena bentrok');
    }

    public function test_tambah_pola_ditolak_bila_kombinasi_hari_sesi_group_sudah_ada(): void
    {
        $this->pattern->items()->create([
            'hari' => 'Senin', 'session_id' => $this->session->id,
            'subject_id' => $this->subject->id, 'student_group_id' => $this->group->id,
        ]);

        $resp = $this->actingAs($this->admin)->postJson("/admin/tutors/{$this->tutor->id}/pattern-items", [
            'schedule_pattern_id' => $this->pattern->id,
            'hari' => 'Senin',
            'session_id' => $this->session->id,
            'subject_id' => $this->subject->id,
            'student_group_id' => $this->group->id,
            'week_start' => now()->startOfWeek(Carbon::MONDAY)->toDateString(),
        ]);

        $resp->assertStatus(422);
        $this->assertSame(1, $this->pattern->items()->count());
    }

    public function test_ubah_baris_pola(): void
    {
        $item = $this->pattern->items()->create([
            'hari' => 'Senin', 'session_id' => $this->session->id,
            'subject_id' => $this->subject->id, 'student_group_id' => $this->group->id,
        ]);

        $sesiBaru = ScheduleSession::create(['name' => 'Sesi Siang', 'time_start' => '13:00', 'time_end' => '14:30']);

        $resp = $this->actingAs($this->admin)->putJson("/admin/schedule-pattern-items/{$item->id}", [
            'hari' => 'Rabu',
            'session_id' => $sesiBaru->id,
            'subject_id' => $this->subject->id,
            'student_group_id' => $this->group->id,
        ]);

        $resp->assertOk()->assertJson(['success' => true]);

        $item->refresh();
        $this->assertSame('Rabu', $item->hari);
        $this->assertSame($sesiBaru->id, $item->session_id);
    }

    public function test_ubah_baris_pola_ditolak_bila_bentrok_dengan_baris_lain(): void
    {
        $itemA = $this->pattern->items()->create([
            'hari' => 'Senin', 'session_id' => $this->session->id,
            'subject_id' => $this->subject->id, 'student_group_id' => $this->group->id,
        ]);

        $groupLain = StudentGroup::create(['name' => 'Group Lain', 'session_id' => $this->session->id, 'hari' => 'Senin']);
        $itemB = $this->pattern->items()->create([
            'hari' => 'Selasa', 'session_id' => $this->session->id,
            'subject_id' => $this->subject->id, 'student_group_id' => $groupLain->id,
        ]);

        // Coba ubah itemB supaya sama persis dengan itemA -> harus ditolak (duplikat).
        $resp = $this->actingAs($this->admin)->putJson("/admin/schedule-pattern-items/{$itemB->id}", [
            'hari' => $itemA->hari, 'session_id' => $itemA->session_id,
            'subject_id' => $this->subject->id, 'student_group_id' => $itemA->student_group_id,
        ]);

        $resp->assertStatus(422);
        $this->assertSame('Selasa', $itemB->fresh()->hari);
    }

    public function test_hapus_baris_pola_dari_halaman_detail_tutor(): void
    {
        $item = $this->pattern->items()->create([
            'hari' => 'Senin', 'session_id' => $this->session->id,
            'subject_id' => $this->subject->id, 'student_group_id' => $this->group->id,
        ]);

        $resp = $this->actingAs($this->admin)->deleteJson("/admin/schedule-pattern-items/{$item->id}");

        $resp->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('schedule_pattern_items', ['id' => $item->id]);
    }

    public function test_lepas_dari_tutor_ini_pada_slot_assigned(): void
    {
        $item = $this->pattern->items()->create([
            'hari' => 'Senin', 'session_id' => $this->session->id,
            'subject_id' => $this->subject->id, 'student_group_id' => $this->group->id,
        ]);
        $slot = ScheduleSlot::create([
            'schedule_pattern_item_id' => $item->id, 'week_start' => now()->startOfWeek(Carbon::MONDAY)->toDateString(),
            'class_date' => now()->startOfWeek(Carbon::MONDAY)->toDateString(), 'hari' => 'Senin',
            'session_id' => $this->session->id, 'subject_id' => $this->subject->id, 'student_group_id' => $this->group->id,
            'status' => ScheduleSlot::STATUS_ASSIGNED, 'assigned_tutor_id' => $this->tutor->id, 'assigned_at' => now(),
        ]);

        $resp = $this->actingAs($this->admin)->putJson("/admin/schedule-slots/{$slot->id}/unassign");

        $resp->assertOk();
        $slot->refresh();
        $this->assertSame(ScheduleSlot::STATUS_OPEN, $slot->status);
        $this->assertNull($slot->assigned_tutor_id);

        // Tidak lagi muncul sebagai pola milik tutor ini.
        $this->actingAs($this->admin)->get(route('admin.tutors.show', $this->tutor))
            ->assertOk()
            ->assertSee('Belum ada pola jadwal rutin');
    }

    public function test_pola_yang_dipegang_tutor_lain_tidak_ikut_tampil(): void
    {
        $item = $this->pattern->items()->create([
            'hari' => 'Senin', 'session_id' => $this->session->id,
            'subject_id' => $this->subject->id, 'student_group_id' => $this->group->id,
        ]);
        ScheduleSlot::create([
            'schedule_pattern_item_id' => $item->id, 'week_start' => now()->startOfWeek(Carbon::MONDAY)->toDateString(),
            'class_date' => now()->startOfWeek(Carbon::MONDAY)->toDateString(), 'hari' => 'Senin',
            'session_id' => $this->session->id, 'subject_id' => $this->subject->id, 'student_group_id' => $this->group->id,
            'status' => ScheduleSlot::STATUS_ASSIGNED, 'assigned_tutor_id' => $this->tutorLain->id, 'assigned_at' => now(),
        ]);

        $this->actingAs($this->admin)->get(route('admin.tutors.show', $this->tutor))
            ->assertOk()
            ->assertSee('Belum ada pola jadwal rutin');
    }

    public function test_minggu_depan_tampil_belum_disiapkan_bila_belum_ada_slotnya(): void
    {
        $item = $this->pattern->items()->create([
            'hari' => 'Senin', 'session_id' => $this->session->id,
            'subject_id' => $this->subject->id, 'student_group_id' => $this->group->id,
        ]);
        // Slot hanya ada utk minggu ini -> baris pola tetap "milik" tutor ini (current ownership),
        // tapi utk minggu depan belum ada slotnya sama sekali (belum di-"Siapkan Jadwal").
        ScheduleSlot::create([
            'schedule_pattern_item_id' => $item->id, 'week_start' => now()->startOfWeek(Carbon::MONDAY)->toDateString(),
            'class_date' => now()->startOfWeek(Carbon::MONDAY)->toDateString(), 'hari' => 'Senin',
            'session_id' => $this->session->id, 'subject_id' => $this->subject->id, 'student_group_id' => $this->group->id,
            'status' => ScheduleSlot::STATUS_ASSIGNED, 'assigned_tutor_id' => $this->tutor->id, 'assigned_at' => now(),
        ]);

        $resp = $this->actingAs($this->admin)->get(route('admin.tutors.show', $this->tutor) . '?minggu=depan');

        $resp->assertOk();
        $resp->assertSee('Minggu Depan');
        $resp->assertSee('Belum Disiapkan');
        $resp->assertSee('Group Pola Uji'); // baris polanya tetap tampil, hanya statusnya yang beda
    }

    public function test_tetapkan_ke_tutor_ini_untuk_minggu_depan_membuat_slot_baru(): void
    {
        $item = $this->pattern->items()->create([
            'hari' => 'Senin', 'session_id' => $this->session->id,
            'subject_id' => $this->subject->id, 'student_group_id' => $this->group->id,
        ]);
        ScheduleSlot::create([
            'schedule_pattern_item_id' => $item->id, 'week_start' => now()->startOfWeek(Carbon::MONDAY)->toDateString(),
            'class_date' => now()->startOfWeek(Carbon::MONDAY)->toDateString(), 'hari' => 'Senin',
            'session_id' => $this->session->id, 'subject_id' => $this->subject->id, 'student_group_id' => $this->group->id,
            'status' => ScheduleSlot::STATUS_ASSIGNED, 'assigned_tutor_id' => $this->tutor->id, 'assigned_at' => now(),
        ]);

        $weekDepan = now()->addWeek()->startOfWeek(Carbon::MONDAY);

        $resp = $this->actingAs($this->admin)->postJson(
            "/admin/tutors/{$this->tutor->id}/pattern-items/{$item->id}/assign-week",
            ['week_start' => $weekDepan->toDateString()]
        );

        $resp->assertOk()->assertJson(['success' => true]);

        $slotDepan = ScheduleSlot::where('schedule_pattern_item_id', $item->id)
            ->whereDate('week_start', $weekDepan->toDateString())->first();
        $this->assertNotNull($slotDepan);
        $this->assertSame(ScheduleSlot::STATUS_ASSIGNED, $slotDepan->status);
        $this->assertSame($this->tutor->id, $slotDepan->assigned_tutor_id);
        $this->assertSame(2, Schedule::where('schedule_slot_id', $slotDepan->id)->count());
    }

    public function test_tetapkan_ke_tutor_ini_ditolak_bila_minggu_itu_sudah_terisi_tutor_lain(): void
    {
        $item = $this->pattern->items()->create([
            'hari' => 'Senin', 'session_id' => $this->session->id,
            'subject_id' => $this->subject->id, 'student_group_id' => $this->group->id,
        ]);
        $weekStart = now()->startOfWeek(Carbon::MONDAY);
        ScheduleSlot::create([
            'schedule_pattern_item_id' => $item->id, 'week_start' => $weekStart->toDateString(),
            'class_date' => $weekStart->toDateString(), 'hari' => 'Senin',
            'session_id' => $this->session->id, 'subject_id' => $this->subject->id, 'student_group_id' => $this->group->id,
            'status' => ScheduleSlot::STATUS_ASSIGNED, 'assigned_tutor_id' => $this->tutorLain->id, 'assigned_at' => now(),
        ]);

        $resp = $this->actingAs($this->admin)->postJson(
            "/admin/tutors/{$this->tutor->id}/pattern-items/{$item->id}/assign-week",
            ['week_start' => $weekStart->toDateString()]
        );

        $resp->assertStatus(422);
    }

    public function test_tetapkan_ke_tutor_ini_ditolak_bila_bentrok_jam_lain(): void
    {
        $item = $this->pattern->items()->create([
            'hari' => 'Senin', 'session_id' => $this->session->id,
            'subject_id' => $this->subject->id, 'student_group_id' => $this->group->id,
        ]);
        $weekStart = now()->startOfWeek(Carbon::MONDAY);

        $siswaLain = Student::create(['full_name' => 'Siswa Bentrok 2', 'grade' => 'SD Kelas 3', 'program' => json_encode(['Matematika']), 'quota_sessions' => 10, 'status' => 1]);
        Schedule::create([
            'student_id' => $siswaLain->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => $weekStart->toDateString(), 'start_time' => '08:00', 'end_time' => '09:30', 'status_schedule' => 'scheduled',
        ]);

        $resp = $this->actingAs($this->admin)->postJson(
            "/admin/tutors/{$this->tutor->id}/pattern-items/{$item->id}/assign-week",
            ['week_start' => $weekStart->toDateString()]
        );

        $resp->assertStatus(422);
        $this->assertSame(0, ScheduleSlot::where('schedule_pattern_item_id', $item->id)->count());
    }
}
