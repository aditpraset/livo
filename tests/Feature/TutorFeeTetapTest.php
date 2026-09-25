<?php

namespace Tests\Feature;

use App\Http\Controllers\Concerns\ComputesTutorFee;
use App\Models\Evaluation;
use App\Models\Schedule;
use App\Models\ScheduleSession;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Tutor;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Fee tutor TETAP terhadap paket "sesi saja" (TKA Visit, package_id 7).
 *
 * Aturan yang dikunci di sini:
 *  - Sesi TKA Visit TIDAK membebani kuota maks_sesi_tunjangan_per_bulan.
 *  - Sesi TKA Visit tetap dibayar penuh per sesi dengan fee_tka_visit.
 *  - Paket lain tidak dibayar per sesi untuk tutor tetap (sudah tercakup gaji pokok).
 */
class TutorFeeTetapTest extends TestCase
{
    use RefreshDatabase;
    use ComputesTutorFee;

    private const PAKET_SEMI = 6;
    private const PAKET_TKA_VISIT = 7;

    private Subject $subject;
    private ScheduleSession $session;
    private Carbon $bulan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bulan = Carbon::create(2026, 9, 1);
        $this->subject = Subject::create(['subject_name' => 'Matematika']);
        $this->session = ScheduleSession::create(['name' => 'Sesi 1', 'time_start' => '13:00', 'time_end' => '14:30']);

        DB::table('packages')->insert([
            ['id' => self::PAKET_SEMI, 'package_name' => 'Kelas Semi Privat', 'price' => 0,
             'total_sessions' => 0, 'description' => '', 'created_at' => now(), 'updated_at' => now()],
            ['id' => self::PAKET_TKA_VISIT, 'package_name' => 'Kelas TKA Visit', 'price' => 0,
             'total_sessions' => 0, 'description' => '', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function buatSesi(Tutor $tutor, int $paket, int $hariKe): void
    {
        $siswa = Student::create([
            'full_name' => "Siswa p{$paket} h{$hariKe}", 'grade' => 'SMA Kelas 10',
            'package_id' => $paket, 'quota_sessions' => 20, 'status' => 1,
            'program' => json_encode(['Matematika']),
        ]);

        $schedule = Schedule::create([
            'student_id' => $siswa->id, 'tutor_id' => $tutor->id, 'subject_id' => $this->subject->id,
            'class_date' => $this->bulan->copy()->addDays($hariKe)->toDateString(),
            'start_time' => '13:00', 'end_time' => '14:30', 'status_schedule' => 'done',
        ]);

        Evaluation::create(['schedule_id' => $schedule->id, 'student_attendance' => 'hadir']);
    }

    public function test_tka_visit_tidak_membebani_kuota_tapi_tetap_dibayar(): void
    {
        $tutor = Tutor::create([
            'name' => 'Tutor Tetap', 'phone' => '0811', 'specialization' => ['Matematika'],
            'kategori' => 'tetap',
            'gaji_pokok' => 2_000_000, 'tunjangan_per_bulan' => 500_000,
            'maks_sesi_tunjangan_per_bulan' => 2,
            'fee_per_session' => 15_000, 'fee_tka_visit' => 150_000,
            'fee_per_student' => 9_999, 'fee_transport_per_day' => 9_999, // tidak boleh terpakai
        ]);

        // 4 sesi paket biasa (membebani kuota) + 3 sesi TKA Visit (tidak membebani)
        foreach ([0, 1, 2, 3] as $h) {
            $this->buatSesi($tutor, self::PAKET_SEMI, $h);
        }
        foreach ([10, 11, 12] as $h) {
            $this->buatSesi($tutor, self::PAKET_TKA_VISIT, $h);
        }

        $b = $this->tutorFeeForMonth($tutor->fresh(), $this->bulan);
        $tka = collect($b['session_breakdown'])->firstWhere('package_id', self::PAKET_TKA_VISIT);

        // Kuota: 4 sesi biasa − maks 2 = 2 kelebihan. TKA Visit (3 sesi) TIDAK ikut,
        // kalau ikut maka kelebihannya akan jadi 5.
        $this->assertSame(2, $b['extra_session_count'], 'Sesi TKA Visit tidak boleh membebani kuota tunjangan');
        $this->assertSame(30_000.0, (float) $b['fee_extra_session'], '2 sesi kelebihan × Rp 15.000');

        // TKA Visit dibayar penuh dengan tarifnya sendiri
        $this->assertSame(3, $tka['count']);
        $this->assertTrue($tka['session_only']);
        $this->assertSame(150_000.0, (float) $tka['rate'], 'Harus memakai fee_tka_visit, bukan fee_per_session');
        $this->assertSame(450_000.0, (float) $tka['subtotal']);

        // Paket biasa tidak dibayar per sesi untuk tutor tetap
        $semi = collect($b['session_breakdown'])->firstWhere('package_id', self::PAKET_SEMI);
        $this->assertSame(0.0, (float) $semi['subtotal'], 'Paket biasa sudah tercakup gaji pokok');

        // Fee per siswa & transport tetap nol untuk tutor tetap
        $this->assertSame(0.0, (float) $b['fee_regular']);
        $this->assertSame(0.0, (float) $b['fee_transport']);

        // Total = 2.000.000 + 500.000 + 30.000 + 450.000
        $this->assertSame(2_980_000.0, (float) $b['total']);
    }

    public function test_freelance_tidak_berubah_perilakunya(): void
    {
        $tutor = Tutor::create([
            'name' => 'Tutor Freelance', 'phone' => '0812', 'specialization' => ['Matematika'],
            'kategori' => 'freelance',
            'fee_per_session' => 15_000, 'fee_tka_visit' => 150_000,
            'fee_per_student' => 1_000, 'fee_transport_per_day' => 5_000,
        ]);

        $this->buatSesi($tutor, self::PAKET_SEMI, 0);
        $this->buatSesi($tutor, self::PAKET_TKA_VISIT, 1);

        $b = $this->tutorFeeForMonth($tutor->fresh(), $this->bulan);
        $tka = collect($b['session_breakdown'])->firstWhere('package_id', self::PAKET_TKA_VISIT);

        $this->assertSame(150_000.0, (float) $tka['rate'], 'Freelance tetap memakai fee_tka_visit');
        // (b) = sesi semi 15.000 + TKA Visit 150.000
        $this->assertSame(165_000.0, (float) $b['fee_session']);
        // fee per siswa hanya siswa non-"sesi saja" → 1 siswa × 1.000
        $this->assertSame(1, $b['regular_count'], 'Siswa TKA Visit tidak menambah fee per siswa');
        $this->assertSame(1_000.0, (float) $b['fee_regular']);
        // transport: hanya hari yang ada siswa non-"sesi saja" → 1 hari
        $this->assertSame(1, $b['day_count']);
        $this->assertSame(0, $b['extra_session_count'], 'Freelance tidak punya kuota tunjangan');
    }
}
