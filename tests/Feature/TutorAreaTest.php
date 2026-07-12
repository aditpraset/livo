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

class TutorAreaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tutor $tutor;
    private Student $student;
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'tutor', 'siswa'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->tutor = Tutor::create([
            'name' => 'Tutor Uji',
            'email' => 'tutor@area.test',
            'phone' => '0812',
            'specialization' => ['Matematika'],
            'fee_per_session' => 50000,
        ]);

        $this->user = User::factory()->create([
            'email' => 'tutor@area.test',
            'password' => 'password123',
            'role' => 'tutor',
            'status' => 'aktif',
            'tutor_id' => $this->tutor->id,
        ]);
        $this->user->syncRoles(['tutor']);

        $this->student = Student::create([
            'full_name' => 'Siswa Uji',
            'grade' => 'SMA 10',
            'program' => 'Matematika',
            'quota_sessions' => 5,
        ]);

        $this->subject = Subject::create(['subject_name' => 'Matematika']);
    }

    private function makeSchedule(array $attrs = []): Schedule
    {
        return Schedule::create(array_merge([
            'student_id' => $this->student->id,
            'tutor_id' => $this->tutor->id,
            'subject_id' => $this->subject->id,
            'class_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:30',
            'status_schedule' => 'done',
        ], $attrs));
    }

    public function test_dashboard_shows_accumulated_stats(): void
    {
        $done = $this->makeSchedule();
        Evaluation::create([
            'schedule_id' => $done->id,
            'student_attendance' => 'hadir',
            'post_test' => 80,
        ]);
        $this->makeSchedule(['status_schedule' => 'scheduled', 'class_date' => now()->addDay()->toDateString()]);

        $this->actingAs($this->user)->get(route('tutor.dashboard'))
            ->assertOk()
            ->assertSee('Tutor Uji')
            ->assertSee('Total Sesi Selesai')
            ->assertSee('Review Hasil Penilaian');
    }

    public function test_weekly_schedule_shows_sessions_and_student_link(): void
    {
        $this->makeSchedule(['status_schedule' => 'scheduled']);

        $this->actingAs($this->user)->get(route('tutor.schedules.week'))
            ->assertOk()
            ->assertSee('Jadwal Mingguan')
            ->assertSee('Siswa Uji')
            ->assertSee(route('tutor.students.show', $this->student->id), false);
    }

    public function test_student_detail_accessible_only_for_own_students(): void
    {
        $this->makeSchedule();
        $other = Student::create(['full_name' => 'Siswa Lain', 'program' => 'IPA', 'quota_sessions' => 3]);

        $this->actingAs($this->user)->get(route('tutor.students.show', $this->student->id))
            ->assertOk()->assertSee('Siswa Uji');

        // Riwayat sesi dimuat via DataTables (ajax)
        $this->actingAs($this->user)
            ->get(route('tutor.students.data', $this->student->id), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonFragment(['recordsTotal' => 1]);

        $this->actingAs($this->user)->get(route('tutor.students.show', $other->id))
            ->assertForbidden();
        $this->actingAs($this->user)->get(route('tutor.students.data', $other->id))
            ->assertForbidden();
    }

    public function test_pending_evaluation_list_and_store(): void
    {
        $schedule = $this->makeSchedule(); // done tanpa evaluasi

        $this->actingAs($this->user)->get(route('tutor.evaluations.index'))
            ->assertOk()->assertSee('Evaluasi Siswa');

        // Daftar pending dimuat via DataTables (ajax)
        $data = $this->actingAs($this->user)
            ->get(route('tutor.evaluations.data'), ['X-Requested-With' => 'XMLHttpRequest']);
        $data->assertOk()->assertJsonFragment(['recordsTotal' => 1]);
        $this->assertStringContainsString('Siswa Uji', $data->getContent());

        $this->actingAs($this->user)->get(route('tutor.evaluations.create', $schedule->id))
            ->assertOk()->assertSee('Isi Evaluasi Sesi');

        $this->actingAs($this->user)->post(route('tutor.evaluations.store', $schedule->id), [
            'student_attendance' => 'hadir',
            'materi_manual' => 'Aljabar Dasar',
            'post_test' => 85,
            'pemahaman' => 80,
        ])->assertRedirect(route('tutor.evaluations.index'));

        $this->assertDatabaseHas('evaluations', [
            'schedule_id' => $schedule->id,
            'student_attendance' => 'hadir',
            'post_test' => 85,
        ]);

        // Kehadiran "hadir" memotong kuota sesi siswa
        $this->assertSame(4, $this->student->fresh()->quota_sessions);
    }

    public function test_cannot_evaluate_other_tutors_schedule(): void
    {
        $otherTutor = Tutor::create(['name' => 'Tutor Lain', 'phone' => '08', 'specialization' => ['IPA']]);
        $schedule = $this->makeSchedule(['tutor_id' => $otherTutor->id]);

        $this->actingAs($this->user)->get(route('tutor.evaluations.create', $schedule->id))->assertForbidden();
        $this->actingAs($this->user)->post(route('tutor.evaluations.store', $schedule->id), [
            'student_attendance' => 'hadir',
        ])->assertForbidden();
    }

    public function test_profile_view_and_update(): void
    {
        $this->actingAs($this->user)->get(route('tutor.profile'))
            ->assertOk()->assertSee('Tutor Uji');

        $this->actingAs($this->user)->put(route('tutor.profile.update'), [
            'phone' => '089999',
            'no_rekening' => 'BCA 123',
        ])->assertRedirect(route('tutor.profile'));

        $this->assertSame('089999', $this->tutor->fresh()->phone);
    }

    public function test_profile_photo_upload_and_replace(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        // Upload foto pertama
        $this->actingAs($this->user)->put(route('tutor.profile.update'), [
            'phone' => '0812',
            'photo' => \Illuminate\Http\UploadedFile::fake()->image('foto.jpg', 300, 300),
        ])->assertRedirect(route('tutor.profile'));

        $firstPhoto = $this->tutor->fresh()->photo;
        $this->assertNotNull($firstPhoto);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($firstPhoto);

        // Ganti foto → file lama dihapus, path baru tersimpan
        $this->actingAs($this->user)->put(route('tutor.profile.update'), [
            'phone' => '0812',
            'photo' => \Illuminate\Http\UploadedFile::fake()->image('baru.png', 300, 300),
        ])->assertRedirect(route('tutor.profile'));

        $secondPhoto = $this->tutor->fresh()->photo;
        $this->assertNotSame($firstPhoto, $secondPhoto);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($secondPhoto);
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($firstPhoto);

        // File non-gambar ditolak validasi
        $this->actingAs($this->user)->put(route('tutor.profile.update'), [
            'phone' => '0812',
            'photo' => \Illuminate\Http\UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('photo');
    }

    public function test_rekap_pengajaran_and_fee(): void
    {
        $done = $this->makeSchedule();
        Evaluation::create(['schedule_id' => $done->id, 'student_attendance' => 'hadir', 'post_test' => 90]);
        $this->makeSchedule(); // sesi done kedua

        $this->actingAs($this->user)->get(route('tutor.rekap-pengajaran'))
            ->assertOk()->assertSee('Rekapitulasi Hasil Pengajaran');

        // Tabel rekap dimuat via DataTables (ajax) dengan filter bulan
        $data = $this->actingAs($this->user)
            ->get(route('tutor.rekap-pengajaran.data', ['month' => now()->format('Y-m')]), ['X-Requested-With' => 'XMLHttpRequest']);
        $data->assertOk()->assertJsonFragment(['recordsTotal' => 2]);
        $this->assertStringContainsString('Siswa Uji', $data->getContent());

        // 2 sesi × Rp 50.000 = Rp 100.000
        $this->actingAs($this->user)->get(route('tutor.rekap-fee'))
            ->assertOk()->assertSee('Rekapitulasi Fee')->assertSee('100.000');
    }

    public function test_salary_slip_and_summary_pdf_download(): void
    {
        $this->makeSchedule();

        $month = now()->format('Y-m');

        $slip = $this->actingAs($this->user)->get(route('tutor.reports.slip-gaji', ['month' => $month]));
        $slip->assertOk();
        $this->assertStringContainsString('application/pdf', $slip->headers->get('content-type'));

        $summary = $this->actingAs($this->user)->get(route('tutor.reports.summary', ['month' => $month]));
        $summary->assertOk();
        $this->assertStringContainsString('application/pdf', $summary->headers->get('content-type'));
    }

    public function test_non_tutor_roles_cannot_access_tutor_area(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa', 'status' => 'aktif', 'password' => 'password123']);
        $siswa->syncRoles(['siswa']);

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif', 'password' => 'password123']);
        $admin->syncRoles(['admin']);

        $this->actingAs($siswa)->get(route('tutor.dashboard'))->assertForbidden();
        $this->actingAs($admin)->get(route('tutor.dashboard'))->assertForbidden();
    }

    public function test_tutor_user_without_master_link_gets_403(): void
    {
        $orphan = User::factory()->create(['role' => 'tutor', 'status' => 'aktif', 'password' => 'password123']);
        $orphan->syncRoles(['tutor']);

        $this->actingAs($orphan)->get(route('tutor.dashboard'))->assertForbidden();
    }
}
