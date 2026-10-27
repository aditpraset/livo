<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\PaymentController;
use App\Models\Payment;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Bug yang diperbaiki: "Kuota Sesi" di PDF Reminder SPP sebelumnya diambil mentah-mentah
 * dari payments.quota (field manual, rawan salah/default lupa diubah) alih-alih mengikuti
 * kuota master Program siswa — mis. Program Intensif (kuota 12) tapi PDF menampilkan 8.
 */
class PaymentReminderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'tutor', 'siswa'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $this->admin->syncRoles(['admin']);

        $this->student = Student::create(['full_name' => 'Siswa Reminder', 'program' => 'Matematika', 'quota_sessions' => 3, 'status' => 1]);
    }

    public function test_quota_purchased_follows_master_program_times_period_not_stale_manual_value(): void
    {
        $intensif = Program::create(['program_name' => 'Program Intensif', 'kuota' => 12, 'duration' => 3]);

        // Reproduksi persis bug yang dilaporkan: payment.quota tersimpan 8 (default form lama),
        // padahal master Program Intensif kuotanya 12, dan payment ini untuk 1 bulan.
        $paymentSatuBulan = Payment::create([
            'student_id' => $this->student->id, 'no_payment' => 'X1', 'payment_date' => now(), 'category_payment' => 2,
            'amount' => 100000, 'payment_method' => 'cash', 'quota' => 8, 'period' => 1,
        ]);

        $controller = app(PaymentController::class);

        $this->assertSame(
            12,
            $controller->resolveQuotaPurchased($intensif, $paymentSatuBulan),
            'Kuota harus 12 (master Program × 1 bulan), bukan 8 dari payments.quota yang stale'
        );
    }

    public function test_quota_purchased_multiplies_by_period_for_multi_month_payments(): void
    {
        $intensif = Program::create(['program_name' => 'Program Intensif', 'kuota' => 12, 'duration' => 3]);

        $paymentEnamBulan = Payment::create([
            'student_id' => $this->student->id, 'no_payment' => 'X2', 'payment_date' => now(), 'category_payment' => 2,
            'amount' => 100000, 'payment_method' => 'cash', 'quota' => 999, 'period' => 6,
        ]);

        $controller = app(PaymentController::class);

        $this->assertSame(72, $controller->resolveQuotaPurchased($intensif, $paymentEnamBulan));
    }

    public function test_quota_purchased_falls_back_to_manual_value_when_student_has_no_program(): void
    {
        $paymentTanpaPeriode = Payment::create([
            'student_id' => $this->student->id, 'no_payment' => 'X3', 'payment_date' => now(), 'category_payment' => 2,
            'amount' => 100000, 'payment_method' => 'cash', 'quota' => 15, 'period' => null,
        ]);

        $controller = app(PaymentController::class);

        // Tanpa program (null) ATAU tanpa period, tidak bisa dihitung dari master -> fallback manual.
        $this->assertSame(15, $controller->resolveQuotaPurchased(null, $paymentTanpaPeriode));

        $programTanpaPeriode = Program::create(['program_name' => 'Program X', 'kuota' => 10, 'duration' => 1]);
        $this->assertSame(15, $controller->resolveQuotaPurchased($programTanpaPeriode, $paymentTanpaPeriode));
    }

    public function test_reminder_pdf_endpoint_still_returns_valid_pdf(): void
    {
        $program = Program::create(['program_name' => 'Program Intensif', 'kuota' => 12, 'duration' => 3]);
        $student = Student::create([
            'full_name' => 'Siswa Reminder', 'program' => 'Program Intensif', 'program_id' => $program->id,
            'quota_sessions' => 3, 'status' => 1,
        ]);
        Payment::create([
            'student_id' => $student->id, 'no_payment' => 'X4', 'payment_date' => now(),
            'expired_date' => now()->addMonth(), 'category_payment' => 2, 'amount' => 100000,
            'payment_method' => 'cash', 'quota' => 8, 'period' => 1, 'masa_aktif' => 30,
        ]);

        $resp = $this->actingAs($this->admin)->get(route('admin.payments.reminders.print', $student));

        $resp->assertOk();
        $this->assertStringContainsString('application/pdf', $resp->headers->get('content-type'));
    }
}
