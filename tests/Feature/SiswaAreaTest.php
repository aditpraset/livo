<?php

namespace Tests\Feature;

use App\Http\Controllers\Siswa\ModeController;
use App\Models\Evaluation;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SiswaAreaTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;
    private Tutor $tutor;
    private Subject $subject;
    private ?User $siswaUser = null;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'tutor', 'siswa'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->student = Student::create([
            'full_name' => 'Siswa Uji',
            'nickname' => 'Uji',
            'email' => 'siswa@area.test',
            'grade' => 'SMA 10',
            'program' => 'Matematika',
            'quota_sessions' => 5,
        ]);

        $this->tutor = Tutor::create([
            'name' => 'Tutor Uji', 'phone' => '0812', 'specialization' => ['Matematika'],
        ]);

        $this->subject = Subject::create(['subject_name' => 'Matematika']);
    }

    private function siswaUser(): User
    {
        if ($this->siswaUser) {
            return $this->siswaUser;
        }

        $user = User::factory()->create([
            'name' => 'Siswa Uji',
            'email' => 'siswa@area.test',
            'password' => 'password123',
            'role' => 'siswa',
            'status' => 'aktif',
            'student_id' => $this->student->id,
        ]);
        $user->syncRoles(['siswa']);

        return $this->siswaUser = $user;
    }

    /** Login sebagai siswa dengan mode masuk yang sudah dipilih. */
    private function actingAsSiswa(string $mode = 'siswa'): self
    {
        return $this->actingAs($this->siswaUser())
            ->withSession([ModeController::SESSION_KEY => $mode]);
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

    // ── Login ────────────────────────────────────────────────────────────

    public function test_login_provisions_siswa_account_from_master_student(): void
    {
        $this->post(route('admin.login.check-email'), ['email' => 'siswa@area.test'])
            ->assertRedirect(route('admin.login'));

        $user = User::where('email', 'siswa@area.test')->first();
        $this->assertNotNull($user);
        $this->assertSame('siswa', $user->role);
        $this->assertSame($this->student->id, $user->student_id);
        $this->assertTrue($user->hasRole('siswa'));
        $this->assertNull($user->password);
    }

    public function test_siswa_creates_password_then_lands_on_mode_selection(): void
    {
        $this->post(route('admin.login.check-email'), ['email' => 'siswa@area.test'])->assertRedirect();

        // Form buat password ditampilkan untuk akun tanpa password
        $this->get(route('admin.login'))->assertOk()->assertSee('buat password');

        $this->post(route('admin.login.create-password'), [
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('siswa.mode'));

        $this->assertAuthenticated();
        $this->assertSame('aktif', User::where('email', 'siswa@area.test')->first()->status);
    }

    public function test_existing_siswa_logs_in_and_is_sent_to_mode_selection(): void
    {
        $this->siswaUser();

        $this->post(route('admin.login.check-email'), ['email' => 'siswa@area.test'])->assertRedirect();
        $this->post(route('admin.login.submit'), ['password' => 'password123'])
            ->assertRedirect(route('siswa.mode'));

        $this->assertAuthenticated();
    }

    // ── Pilihan mode: Siswa / Orang Tua ──────────────────────────────────

    public function test_mode_page_offers_both_options(): void
    {
        $this->actingAs($this->siswaUser())->get(route('siswa.mode'))
            ->assertOk()
            ->assertSee('Masuk sebagai Siswa')
            ->assertSee('Masuk sebagai Orang Tua');
    }

    public function test_dashboard_is_blocked_until_a_mode_is_chosen(): void
    {
        $this->actingAs($this->siswaUser())->get(route('siswa.dashboard'))
            ->assertRedirect(route('siswa.mode'));

        // Halaman lain di area siswa ikut terjaga
        $this->actingAs($this->siswaUser())->get(route('siswa.evaluations.index'))
            ->assertRedirect(route('siswa.mode'));
    }

    public function test_choosing_siswa_mode_opens_dashboard(): void
    {
        $this->actingAs($this->siswaUser())
            ->post(route('siswa.mode.store'), ['mode' => 'siswa'])
            ->assertRedirect(route('siswa.dashboard'))
            ->assertSessionHas(ModeController::SESSION_KEY, 'siswa');

        $this->actingAsSiswa('siswa')->get(route('siswa.dashboard'))
            ->assertOk()
            ->assertSee('Mode Siswa')
            ->assertSee('Halo,');
    }

    public function test_choosing_orang_tua_mode_switches_the_framing(): void
    {
        $this->actingAs($this->siswaUser())
            ->post(route('siswa.mode.store'), ['mode' => 'orang_tua'])
            ->assertRedirect(route('siswa.dashboard'))
            ->assertSessionHas(ModeController::SESSION_KEY, 'orang_tua');

        $this->actingAsSiswa('orang_tua')->get(route('siswa.dashboard'))
            ->assertOk()
            ->assertSee('Mode Orang Tua')
            ->assertSee('Perkembangan Belajar');
    }

    public function test_invalid_mode_is_rejected(): void
    {
        $this->actingAs($this->siswaUser())
            ->post(route('siswa.mode.store'), ['mode' => 'kepala_sekolah'])
            ->assertSessionHasErrors('mode');

        $this->assertNull(session(ModeController::SESSION_KEY));
    }

    public function test_switching_mode_returns_to_the_choice_page(): void
    {
        $this->actingAsSiswa('siswa')
            ->post(route('siswa.mode.switch'))
            ->assertRedirect(route('siswa.mode'))
            ->assertSessionMissing(ModeController::SESSION_KEY);
    }

    // ── Dashboard & halaman ──────────────────────────────────────────────

    public function test_dashboard_shows_student_summary(): void
    {
        $done = $this->makeSchedule();
        Evaluation::create([
            'schedule_id' => $done->id, 'student_attendance' => 'hadir',
            'post_test' => 90, 'is_published' => true,
        ]);
        $this->makeSchedule(['status_schedule' => 'scheduled', 'class_date' => now()->addDay()->toDateString()]);

        $this->actingAsSiswa()->get(route('siswa.dashboard'))
            ->assertOk()
            ->assertSee('Uji')
            ->assertSee('Sisa Kuota Sesi')
            ->assertSee('Jadwal Terdekat');
    }

    public function test_weekly_schedule_page_lists_own_sessions(): void
    {
        $this->makeSchedule(['status_schedule' => 'scheduled']);

        $this->actingAsSiswa()->get(route('siswa.schedules.week'))
            ->assertOk()
            ->assertSee('Jadwal Belajar')
            ->assertSee('Matematika');
    }

    public function test_siswa_only_sees_published_evaluations(): void
    {
        $published = $this->makeSchedule();
        Evaluation::create([
            'schedule_id' => $published->id, 'student_attendance' => 'hadir',
            'post_test' => 95, 'is_published' => true,
        ]);

        $draft = $this->makeSchedule(['class_date' => now()->subDay()->toDateString()]);
        Evaluation::create([
            'schedule_id' => $draft->id, 'student_attendance' => 'hadir',
            'post_test' => 40, 'is_published' => false,
        ]);

        $this->actingAsSiswa()->get(route('siswa.evaluations.index'))
            ->assertOk()->assertSee('Nilai');

        $data = $this->actingAsSiswa()
            ->getJson(route('siswa.evaluations.data'), ['X-Requested-With' => 'XMLHttpRequest']);
        $data->assertOk()->assertJsonPath('recordsTotal', 1);

        // Nilai dari evaluasi draft tidak boleh bocor ke siswa
        $this->assertStringContainsString('95', $data->getContent());
        $this->assertStringNotContainsString('>40<', $data->getContent());
    }

    public function test_published_evaluations_are_also_visible_in_orang_tua_mode(): void
    {
        $published = $this->makeSchedule();
        Evaluation::create([
            'schedule_id' => $published->id, 'student_attendance' => 'hadir',
            'post_test' => 95, 'is_published' => true,
        ]);

        $this->actingAsSiswa('orang_tua')
            ->getJson(route('siswa.evaluations.data'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('recordsTotal', 1);
    }

    public function test_payment_history_page_and_data(): void
    {
        Payment::create([
            'student_id' => $this->student->id,
            'no_payment' => 'LVR-TEST0001',
            'payment_date' => now()->toDateString(),
            'expired_date' => now()->addDays(30)->toDateString(),
            'category_payment' => 2,
            'description' => 'SPP Bulan Ini',
            'amount' => 350000,
            'payment_method' => 'transfer',
        ]);

        $this->actingAsSiswa()->get(route('siswa.payments.index'))
            ->assertOk()->assertSee('Riwayat Pembayaran')->assertSee('350.000');

        $this->actingAsSiswa()->getJson(route('siswa.payments.data'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('recordsTotal', 1);
    }

    public function test_profile_show_and_update(): void
    {
        $this->actingAsSiswa()->get(route('siswa.profile'))
            ->assertOk()->assertSee('Siswa Uji');

        $this->actingAsSiswa()->put(route('siswa.profile.update'), [
            'phone' => '089999',
            'address' => 'Jl. Baru No. 1',
        ])->assertRedirect(route('siswa.profile'));

        $this->assertSame('089999', $this->student->fresh()->phone);
        $this->assertSame('Jl. Baru No. 1', $this->student->fresh()->address);
    }

    // ── Proteksi akses ───────────────────────────────────────────────────

    public function test_other_roles_cannot_access_siswa_area(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif', 'password' => 'password123']);
        $admin->syncRoles(['admin']);

        $tutorUser = User::factory()->create([
            'role' => 'tutor', 'status' => 'aktif', 'password' => 'password123', 'tutor_id' => $this->tutor->id,
        ]);
        $tutorUser->syncRoles(['tutor']);

        $this->actingAs($admin)->get(route('siswa.dashboard'))->assertForbidden();
        $this->actingAs($admin)->get(route('siswa.mode'))->assertForbidden();
        $this->actingAs($tutorUser)->get(route('siswa.dashboard'))->assertForbidden();
    }

    public function test_siswa_user_without_master_link_gets_403(): void
    {
        $orphan = User::factory()->create(['role' => 'siswa', 'status' => 'aktif', 'password' => 'password123']);
        $orphan->syncRoles(['siswa']);

        $this->actingAs($orphan)->get(route('siswa.mode'))->assertForbidden();
        $this->actingAs($orphan)
            ->withSession([ModeController::SESSION_KEY => 'siswa'])
            ->get(route('siswa.dashboard'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('siswa.dashboard'))->assertRedirect(route('admin.login'));
        $this->get(route('siswa.mode'))->assertRedirect(route('admin.login'));
    }
}
