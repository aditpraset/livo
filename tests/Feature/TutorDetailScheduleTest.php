<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\ScheduleSession;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Halaman Detail Tutor (admin): lihat profil + kelola (tambah/ubah/hapus) jadwal mengajarnya. */
class TutorDetailScheduleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Tutor $tutor;
    private Subject $subject;
    private Student $student;

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
        $this->tutor = Tutor::create(['name' => 'Tutor Detail Uji', 'phone' => '0811', 'specialization' => ['Matematika']]);
        $this->student = Student::create(['full_name' => 'Siswa Detail Tutor', 'grade' => 'SMA 10', 'program' => json_encode(['Matematika']), 'quota_sessions' => 10, 'status' => 1]);
    }

    public function test_index_tutor_menampilkan_tautan_detail(): void
    {
        $this->actingAs($this->admin)->get(route('admin.tutors.index'))->assertOk();

        $resp = $this->actingAs($this->admin)->getJson(route('admin.tutors.data'));
        $resp->assertOk();

        $row = collect($resp->json('data'))->firstWhere('id', $this->tutor->id);
        $this->assertNotNull($row);
        $this->assertStringContainsString('bi-eye', $row['action'], 'Tombol Detail (ikon mata) harus ada di kolom aksi');
        $this->assertStringContainsString(route('admin.tutors.show', $this->tutor->id), $row['action']);
    }

    public function test_halaman_detail_tutor_menampilkan_profil_dan_container_datatable(): void
    {
        $resp = $this->actingAs($this->admin)->get(route('admin.tutors.show', $this->tutor));

        $resp->assertOk();
        $resp->assertSee('Tutor Detail Uji');
        $resp->assertSee('id="tutor-schedules-table"', false);
    }

    public function test_jadwal_mengajar_pakai_datatables_serverside_max_10_per_halaman(): void
    {
        foreach (range(1, 12) as $i) {
            Schedule::create([
                'student_id' => $this->student->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
                'class_date' => now()->addDays($i)->toDateString(), 'start_time' => '10:00', 'end_time' => '11:30', 'status_schedule' => 'scheduled',
            ]);
        }

        // Tanpa parameter length -> DataTable client akan minta 10 (pageLength bawaan),
        // tapi server juga harus tunduk ke length yg diminta berapa pun dikirim.
        $resp = $this->actingAs($this->admin)->getJson(
            route('admin.tutors.schedules.data', $this->tutor) . '?draw=1&start=0&length=10'
        );

        $resp->assertOk();
        $this->assertSame(12, $resp->json('recordsTotal'));
        $this->assertSame(12, $resp->json('recordsFiltered'));
        $this->assertCount(10, $resp->json('data'), 'Server harus membatasi hasil sesuai length yg diminta (10)');

        $row = $resp->json('data')[0];
        $this->assertStringContainsString('Siswa Detail Tutor', $row['student_name']);
        $this->assertStringContainsString('Matematika', $row['subject_name']);
        $this->assertStringContainsString('10:00', $row['time']);
    }

    public function test_tambah_jadwal_dari_halaman_detail_tutor(): void
    {
        $resp = $this->actingAs($this->admin)->postJson('/admin/schedules', [
            'student_id' => $this->student->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => '2027-02-02', 'start_time' => '09:00', 'end_time' => '10:30',
        ]);

        $resp->assertOk()->assertJson(['success' => true]);
        $this->assertSame(1, Schedule::where('tutor_id', $this->tutor->id)->count());
    }

    public function test_ubah_jadwal_dari_halaman_detail_tutor(): void
    {
        $schedule = Schedule::create([
            'student_id' => $this->student->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => '2027-02-02', 'start_time' => '09:00', 'end_time' => '10:30', 'status_schedule' => 'scheduled',
        ]);

        $resp = $this->actingAs($this->admin)->putJson("/admin/schedules/{$schedule->id}", [
            'student_id' => $this->student->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'room' => 'Ruang C', 'class_date' => '2027-02-02', 'start_time' => '09:00', 'end_time' => '10:30',
        ]);

        $resp->assertOk()->assertJson(['success' => true]);
        $this->assertSame('Ruang C', $schedule->fresh()->room);
    }

    public function test_hapus_jadwal_dari_halaman_detail_tutor(): void
    {
        $schedule = Schedule::create([
            'student_id' => $this->student->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => '2027-02-02', 'start_time' => '09:00', 'end_time' => '10:30', 'status_schedule' => 'scheduled',
        ]);

        $resp = $this->actingAs($this->admin)->deleteJson("/admin/schedules/{$schedule->id}");

        $resp->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
    }

    public function test_tambah_jadwal_bentrok_tetap_ditolak_dari_halaman_detail_tutor(): void
    {
        Schedule::create([
            'student_id' => $this->student->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => '2027-02-02', 'start_time' => '09:00', 'end_time' => '10:30', 'status_schedule' => 'scheduled',
        ]);

        $siswaLain = Student::create(['full_name' => 'Siswa Lain Tutor', 'grade' => 'SMA 10', 'program' => json_encode(['Matematika']), 'quota_sessions' => 10, 'status' => 1]);

        $resp = $this->actingAs($this->admin)->postJson('/admin/schedules', [
            'student_id' => $siswaLain->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => '2027-02-02', 'start_time' => '09:30', 'end_time' => '11:00',
        ]);

        $resp->assertStatus(422);
        $this->assertSame(1, Schedule::where('tutor_id', $this->tutor->id)->count(), 'Jadwal kedua yang bentrok tidak boleh dibuat');
    }
}
