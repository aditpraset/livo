<?php

namespace Tests\Feature\Api;

use App\Models\ScheduleSession;
use App\Models\SchedulePattern;
use App\Models\SchedulePatternItem;
use App\Models\ScheduleSlot;
use App\Models\ScheduleSlotApplication;
use App\Models\Student;
use App\Models\StudentGroup;
use App\Models\Subject;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Jadwal dari pola (API mobile): konfirmasi mingguan & pengambilan jadwal baru. */
class TutorScheduleSlotApiTest extends TestCase
{
    use RefreshDatabase;

    private Tutor $tutor;
    private Subject $subject;
    private StudentGroup $group;
    private SchedulePatternItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'tutor', 'siswa'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->subject = Subject::create(['subject_name' => 'Matematika']);
        $this->tutor = Tutor::create([
            'name' => 'Tutor Slot API', 'phone' => '0812', 'specialization' => ['Matematika'],
        ]);

        $session = ScheduleSession::create(['name' => 'Sesi Sore', 'time_start' => '16:00', 'time_end' => '17:30']);
        $this->group = StudentGroup::create(['name' => 'Group API', 'session_id' => $session->id, 'hari' => 'Senin']);
        foreach (['Siswa 1', 'Siswa 2'] as $nama) {
            $siswa = Student::create(['full_name' => $nama, 'grade' => 'SD Kelas 3', 'program' => 'Matematika', 'quota_sessions' => 10, 'status' => 1]);
            $this->group->students()->attach($siswa->id);
        }

        $pattern = SchedulePattern::create(['name' => 'Pola API', 'is_active' => true]);
        $this->item = $pattern->items()->create([
            'hari' => 'Senin', 'session_id' => $session->id,
            'subject_id' => $this->subject->id, 'student_group_id' => $this->group->id,
        ]);
    }

    private function authHeaders(Tutor $tutor, string $email): array
    {
        $user = User::factory()->create([
            'email' => $email, 'role' => 'tutor', 'status' => 'aktif', 'tutor_id' => $tutor->id,
        ]);
        $user->syncRoles(['tutor']);
        $token = $user->createToken('test')->plainTextToken;

        return ['Authorization' => 'Bearer ' . $token];
    }

    private function slot(array $attrs = []): ScheduleSlot
    {
        return ScheduleSlot::create(array_merge([
            'schedule_pattern_item_id' => $this->item->id,
            'week_start' => '2027-03-01',
            'class_date' => '2027-03-01',
            'hari' => 'Senin',
            'session_id' => $this->item->session_id,
            'subject_id' => $this->item->subject_id,
            'student_group_id' => $this->item->student_group_id,
            'status' => ScheduleSlot::STATUS_OPEN,
        ], $attrs));
    }

    public function test_unauthenticated_request_rejected(): void
    {
        $this->getJson('/api/tutor/schedule-slots')->assertUnauthorized();
    }

    public function test_index_lists_needs_confirmation_and_available_slots(): void
    {
        $headers = $this->authHeaders($this->tutor, 'slot-a@test.com');

        $pending = $this->slot([
            'status' => ScheduleSlot::STATUS_PENDING_CONFIRMATION,
            'assigned_tutor_id' => $this->tutor->id,
        ]);

        // Pola item kedua — satu slot per pattern item per minggu (constraint unik asli).
        $item2 = $this->item->pattern->items()->create([
            'hari' => 'Rabu', 'session_id' => $this->item->session_id,
            'subject_id' => $this->item->subject_id, 'student_group_id' => $this->item->student_group_id,
        ]);
        $open = $this->slot(['schedule_pattern_item_id' => $item2->id, 'class_date' => '2027-03-03', 'hari' => 'Rabu']);

        $resp = $this->getJson('/api/tutor/schedule-slots?week=2027-03-01', $headers)
            ->assertOk()
            ->assertJsonStructure(['week_start', 'week_end', 'prev_week', 'next_week', 'needs_confirmation', 'available', 'decisions']);

        $ids = collect($resp->json('needs_confirmation'))->pluck('id');
        $this->assertTrue($ids->contains($pending->id));

        $availableIds = collect($resp->json('available'))->pluck('id');
        $this->assertTrue($availableIds->contains($open->id));
    }

    public function test_tutor_cannot_apply_outside_specialization(): void
    {
        $lain = Subject::create(['subject_name' => 'Fisika']);
        $slot = $this->slot(['subject_id' => $lain->id]);

        $headers = $this->authHeaders($this->tutor, 'slot-b@test.com');

        $this->postJson("/api/tutor/schedule-slots/{$slot->id}/apply", [], $headers)
            ->assertStatus(422);
    }

    public function test_apply_then_withdraw(): void
    {
        $slot = $this->slot();
        $headers = $this->authHeaders($this->tutor, 'slot-c@test.com');

        $this->postJson("/api/tutor/schedule-slots/{$slot->id}/apply", ['note' => 'siap'], $headers)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('schedule_slot_applications', [
            'schedule_slot_id' => $slot->id, 'tutor_id' => $this->tutor->id, 'status' => ScheduleSlotApplication::STATUS_PENDING,
        ]);

        $this->putJson("/api/tutor/schedule-slots/{$slot->id}/withdraw", [], $headers)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('schedule_slot_applications', [
            'schedule_slot_id' => $slot->id, 'tutor_id' => $this->tutor->id, 'status' => ScheduleSlotApplication::STATUS_WITHDRAWN,
        ]);
    }

    public function test_confirm_materializes_real_schedules(): void
    {
        $slot = $this->slot([
            'status' => ScheduleSlot::STATUS_PENDING_CONFIRMATION,
            'assigned_tutor_id' => $this->tutor->id,
        ]);
        $headers = $this->authHeaders($this->tutor, 'slot-d@test.com');

        $resp = $this->putJson("/api/tutor/schedule-slots/{$slot->id}/confirm", [], $headers)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(2, $resp->json('created')); // 2 siswa aktif di group
        $slot->refresh();
        $this->assertSame(ScheduleSlot::STATUS_ASSIGNED, $slot->status);
        $this->assertSame(2, \App\Models\Schedule::where('schedule_slot_id', $slot->id)->count());
    }

    public function test_izin_keeps_ownership_for_next_week(): void
    {
        $slot = $this->slot([
            'status' => ScheduleSlot::STATUS_PENDING_CONFIRMATION,
            'assigned_tutor_id' => $this->tutor->id,
        ]);
        $headers = $this->authHeaders($this->tutor, 'slot-e@test.com');

        $this->putJson("/api/tutor/schedule-slots/{$slot->id}/izin", [], $headers)
            ->assertOk()
            ->assertJson(['success' => true]);

        $slot->refresh();
        $this->assertSame(ScheduleSlot::STATUS_VACANT, $slot->status);
        $this->assertSame($this->tutor->id, $slot->assigned_tutor_id, 'kepemilikan pola harus tetap, bukan dihapus');
        $this->assertNotNull($slot->izin_at);
    }

    public function test_confirm_rejects_slot_not_owned_by_tutor(): void
    {
        $lain = Tutor::create(['name' => 'Tutor Lain', 'phone' => '0813', 'specialization' => ['Matematika']]);
        $slot = $this->slot([
            'status' => ScheduleSlot::STATUS_PENDING_CONFIRMATION,
            'assigned_tutor_id' => $lain->id,
        ]);
        $headers = $this->authHeaders($this->tutor, 'slot-f@test.com');

        $this->putJson("/api/tutor/schedule-slots/{$slot->id}/confirm", [], $headers)
            ->assertStatus(422);
    }
}
