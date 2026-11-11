<?php

namespace Tests\Feature;

use App\Enums\ExamResultStatus;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Trainee;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guests_are_redirected_from_exams_and_question_banks(): void
    {
        $this->get(route('exams.index'))->assertRedirect(route('login'));
        $this->get(route('question-banks.index'))->assertRedirect(route('login'));
    }

    public function test_users_without_permission_cannot_access_exams_or_question_banks(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('exams.index'))->assertForbidden();
        $this->actingAs($user)->get(route('question-banks.index'))->assertForbidden();
    }

    public function test_grader_can_view_exams_but_cannot_manage_them(): void
    {
        $exam = Exam::create([
            'session_id' => CourseSession::factory()->create()->id,
            'exam_type' => 'final',
            'title' => 'اختبار عرض',
            'total_score' => 100,
        ]);

        $grader = $this->actor(['EXAMS_GRADE']);

        $this->actingAs($grader)->get(route('exams.index'))->assertOk()->assertSee('اختبار عرض');
        $this->actingAs($grader)->get(route('exams.show', $exam))->assertOk()->assertSee('اختبار عرض');
        $this->actingAs($grader)->get(route('exams.create'))->assertForbidden();
    }

    public function test_question_bank_can_be_created_with_nested_questions_and_options(): void
    {
        $course = Course::factory()->create();

        $this->actingAs($this->actor(['QUESTION_BANK_MANAGE']))
            ->post(route('question-banks.store'), [
                'course_id' => $course->id,
                'name' => 'بنك اختبار',
                'questions' => [
                    [
                        'question_text' => 'ما هو الذكاء الاصطناعي؟',
                        'question_type' => 'mcq',
                        'difficulty' => 2,
                        'default_points' => 2,
                        'is_active' => 1,
                        'options' => [
                            ['option_text' => 'فرع من علوم الحاسب', 'is_correct' => 1],
                            ['option_text' => 'لغة برمجة', 'is_correct' => 0],
                        ],
                    ],
                    [
                        'question_text' => 'اشرح مفهوم التعلم العميق.',
                        'question_type' => 'text',
                        'default_points' => 4,
                        'is_active' => 1,
                        'options' => [],
                    ],
                ],
            ])
            ->assertRedirect();

        $bank = QuestionBank::firstOrFail();
        $this->assertSame('بنك اختبار', $bank->name);
        $this->assertDatabaseCount('questions', 2);
        $this->assertDatabaseCount('question_options', 2);
        $this->assertDatabaseHas('question_options', [
            'option_text' => 'فرع من علوم الحاسب',
            'is_correct' => true,
        ]);
    }

    public function test_mcq_requires_options_and_exactly_one_correct_answer(): void
    {
        $course = Course::factory()->create();
        $actor = $this->actor(['QUESTION_BANK_MANAGE']);

        $this->actingAs($actor)
            ->post(route('question-banks.store'), [
                'course_id' => $course->id,
                'name' => 'بنك غير صالح',
                'questions' => [[
                    'question_text' => 'سؤال بلا خيارات',
                    'question_type' => 'mcq',
                    'default_points' => 1,
                    'is_active' => 1,
                    'options' => [],
                ]],
            ])
            ->assertSessionHasErrors('questions.0.options');

        $this->assertDatabaseCount('question_banks', 0);

        $this->actingAs($actor)
            ->post(route('question-banks.store'), [
                'course_id' => $course->id,
                'name' => 'بنك غير صالح',
                'questions' => [[
                    'question_text' => 'سؤال بخيارين صحيحين',
                    'question_type' => 'mcq',
                    'default_points' => 1,
                    'is_active' => 1,
                    'options' => [
                        ['option_text' => 'أ', 'is_correct' => 1],
                        ['option_text' => 'ب', 'is_correct' => 1],
                    ],
                ]],
            ])
            ->assertSessionHasErrors('questions.0.options');
    }

    public function test_question_bank_can_be_updated_and_replaces_options(): void
    {
        $bank = QuestionBank::create([
            'course_id' => Course::factory()->create()->id,
            'name' => 'بنك قديم',
        ]);
        $question = Question::create([
            'bank_id' => $bank->id,
            'question_text' => 'سؤال قديم',
            'question_type' => 'text',
            'default_points' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($this->actor(['QUESTION_BANK_MANAGE']))
            ->put(route('question-banks.update', $bank), [
                'course_id' => $bank->course_id,
                'name' => 'بنك محدّث',
                'questions' => [[
                    'id' => $question->id,
                    'question_text' => 'سؤال محدّث',
                    'question_type' => 'mcq',
                    'default_points' => 3,
                    'is_active' => 1,
                    'options' => [
                        ['option_text' => 'خيار أ', 'is_correct' => 1],
                        ['option_text' => 'خيار ب', 'is_correct' => 0],
                    ],
                ]],
            ])
            ->assertRedirect(route('question-banks.edit', $bank));

        $this->assertSame('بنك محدّث', $bank->refresh()->name);
        $this->assertSame('سؤال محدّث', $question->refresh()->question_text);
        $this->assertSame(2, $question->questionOptions()->count());
    }

    public function test_exam_can_be_created_with_selected_questions_and_points(): void
    {
        $session = CourseSession::factory()->create();
        $questionOne = $this->question($session->course_id, 10);
        $questionTwo = $this->question($session->course_id, 20);

        $this->actingAs($this->actor(['EXAMS_MANAGE']))
            ->post(route('exams.store'), [
                'session_id' => $session->id,
                'exam_type' => 'final',
                'title' => 'الاختبار النهائي',
                'total_score' => 100,
                'pass_score' => 50,
                'weight_pct' => 40,
                'question_ids' => [$questionOne->id, $questionTwo->id],
                'points' => [$questionOne->id => 40, $questionTwo->id => 60],
            ])
            ->assertRedirect();

        $exam = Exam::firstOrFail();
        $this->assertSame('الاختبار النهائي', $exam->title);
        $this->assertSame(2, $exam->questions()->count());
        $this->assertDatabaseHas('exam_questions', [
            'exam_id' => $exam->id,
            'question_id' => $questionOne->id,
            'points' => 40,
        ]);
    }

    public function test_exam_results_can_be_graded_and_pass_fail_is_calculated(): void
    {
        $session = CourseSession::factory()->create();
        $enrollment = Enrollment::factory()->create([
            'session_id' => $session->id,
            'trainee_id' => Trainee::factory()->create()->id,
        ]);
        $exam = Exam::create([
            'session_id' => $session->id,
            'exam_type' => 'final',
            'title' => 'اختبار تصحيح',
            'total_score' => 100,
            'pass_score' => 50,
        ]);
        $gradedBy = $this->actor(['EXAMS_GRADE']);

        $this->actingAs($gradedBy)
            ->post(route('exams.grade', $exam), [
                'results' => [$enrollment->id => ['score' => 80]],
            ])
            ->assertRedirect(route('exams.show', $exam));

        $result = ExamResult::firstOrFail();
        $this->assertSame('80.00', $result->score);
        $this->assertSame('80.00', $result->percentage);
        $this->assertTrue($result->is_passed);
        $this->assertSame(ExamResultStatus::Graded, $result->status);
        $this->assertSame($gradedBy->id, $result->graded_by);

        $this->actingAs($gradedBy)
            ->post(route('exams.grade', $exam), [
                'results' => [$enrollment->id => ['score' => 25]],
            ])
            ->assertRedirect();

        $this->assertFalse($result->refresh()->is_passed);
        $this->assertSame('25.00', $result->percentage);
    }

    public function test_grading_rejects_an_enrollment_from_another_session(): void
    {
        $session = CourseSession::factory()->create();
        $exam = Exam::create([
            'session_id' => $session->id,
            'exam_type' => 'final',
            'total_score' => 100,
        ]);
        $otherEnrollment = Enrollment::factory()->create();

        $this->actingAs($this->actor(['EXAMS_GRADE']))
            ->post(route('exams.grade', $exam), [
                'results' => [$otherEnrollment->id => ['score' => 60]],
            ])
            ->assertSessionHasErrors('results');

        $this->assertDatabaseCount('exam_results', 0);
    }

    public function test_question_bank_used_by_an_exam_cannot_be_deleted(): void
    {
        $session = CourseSession::factory()->create();
        $question = $this->question($session->course_id, 5);
        $exam = Exam::create([
            'session_id' => $session->id,
            'exam_type' => 'final',
            'total_score' => 100,
        ]);
        $exam->questions()->attach($question->id, ['points' => 5]);

        $this->actingAs($this->actor(['QUESTION_BANK_MANAGE']))
            ->from(route('question-banks.index'))
            ->delete(route('question-banks.destroy', $question->bank))
            ->assertRedirect(route('question-banks.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('question_banks', ['id' => $question->bank_id]);
    }

    public function test_exam_with_results_cannot_be_deleted(): void
    {
        $session = CourseSession::factory()->create();
        $enrollment = Enrollment::factory()->create([
            'session_id' => $session->id,
            'trainee_id' => Trainee::factory()->create()->id,
        ]);
        $exam = Exam::create([
            'session_id' => $session->id,
            'exam_type' => 'final',
            'total_score' => 100,
        ]);
        ExamResult::create([
            'exam_id' => $exam->id,
            'enrollment_id' => $enrollment->id,
            'score' => 70,
            'percentage' => 70,
            'is_passed' => true,
            'status' => 'graded',
        ]);

        $this->actingAs($this->actor(['EXAMS_MANAGE']))
            ->from(route('exams.show', $exam))
            ->delete(route('exams.destroy', $exam))
            ->assertRedirect(route('exams.show', $exam))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('exams', ['id' => $exam->id]);
    }

    private function question(int $courseId, float $points): Question
    {
        $bank = QuestionBank::create([
            'course_id' => $courseId,
            'name' => 'بنك '.fake()->unique()->word(),
        ]);

        return Question::create([
            'bank_id' => $bank->id,
            'question_text' => 'سؤال '.fake()->unique()->sentence(),
            'question_type' => 'text',
            'default_points' => $points,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function actor(array $permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }
}
