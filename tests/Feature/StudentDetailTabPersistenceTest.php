<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Halaman Detail Siswa admin memakai Bootstrap tabs (Data Informasi/Jadwal Belajar/Evaluasi)
 * tanpa tracking URL — setiap aksi AJAX di tab "Jadwal Belajar"/"Evaluasi" (Tandai Selesai,
 * Batalkan, Hapus, Simpan Evaluasi) memanggil location.reload(), yang sebelumnya selalu
 * me-reset tampilan balik ke tab pertama ("Data Informasi") alih-alih tetap di tab semula.
 * Test ini memverifikasi halaman masih render normal dan skrip penyimpan tab aktif via
 * URL hash sudah terpasang (perbaikannya murni JS, lihat juga catatan di blade view).
 */
class StudentDetailTabPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_detail_page_renders_with_tab_persistence_script(): void
    {
        foreach (['admin', 'tutor', 'siswa'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $admin->syncRoles(['admin']);

        $student = Student::create(['full_name' => 'Siswa Tab Test', 'program' => 'Matematika', 'quota_sessions' => 5, 'status' => 1]);

        $resp = $this->actingAs($admin)->get(route('admin.students.show', $student));

        $resp->assertOk();
        $resp->assertSee('id="studentTab"', false);
        $resp->assertSee('shown.bs.tab', false);
        $resp->assertSee('location.hash', false);
    }
}
