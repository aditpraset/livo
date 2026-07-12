<?php

namespace Tests\Feature\Api;

use App\Models\Evaluation;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TutorApiTest extends TestCase
{
    use RefreshDatabase;

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
            'name' => 'Tutor API',
            'email' => 'tutor-api@test.com',
            'phone' => '0812',
            'specialization' => ['Matematika'],
            'fee_per_session' => 60000,
        ]);

        $this->student = Student::create([
            'full_name' => 'Siswa API',
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

    public function test_check_email_provisions_tutor_account_without_password(): void
    {
        $this->postJson('/api/tutor/auth/check-email', ['email' => 'tutor-api@test.com'])
            ->assertOk()
            ->assertJson(['email' => 'tutor-api@test.com', 'has_password' => false]);

        $user = User::where('email', 'tutor-api@test.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('tutor', $user->role);
        $this->assertSame($this->tutor->id, $user->tutor_id);
        $this->assertTrue($user->hasRole('tutor'));
    }

    public function test_check_email_rejects_unknown_email(): void
    {
        $this->postJson('/api/tutor/auth/check-email', ['email' => 'siapa@test.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_check_email_rejects_email_belonging_to_admin_role(): void
    {
        $admin = User::factory()->create(['email' => 'admin@test.com', 'password' => 'password123', 'role' => 'admin', 'status' => 'aktif']);
        $admin->syncRoles(['admin']);

        $this->postJson('/api/tutor/auth/check-email', ['email' => 'admin@test.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_create_password_then_login_flow_issues_token(): void
    {
        $this->postJson('/api/tutor/auth/check-email', ['email' => 'tutor-api@test.com'])->assertOk();

        // Buat password pertama kali → langsung dapat token
        $create = $this->postJson('/api/tutor/auth/create-password', [
            'email' => 'tutor-api@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $create->assertOk()->assertJsonStructure(['token', 'token_type', 'user', 'tutor']);

        $user = User::where('email', 'tutor-api@test.com')->first();
        $this->assertSame('aktif', $user->status);
        $this->assertNotNull($user->password);

        // Tidak boleh membuat password lagi setelah punya password
        $this->postJson('/api/tutor/auth/create-password', [
            'email' => 'tutor-api@test.com',
            'password' => 'lainnya123',
            'password_confirmation' => 'lainnya123',
        ])->assertUnprocessable();

        // Login dengan password yang baru dibuat
        $login = $this->postJson('/api/tutor/auth/login', [
            'email' => 'tutor-api@test.com',
            'password' => 'password123',
        ]);
        $login->assertOk()->assertJsonStructure(['token', 'user' => ['role'], 'tutor']);
        $this->assertSame('tutor', $login->json('user.role'));

        // Password salah ditolak
        $this->postJson('/api/tutor/auth/login', [
            'email' => 'tutor-api@test.com',
            'password' => 'salah',
        ])->assertUnprocessable();
    }

    private function authHeaders(): array
    {
        $user = User::where('email', 'tutor-api@test.com')->first()
            ?? User::factory()->create([
                'email' => 'tutor-api@test.com', 'password' => 'password123',
                'role' => 'tutor', 'status' => 'aktif', 'tutor_id' => $this->tutor->id,
            ]);
        $user->syncRoles(['tutor']);
        $token = $user->createToken('test')->plainTextToken;

        return ['Authorization' => 'Bearer ' . $token];
    }

    public function test_me_and_logout(): void
    {
        $headers = $this->authHeaders();

        $this->getJson('/api/tutor/auth/me', $headers)
            ->assertOk()
            ->assertJsonPath('user.email', 'tutor-api@test.com')
            ->assertJsonPath('tutor.name', 'Tutor API');

        $this->postJson('/api/tutor/auth/logout', [], $headers)->assertOk();

        // Guard Sanctum di-cache oleh container Laravel antar panggilan dalam satu test;
        // di request HTTP sungguhan guard selalu baru, jadi ini murni penyesuaian test harness.
        Auth::forgetGuards();

        // Token yang sudah dicabut tidak bisa dipakai lagi
        $this->getJson('/api/tutor/auth/me', $headers)->assertUnauthorized();
    }

    public function test_dashboard_returns_accumulated_stats(): void
    {
        $done = $this->makeSchedule();
        Evaluation::create(['schedule_id' => $done->id, 'student_attendance' => 'hadir', 'post_test' => 88]);
        $this->makeSchedule(['status_schedule' => 'scheduled', 'class_date' => now()->addDay()->toDateString()]);

        $response = $this->getJson('/api/tutor/dashboard', $this->authHeaders());

        $response->assertOk()
            ->assertJsonPath('stats.total_sessions', 1)
            ->assertJsonPath('stats.upcoming_sessions', 1)
            ->assertJsonPath('review.evaluated', 1);
    }

    public function test_weekly_schedule_and_student_ownership(): void
    {
        $this->makeSchedule();
        $other = Student::create(['full_name' => 'Siswa Lain', 'program' => 'IPA', 'quota_sessions' => 3]);

        $week = $this->getJson('/api/tutor/schedules/week', $this->authHeaders());
        $week->assertOk()->assertJsonPath('total', 1);

        $this->getJson('/api/tutor/students/' . $this->student->id, $this->authHeaders())
            ->assertOk()->assertJsonPath('student.full_name', 'Siswa API');

        $this->getJson('/api/tutor/students/' . $other->id, $this->authHeaders())
            ->assertForbidden();

        $this->getJson('/api/tutor/students/' . $this->student->id . '/history', $this->authHeaders())
            ->assertOk()->assertJsonPath('total', 1);
    }

    public function test_pending_evaluation_list_and_store(): void
    {
        $schedule = $this->makeSchedule();
        $headers = $this->authHeaders();

        $this->getJson('/api/tutor/evaluations', $headers)
            ->assertOk()->assertJsonPath('total', 1);

        $this->getJson('/api/tutor/evaluations/' . $schedule->id, $headers)
            ->assertOk()->assertJsonStructure(['schedule', 'syllabi']);

        $store = $this->postJson('/api/tutor/evaluations/' . $schedule->id, [
            'student_attendance' => 'hadir',
            'materi_manual' => 'Aljabar Dasar',
            'post_test' => 90,
        ], $headers);
        $store->assertOk();

        $this->assertDatabaseHas('evaluations', [
            'schedule_id' => $schedule->id,
            'student_attendance' => 'hadir',
            'post_test' => 90,
        ]);
        $this->assertSame(4, $this->student->fresh()->quota_sessions);
    }

    public function test_cannot_evaluate_other_tutors_schedule(): void
    {
        $otherTutor = Tutor::create(['name' => 'Tutor Lain', 'phone' => '08', 'specialization' => ['IPA']]);
        $schedule = $this->makeSchedule(['tutor_id' => $otherTutor->id]);
        $headers = $this->authHeaders();

        $this->getJson('/api/tutor/evaluations/' . $schedule->id, $headers)->assertForbidden();
        $this->postJson('/api/tutor/evaluations/' . $schedule->id, ['student_attendance' => 'hadir'], $headers)
            ->assertForbidden();
    }

    public function test_profile_show_and_update(): void
    {
        $headers = $this->authHeaders();

        $this->getJson('/api/tutor/profile', $headers)
            ->assertOk()->assertJsonPath('tutor.name', 'Tutor API');

        $update = $this->postJson('/api/tutor/profile', [
            'phone' => '089999',
            'no_rekening' => 'BCA 123',
        ], $headers);
        $update->assertOk()->assertJsonPath('tutor.phone', '089999');

        $this->assertSame('089999', $this->tutor->fresh()->phone);
    }

    public function test_rekap_pengajaran_and_fee(): void
    {
        $done = $this->makeSchedule();
        Evaluation::create(['schedule_id' => $done->id, 'student_attendance' => 'hadir', 'post_test' => 90]);
        $this->makeSchedule();
        $headers = $this->authHeaders();

        $this->getJson('/api/tutor/rekap-pengajaran', $headers)
            ->assertOk()->assertJsonPath('stats.done', 2);

        // 2 sesi x Rp 60.000 = Rp 120.000
        $this->getJson('/api/tutor/rekap-fee?year=' . now()->year, $headers)
            ->assertOk()
            ->assertJsonPath('total_sessions', 2)
            ->assertJsonPath('total_fee', 120000);
    }

    public function test_salary_slip_and_summary_pdf_download(): void
    {
        $this->makeSchedule();
        $headers = $this->authHeaders();
        $month = now()->format('Y-m');

        $slip = $this->get('/api/tutor/reports/slip-gaji?month=' . $month, $headers);
        $slip->assertOk();
        $this->assertStringContainsString('application/pdf', $slip->headers->get('content-type'));

        $summary = $this->get('/api/tutor/reports/summary?month=' . $month, $headers);
        $summary->assertOk();
        $this->assertStringContainsString('application/pdf', $summary->headers->get('content-type'));
    }

    public function test_unauthenticated_request_rejected(): void
    {
        $this->getJson('/api/tutor/dashboard')->assertUnauthorized();
    }

    public function test_non_tutor_role_forbidden(): void
    {
        $siswa = User::factory()->create(['role' => 'siswa', 'status' => 'aktif', 'password' => 'password123']);
        $siswa->syncRoles(['siswa']);
        $token = $siswa->createToken('test')->plainTextToken;

        $this->getJson('/api/tutor/dashboard', ['Authorization' => 'Bearer ' . $token])
            ->assertForbidden();
    }

    public function test_tutor_user_without_master_link_gets_403(): void
    {
        $orphan = User::factory()->create(['role' => 'tutor', 'status' => 'aktif', 'password' => 'password123']);
        $orphan->syncRoles(['tutor']);
        $token = $orphan->createToken('test')->plainTextToken;

        $this->getJson('/api/tutor/dashboard', ['Authorization' => 'Bearer ' . $token])
            ->assertForbidden();
    }
}
