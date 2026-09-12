<?php

namespace Tests\Feature;

use App\Models\Evaluation;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Alur penuh penjadwalan dari sisi admin, memakai endpoint yang sama persis
 * dipanggil browser (termasuk method spoofing POST + _method untuk PUT):
 *
 *   Tambah Jadwal → tampil di kalender → buka detail → Edit → Tandai Selesai
 *   → muncul di reminder "belum dievaluasi" → Isi Evaluasi → kuota siswa terpotong.
 */
class ScheduleLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Student $student;
    private Tutor $tutor;
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        // CSRF di-skip: _token aman karena ikut di body POST yang diparse PHP native.
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

        foreach (['admin', 'tutor', 'siswa'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->admin = User::factory()->create([
            'email' => 'admin@lifecycle.test', 'role' => 'admin', 'status' => 'aktif',
        ]);
        $this->admin->syncRoles(['admin']);

        $this->tutor = Tutor::create([
            'name' => 'Tutor Lifecycle', 'phone' => '0812', 'specialization' => ['Matematika'],
            'kategori' => 'freelance', 'fee_per_session' => 50000,
        ]);
        $this->subject = Subject::create(['subject_name' => 'Matematika']);
        $this->student = Student::create([
            'full_name'      => 'Siswa Lifecycle',
            'grade'          => 'SMA Kelas 10',
            'program'        => json_encode(['Matematika']),
            'quota_sessions' => 5,
            'status'         => 1,
        ]);
    }

    /** Kirim seperti browser: POST + _method (method spoofing Laravel). */
    private function spoof(string $method, string $url, array $data = [])
    {
        return $this->actingAs($this->admin)
            ->post($url, array_merge($data, ['_method' => $method]));
    }

    private function events(): array
    {
        return $this->actingAs($this->admin)
            ->getJson('/admin/schedules/events')->json();
    }

    private function pendingEvalCount(): int
    {
        return (int) $this->actingAs($this->admin)
            ->getJson('/admin/data/administrasi/pending-evaluations?draw=1&start=0&length=10')
            ->json('recordsTotal');
    }

    public function test_alur_penuh_buat_jadwal_sampai_selesai_dan_dievaluasi(): void
    {
        /* ── 1. Tambah Jadwal ─────────────────────────────────────────── */
        $this->actingAs($this->admin)->post('/admin/schedules', [
            'student_id' => $this->student->id,
            'tutor_id'   => $this->tutor->id,
            'subject_id' => $this->subject->id,
            'room'       => 'Ruang A',
            'class_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time'   => '11:30',
        ])->assertOk()->assertJson(['success' => true]);

        $schedule = Schedule::firstOrFail();
        $this->assertSame('scheduled', $schedule->status_schedule, 'Jadwal baru harus berstatus scheduled');
        $this->assertSame(5, $this->student->fresh()->quota_sessions, 'Kuota belum boleh terpotong saat baru dijadwalkan');

        /* ── 2. Tampil di kalender (biru = dijadwalkan) ────────────────── */
        $events = $this->events();
        $this->assertCount(1, $events);
        $this->assertSame('#4299e1', $events[0]['color']);
        $this->assertSame('scheduled', $events[0]['extendedProps']['status']);
        $this->assertFalse($events[0]['extendedProps']['has_eval']);
        $this->assertStringContainsString('Siswa Lifecycle', $events[0]['title']);

        /* ── 3. Klik event → muat detail ───────────────────────────────── */
        $this->actingAs($this->admin)->getJson("/admin/schedules/{$schedule->id}")
            ->assertOk()
            ->assertJsonPath('student.full_name', 'Siswa Lifecycle')
            ->assertJsonPath('tutor.name', 'Tutor Lifecycle');

        /* ── 4. Edit Jadwal (POST + _method=PUT) ───────────────────────── */
        $this->spoof('PUT', "/admin/schedules/{$schedule->id}", [
            'student_id' => $this->student->id,
            'tutor_id'   => $this->tutor->id,
            'subject_id' => $this->subject->id,
            'room'       => 'Ruang B',
            'class_date' => now()->toDateString(),
            'start_time' => '13:00',
            'end_time'   => '14:30',
        ])->assertOk()->assertJson(['success' => true]);

        $schedule->refresh();
        $this->assertSame('Ruang B', $schedule->room);
        $this->assertSame('scheduled', $schedule->status_schedule, 'Edit tidak boleh mengubah status');

        /* ── 5. Tandai Selesai (inti bug yang diperbaiki) ──────────────── */
        $this->spoof('PUT', "/admin/schedules/{$schedule->id}/status", ['status' => 'done'])
            ->assertOk()->assertJson(['success' => true]);

        $this->assertSame('done', $schedule->fresh()->status_schedule, 'Tombol Selesai harus mengubah status jadi done');

        /* ── 6. Kalender ikut berubah jadi hijau ───────────────────────── */
        $events = $this->events();
        $this->assertSame('#2fb344', $events[0]['color']);
        $this->assertSame('done', $events[0]['extendedProps']['status']);
        $this->assertFalse($events[0]['extendedProps']['has_eval']);

        /* ── 7. Masuk reminder "belum dievaluasi" di Dashboard Administrasi ─ */
        $this->assertSame(1, $this->pendingEvalCount(), 'Sesi selesai tanpa evaluasi harus muncul di reminder');

        /* ── 8. Isi Evaluasi (tahap akhir) ─────────────────────────────── */
        $this->actingAs($this->admin)->post('/admin/evaluations', [
            'schedule_id'        => $schedule->id,
            'student_attendance' => 'hadir',
            'materi_manual'      => 'Aljabar dasar',
            'post_test'          => 85,
            'pemahaman'          => 80,
            'kemampuan_analisa'  => 75,
            'kemampuan_hafalan'  => 78,
            'kepercayaan_diri'   => 82,
            'tutor_notes'        => 'Perkembangan baik.',
        ])->assertOk()->assertJson(['success' => true]);

        $eval = Evaluation::where('schedule_id', $schedule->id)->firstOrFail();
        $this->assertSame('hadir', $eval->student_attendance);
        $this->assertSame(85, (int) $eval->post_test);

        /* ── 9. Kuota siswa terpotong tepat 1 ──────────────────────────── */
        $this->assertSame(4, $this->student->fresh()->quota_sessions, 'Kuota harus berkurang 1 setelah dievaluasi hadir');

        /* ── 10. Kalender menandai sudah dievaluasi & hilang dari reminder ─ */
        $events = $this->events();
        $this->assertTrue($events[0]['extendedProps']['has_eval']);
        $this->assertSame(0, $this->pendingEvalCount(), 'Setelah dievaluasi harus hilang dari reminder');
    }

    public function test_jadwal_bisa_dibatalkan_dan_tidak_memotong_kuota(): void
    {
        $this->actingAs($this->admin)->post('/admin/schedules', [
            'student_id' => $this->student->id,
            'tutor_id'   => $this->tutor->id,
            'subject_id' => $this->subject->id,
            'class_date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time'   => '09:00',
        ])->assertOk();

        $schedule = Schedule::firstOrFail();

        $this->spoof('PUT', "/admin/schedules/{$schedule->id}/status", ['status' => 'canceled'])
            ->assertOk();

        $this->assertSame('canceled', $schedule->fresh()->status_schedule);
        $this->assertSame(5, $this->student->fresh()->quota_sessions);
        $this->assertSame('#9ca3af', $this->events()[0]['color'], 'Jadwal batal tampil abu-abu di kalender');
    }

    public function test_evaluasi_izin_tidak_memotong_kuota(): void
    {
        $schedule = Schedule::create([
            'student_id' => $this->student->id, 'tutor_id' => $this->tutor->id,
            'subject_id' => $this->subject->id, 'class_date' => now()->toDateString(),
            'start_time' => '15:00', 'end_time' => '16:00', 'status_schedule' => 'done',
        ]);

        $this->actingAs($this->admin)->post('/admin/evaluations', [
            'schedule_id'        => $schedule->id,
            'student_attendance' => 'izin',
            'materi_manual'      => 'Tidak hadir',
        ])->assertOk();

        $this->assertSame(5, $this->student->fresh()->quota_sessions, 'Kehadiran "izin" tidak memotong kuota');
    }
}
