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

/** Tampilan "Grup Sesi" (per minggu) di halaman Penjadwalan admin — jadwal dikelompokkan per hari & sesi, dengan popup daftar siswa. */
class ScheduleWeeklyGroupedViewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Tutor $tutor;
    private Subject $subject;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'tutor', 'siswa'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $this->admin->syncRoles(['admin']);

        $this->tutor = Tutor::create(['name' => 'Tutor Grup Sesi', 'phone' => '0812', 'specialization' => ['Matematika']]);
        $this->subject = Subject::create(['subject_name' => 'Matematika']);
        $this->student = Student::create([
            'full_name' => 'Siswa Grup Sesi', 'nis' => 'NIS001', 'grade' => 'SD Kelas 3',
            'program' => json_encode(['Matematika']), 'quota_sessions' => 5, 'status' => 1,
        ]);
    }

    public function test_jadwal_minggu_ini_tampil_dikelompokkan_per_hari_dengan_data_siswa_tertanam(): void
    {
        $tuesdayThisWeek = now()->startOfWeek(\Carbon\Carbon::MONDAY)->addDay(); // Selasa

        Schedule::create([
            'student_id' => $this->student->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => $tuesdayThisWeek->toDateString(), 'start_time' => '10:00', 'end_time' => '11:30',
            'status_schedule' => 'scheduled',
        ]);

        $resp = $this->actingAs($this->admin)->get(route('admin.schedules.index'));

        $resp->assertOk();
        $resp->assertSee('Selasa', false);
        $resp->assertSee('Tutor Grup Sesi', false);
        $resp->assertSee('Matematika', false);
        $resp->assertSee('10:00', false);
        // Nama & NIS siswa tertanam langsung di atribut data-siswa (JSON), tanpa request AJAX terpisah.
        $resp->assertSee('Siswa Grup Sesi', false);
        $resp->assertSee('NIS001', false);
    }

    public function test_jadwal_minggu_lalu_tidak_tampil_di_minggu_ini_tapi_tampil_saat_navigasi_ke_minggu_lalu(): void
    {
        $lastWeekMonday = now()->startOfWeek(\Carbon\Carbon::MONDAY)->subWeek();

        Schedule::create([
            'student_id' => $this->student->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => $lastWeekMonday->toDateString(), 'start_time' => '08:00', 'end_time' => '09:00',
            'status_schedule' => 'scheduled',
        ]);

        // Catatan: nama siswa SELALU muncul di dropdown modal "Tambah Jadwal" di halaman yang
        // sama terlepas dari filter minggu, jadi yang diuji adalah NIS — hanya tertanam di
        // atribut data-siswa milik grup sesi yang benar-benar tampil di tabel Grup Sesi minggu itu.

        // Minggu ini (default): jadwal minggu lalu tidak boleh ikut muncul.
        $this->actingAs($this->admin)->get(route('admin.schedules.index'))
            ->assertOk()
            ->assertDontSee('NIS001', false);

        // Navigasi ke minggu lalu via ?week=
        $this->actingAs($this->admin)->get(route('admin.schedules.index', ['week' => $lastWeekMonday->toDateString()]))
            ->assertOk()
            ->assertSee('NIS001', false);
    }

    public function test_filter_tutor_pada_grup_sesi(): void
    {
        $tutorLain = Tutor::create(['name' => 'Tutor Lain', 'phone' => '0813', 'specialization' => ['Matematika']]);
        $tanggal = now()->startOfWeek(\Carbon\Carbon::MONDAY)->addDays(2);

        Schedule::create([
            'student_id' => $this->student->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => $tanggal->toDateString(), 'start_time' => '10:00', 'end_time' => '11:30', 'status_schedule' => 'scheduled',
        ]);
        Schedule::create([
            'student_id' => $this->student->id, 'tutor_id' => $tutorLain->id, 'subject_id' => $this->subject->id,
            'class_date' => $tanggal->toDateString(), 'start_time' => '13:00', 'end_time' => '14:00', 'status_schedule' => 'scheduled',
        ]);

        $resp = $this->actingAs($this->admin)->get(route('admin.schedules.index', ['filter_tutor' => $this->tutor->id]));

        // Nama tutor sendiri muncul di banyak tempat (dropdown lain di halaman yang sama),
        // jadi yang diuji adalah SESI mana yang muncul di tabel Grup Sesi: sesi tutor terpilih
        // (10:00) harus tampil, sesi tutor lain (13:00) harus tersaring/tidak tampil.
        $resp->assertOk();
        $resp->assertSee('10:00', false);
        $resp->assertDontSee('13:00', false);
    }

    public function test_tab_kalender_mengelompokkan_banyak_siswa_di_sesi_sama_jadi_satu_event(): void
    {
        $siswaLain = Student::create([
            'full_name' => 'Siswa Lain', 'nis' => 'NIS002', 'grade' => 'SD Kelas 3',
            'program' => json_encode(['Matematika']), 'quota_sessions' => 5, 'status' => 1,
        ]);
        $tanggal = now()->toDateString();

        Schedule::create([
            'student_id' => $this->student->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => $tanggal, 'start_time' => '10:00', 'end_time' => '11:30', 'status_schedule' => 'scheduled',
        ]);
        Schedule::create([
            'student_id' => $siswaLain->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => $tanggal, 'start_time' => '10:00', 'end_time' => '11:30', 'status_schedule' => 'scheduled',
        ]);

        $events = $this->actingAs($this->admin)->getJson(route('admin.schedules.events'))->json();

        // Dua siswa di sesi+tutor+jam yang sama harus jadi SATU event, bukan dua.
        $this->assertCount(1, $events);
        $this->assertStringContainsString('(2)', $events[0]['title']);
        $this->assertCount(2, $events[0]['extendedProps']['siswa']);
        $this->assertEqualsCanonicalizing(
            ['Siswa Grup Sesi', 'Siswa Lain'],
            array_column($events[0]['extendedProps']['siswa'], 'nama')
        );
    }

    public function test_tab_kalender_status_grup_ikut_status_paling_aktif(): void
    {
        $siswaLain = Student::create([
            'full_name' => 'Siswa Lain', 'nis' => 'NIS002', 'grade' => 'SD Kelas 3',
            'program' => json_encode(['Matematika']), 'quota_sessions' => 5, 'status' => 1,
        ]);
        $tanggal = now()->toDateString();

        // Satu siswa di sesi ini sudah 'done', satu lagi masih 'scheduled' -> grup harus 'scheduled'.
        Schedule::create([
            'student_id' => $this->student->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => $tanggal, 'start_time' => '09:00', 'end_time' => '10:00', 'status_schedule' => 'done',
        ]);
        Schedule::create([
            'student_id' => $siswaLain->id, 'tutor_id' => $this->tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => $tanggal, 'start_time' => '09:00', 'end_time' => '10:00', 'status_schedule' => 'scheduled',
        ]);

        $events = $this->actingAs($this->admin)->getJson(route('admin.schedules.events'))->json();

        $this->assertCount(1, $events);
        $this->assertSame('scheduled', $events[0]['extendedProps']['status']);
        $this->assertSame('#4299e1', $events[0]['color']);
    }
}
