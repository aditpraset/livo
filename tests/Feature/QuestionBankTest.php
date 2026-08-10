<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Subject;
use App\Models\Syllabus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuestionBankTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Subject $subject;
    private Syllabus $syllabus;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');

        $this->admin = User::factory()->create([
            'role' => 'admin', 'status' => 'aktif', 'password' => 'password123',
        ]);
        $this->admin->syncRoles(['admin']);

        $this->subject = Subject::create(['subject_name' => 'Matematika']);
        $this->syllabus = Syllabus::create([
            'subject_id' => $this->subject->id,
            'pokok_bahasan' => 'Aljabar',
            'sub_pokok_bahasan' => 'Persamaan Linear',
            'jenis_kurikulum' => 'Kurikulum Merdeka',
            'kelas' => 'SMP Kelas 7',
        ]);
    }

    public function test_index_page_loads(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.subjects.syllabi.questions.index', [$this->subject->id, $this->syllabus->id]))
            ->assertOk()
            ->assertSee('Bank Soal');
    }

    public function test_admin_can_create_a_question_with_correct_answer(): void
    {
        $payload = [
            'question' => 'Berapa hasil dari 2x + 3 = 7?',
            'option_a' => 'x = 1',
            'option_b' => 'x = 2',
            'option_c' => 'x = 3',
            'option_d' => 'x = 4',
            'correct_answer' => 'b',
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.subjects.syllabi.questions.store', [$this->subject->id, $this->syllabus->id]), $payload)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('questions', [
            'syllabus_id' => $this->syllabus->id,
            'question' => 'Berapa hasil dari 2x + 3 = 7?',
            'correct_answer' => 'b',
        ]);
    }

    public function test_all_four_options_are_required(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.subjects.syllabi.questions.store', [$this->subject->id, $this->syllabus->id]), [
                'question' => 'Soal tanpa opsi lengkap',
                'option_a' => 'A',
                'option_b' => 'B',
                'option_c' => '',
                'option_d' => 'D',
                'correct_answer' => 'a',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('option_c');
    }

    public function test_correct_answer_must_be_one_of_a_to_d(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.subjects.syllabi.questions.store', [$this->subject->id, $this->syllabus->id]), [
                'question' => 'Soal dengan jawaban tidak valid',
                'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D',
                'correct_answer' => 'e',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('correct_answer');
    }

    public function test_admin_can_update_and_delete_a_question(): void
    {
        $question = Question::create([
            'syllabus_id' => $this->syllabus->id,
            'question' => 'Soal awal',
            'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D',
            'correct_answer' => 'a',
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.subjects.syllabi.questions.update', [$this->subject->id, $this->syllabus->id, $question->id]), [
                'question' => 'Soal diperbarui',
                'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D',
                'correct_answer' => 'd',
            ])
            ->assertOk();

        $this->assertDatabaseHas('questions', ['id' => $question->id, 'question' => 'Soal diperbarui', 'correct_answer' => 'd']);

        $this->actingAs($this->admin)
            ->delete(route('admin.subjects.syllabi.questions.destroy', [$this->subject->id, $this->syllabus->id, $question->id]))
            ->assertOk();

        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
    }

    public function test_data_endpoint_lists_questions_for_datatables(): void
    {
        Question::create([
            'syllabus_id' => $this->syllabus->id,
            'question' => 'Soal 1',
            'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D',
            'correct_answer' => 'a',
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('admin.subjects.syllabi.questions.data', [$this->subject->id, $this->syllabus->id]))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1);
    }

    public function test_deleting_syllabus_cascades_to_its_questions(): void
    {
        $question = Question::create([
            'syllabus_id' => $this->syllabus->id,
            'question' => 'Soal ikut terhapus',
            'option_a' => 'A', 'option_b' => 'B', 'option_c' => 'C', 'option_d' => 'D',
            'correct_answer' => 'a',
        ]);

        $this->syllabus->delete();

        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
    }

    public function test_guest_cannot_access_question_bank(): void
    {
        $this->get(route('admin.subjects.syllabi.questions.index', [$this->subject->id, $this->syllabus->id]))
            ->assertRedirect(route('admin.login'));
    }
}
