<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regresi: tombol "Selesai" pada penjadwalan bisa diklik tapi status tidak berubah.
 *
 * Penyebab: AJAX mengirim HTTP PUT dengan body form-urlencoded. Sebagian web server /
 * WAF tidak meneruskan body PUT ke PHP (atau mengubah Content-Type-nya), sehingga field
 * `status` hilang → 422 "The status field is required." dan status tidak pernah berubah.
 *
 * Perbaikan: seluruh AJAX PUT/PATCH/DELETE dikirim sebagai POST + _method (method
 * spoofing Laravel) lewat $.ajaxPrefilter global di layout. Test ini mengunci bahwa
 * bentuk request tsb memang diterima route dan benar-benar mengubah data.
 */
class ScheduleStatusSpoofTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Schedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        // Fokus test: routing + method spoofing + persistensi. CSRF sendiri aman di
        // browser karena _token ikut di body POST yang diparse PHP secara native —
        // justru itulah alasan spoofing dipakai (body PUT bisa hilang di server tertentu).
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

        foreach (['admin', 'tutor', 'siswa'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->admin = User::factory()->create([
            'email'  => 'admin@spoof.test',
            'role'   => 'admin',
            'status' => 'aktif',
        ]);
        $this->admin->syncRoles(['admin']);

        $tutor = Tutor::create([
            'name' => 'Tutor Uji', 'phone' => '0812', 'specialization' => ['Matematika'],
        ]);
        $student = Student::create([
            'full_name' => 'Siswa Uji', 'grade' => 'SMA Kelas 10', 'program' => 'Matematika',
        ]);
        $subject = Subject::create(['subject_name' => 'Matematika']);

        $this->schedule = Schedule::create([
            'student_id'      => $student->id,
            'tutor_id'        => $tutor->id,
            'subject_id'      => $subject->id,
            'class_date'      => now()->toDateString(),
            'start_time'      => '10:00',
            'end_time'        => '11:30',
            'status_schedule' => 'scheduled',
        ]);
    }

    public function test_post_dengan_method_spoofing_put_mengubah_status_jadi_done(): void
    {
        $this->actingAs($this->admin)
            ->post("/admin/schedules/{$this->schedule->id}/status", [
                '_method' => 'PUT',
                'status'  => 'done',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('done', $this->schedule->fresh()->status_schedule);
    }

    public function test_post_dengan_method_spoofing_put_bisa_membatalkan_jadwal(): void
    {
        $this->actingAs($this->admin)
            ->post("/admin/schedules/{$this->schedule->id}/status", [
                '_method' => 'PUT',
                'status'  => 'canceled',
            ])
            ->assertOk();

        $this->assertSame('canceled', $this->schedule->fresh()->status_schedule);
    }

    /** Inilah error yang muncul di layar user ketika body PUT tidak sampai ke server. */
    public function test_status_kosong_ditolak_422_dan_status_tidak_berubah(): void
    {
        $this->actingAs($this->admin)
            ->postJson("/admin/schedules/{$this->schedule->id}/status", ['_method' => 'PUT'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->assertSame('scheduled', $this->schedule->fresh()->status_schedule);
    }

    public function test_delete_via_spoofing_juga_terarah_ke_route_destroy(): void
    {
        $this->actingAs($this->admin)
            ->post("/admin/schedules/{$this->schedule->id}", ['_method' => 'DELETE'])
            ->assertOk();

        $this->assertNull(Schedule::find($this->schedule->id));
    }
}
