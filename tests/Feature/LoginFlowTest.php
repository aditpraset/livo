<?php

namespace Tests\Feature;

use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LoginFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'tutor', 'siswa'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }

    public function test_login_page_shows_email_step(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Masukkan email untuk melanjutkan');
    }

    public function test_unknown_email_is_rejected(): void
    {
        $this->from(route('admin.login'))
            ->post(route('admin.login.check-email'), ['email' => 'tidakada@mail.com'])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'tidakada@mail.com']);
    }

    public function test_admin_with_password_gets_password_step_and_can_login(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => 'rahasia123',
            'role' => 'admin',
            'status' => 'aktif',
        ]);
        $admin->syncRoles(['admin']);

        // Step 1: email dikenal → diarahkan ke step password
        $this->post(route('admin.login.check-email'), ['email' => 'admin@test.com'])
            ->assertRedirect(route('admin.login'));

        $this->get(route('admin.login'))->assertSee('Password');

        // Password salah ditolak
        $this->post(route('admin.login.submit'), ['password' => 'salah'])
            ->assertSessionHasErrors('password');

        // Password benar → masuk dashboard admin
        $this->post(route('admin.login.submit'), ['password' => 'rahasia123'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_tutor_from_master_is_provisioned_and_creates_password(): void
    {
        $tutor = Tutor::create([
            'name' => 'Tutor Uji',
            'email' => 'tutor@test.com',
            'phone' => '0812',
            'specialization' => ['Matematika'],
        ]);

        // Step 1: email ada di master tutor → akun dibuat otomatis (role tutor, tanpa password)
        $this->post(route('admin.login.check-email'), ['email' => 'tutor@test.com'])
            ->assertRedirect(route('admin.login'));

        $user = User::where('email', 'tutor@test.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->password);
        $this->assertSame('tutor', $user->role);
        $this->assertSame($tutor->id, $user->tutor_id);
        $this->assertTrue($user->hasRole('tutor'));

        // Step 2: form buat password ditampilkan
        $this->get(route('admin.login'))->assertSee('buat password');

        // Buat password → langsung login & diarahkan ke dashboard tutor
        $this->post(route('admin.login.create-password'), [
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('tutor.dashboard'));

        $this->assertAuthenticated();
        $this->assertSame('aktif', $user->fresh()->status);
        $this->assertNotNull($user->fresh()->password);
    }

    public function test_create_password_cannot_overwrite_existing_password(): void
    {
        User::factory()->create([
            'email' => 'sudah@test.com',
            'password' => 'lama12345',
            'role' => 'admin',
            'status' => 'aktif',
        ]);

        $this->withSession(['login.email' => 'sudah@test.com'])
            ->post(route('admin.login.create-password'), [
                'password' => 'baru12345',
                'password_confirmation' => 'baru12345',
            ])->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_tutor_cannot_access_admin_area(): void
    {
        $tutorMaster = Tutor::create([
            'name' => 'Tutor X',
            'email' => 'tutorx@test.com',
            'phone' => '0812',
            'specialization' => ['Fisika'],
        ]);

        $user = User::factory()->create([
            'email' => 'tutorx@test.com',
            'password' => 'password123',
            'role' => 'tutor',
            'status' => 'aktif',
            'tutor_id' => $tutorMaster->id,
        ]);
        $user->syncRoles(['tutor']);

        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('tutor.dashboard'))->assertOk();
    }
}
