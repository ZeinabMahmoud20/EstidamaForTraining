<?php

namespace Tests\Feature;

use App\Enums\EvalAnswerType;
use App\Models\CourseSession;
use App\Models\EvaluationForm;
use App\Models\EvaluationItem;
use App\Models\EvaluationSection;
use App\Models\EvaluationSubmission;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guests_are_redirected_from_evaluations(): void
    {
        $this->get(route('evaluations.index'))->assertRedirect(route('login'));
    }

    public function test_users_without_permission_cannot_access_evaluations(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('evaluations.index'))->assertForbidden();
        $this->actingAs($user)->get(route('evaluations.reports.index'))->assertForbidden();
    }

    public function test_evaluation_form_can_be_created_with_sections_and_items(): void
    {
        $this->actingAs($this->actor(['EVALUATION_FORMS_MANAGE']))
            ->post(route('evaluations.store'), $this->formPayload())
            ->assertRedirect();

        $form = EvaluationForm::firstOrFail();
        $this->assertSame('EVAL-TEST-1', $form->code);
        $this->assertTrue($form->is_active);
        $this->assertDatabaseCount('evaluation_sections', 1);
        $this->assertDatabaseCount('evaluation_items', 2);
        $this->assertDatabaseHas('evaluation_items', [
            'item_text' => 'مدى ملاءمة المحتوى',
            'answer_type' => 'scale_1_5',
            'is_required' => true,
        ]);
    }

    public function test_required_item_requires_an_answer_type(): void
    {
        $payload = $this->formPayload();
        $payload['sections'][0]['items'][0]['answer_type'] = '';

        $this->actingAs($this->actor(['EVALUATION_FORMS_MANAGE']))
            ->post(route('evaluations.store'), $payload)
            ->assertSessionHasErrors('sections.0.items.0.answer_type');

        $this->assertDatabaseCount('evaluation_forms', 0);
    }

    public function test_evaluation_form_can_be_updated(): void
    {
        $form = $this->makeForm();
        $section = $form->evaluationSections()->firstOrFail();
        $item = $section->evaluationItems()->firstOrFail();

        $this->actingAs($this->actor(['EVALUATION_FORMS_MANAGE']))
            ->put(route('evaluations.update', $form), [
                'code' => $form->code,
                'title' => 'تقييم محدّث',
                'target_type' => 'program',
                'evaluator_type' => 'trainee',
                'is_anonymous' => 1,
                'is_active' => 1,
                'version' => 2,
                'sections' => [[
                    'id' => $section->id,
                    'title' => 'قسم محدّث',
                    'weight' => 100,
                    'items' => [[
                        'id' => $item->id,
                        'item_text' => 'بند محدّث',
                        'answer_type' => 'scale_1_5',
                        'weight' => 2,
                        'is_required' => 1,
                    ]],
                ]],
            ])
            ->assertRedirect(route('evaluations.edit', $form));

        $this->assertSame('تقييم محدّث', $form->refresh()->title);
        $this->assertSame('قسم محدّث', $section->refresh()->title);
        $this->assertSame('بند محدّث', $item->refresh()->item_text);
        $this->assertSame('2.00', $item->weight);
    }

    public function test_evaluation_submission_calculates_score_and_saves_answers(): void
    {
        $form = $this->makeForm();
        $items = $form->evaluationItems()->orderBy('id')->get();
        $session = CourseSession::factory()->create();
        $evaluator = $this->actor(['EVALUATION_SUBMIT']);

        $this->actingAs($evaluator)
            ->get(route('evaluations.submissions.create', $form))
            ->assertOk()
            ->assertSee('تقييم اختباري');

        $this->actingAs($evaluator)
            ->post(route('evaluations.submissions.store', $form), [
                'session_id' => $session->id,
                'answers' => [
                    $items[0]->id => ['score_value' => 4],
                    $items[1]->id => ['score_value' => 5],
                    $items[2]->id => ['text_value' => 'ملاحظة نصية'],
                ],
            ])
            ->assertRedirect(route('evaluations.index'));

        $submission = EvaluationSubmission::firstOrFail();
        $this->assertSame('90.00', $submission->total_score);
        $this->assertSame($evaluator->id, $submission->evaluator_user_id);
        $this->assertSame(3, $submission->evaluationAnswers()->count());
        $this->assertDatabaseHas('evaluation_answers', [
            'submission_id' => $submission->id,
            'item_id' => $items[2]->id,
            'text_value' => 'ملاحظة نصية',
        ]);
        $this->assertDatabaseHas('evaluation_participation', [
            'form_id' => $form->id,
            'session_id' => $session->id,
            'is_submitted' => true,
        ]);
    }

    public function test_required_evaluation_items_must_be_answered(): void
    {
        $form = $this->makeForm();
        $session = CourseSession::factory()->create();
        $item = $form->evaluationItems()->where('is_required', true)->firstOrFail();

        $this->actingAs($this->actor(['EVALUATION_SUBMIT']))
            ->post(route('evaluations.submissions.store', $form), [
                'session_id' => $session->id,
                'answers' => [],
            ])
            ->assertSessionHasErrors('answers.'.$item->id);

        $this->assertDatabaseCount('evaluation_submissions', 0);
    }

    public function test_form_with_submissions_cannot_be_deleted(): void
    {
        $form = $this->makeForm();
        $session = CourseSession::factory()->create();
        EvaluationSubmission::create([
            'form_id' => $form->id,
            'session_id' => $session->id,
            'evaluator_type' => 'trainee',
            'target_type' => 'program',
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->actor(['EVALUATION_FORMS_MANAGE']))
            ->from(route('evaluations.show', $form))
            ->delete(route('evaluations.destroy', $form))
            ->assertRedirect(route('evaluations.show', $form))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('evaluation_forms', ['id' => $form->id]);
    }

    public function test_report_view_shows_submissions_and_requires_permission(): void
    {
        $form = $this->makeForm();
        $session = CourseSession::factory()->create(['session_code' => 'SES-EVAL-1']);
        $evaluator = User::factory()->create(['full_name' => 'مقيّم اختبار']);
        EvaluationSubmission::create([
            'form_id' => $form->id,
            'session_id' => $session->id,
            'evaluator_type' => 'trainee',
            'evaluator_user_id' => $evaluator->id,
            'target_type' => 'program',
            'total_score' => 85,
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->actor(['EVALUATION_REPORTS_VIEW']))
            ->get(route('evaluations.reports.index'))
            ->assertOk()
            ->assertSee('SES-EVAL-1')
            ->assertSee('85.00%');

        $this->actingAs($this->actor(['EVALUATION_SUBMIT']))
            ->get(route('evaluations.reports.index'))
            ->assertForbidden();
    }

    private function makeForm(): EvaluationForm
    {
        $form = EvaluationForm::create([
            'code' => 'EVAL-READY-1',
            'title' => 'تقييم اختباري',
            'target_type' => 'program',
            'evaluator_type' => 'trainee',
            'is_anonymous' => false,
            'is_active' => true,
            'version' => 1,
        ]);

        $section = EvaluationSection::create([
            'form_id' => $form->id,
            'title' => 'المحتوى',
            'weight' => 100,
            'sort_order' => 1,
        ]);

        foreach ([
            ['item_text' => 'جودة المحتوى', 'answer_type' => EvalAnswerType::Scale15, 'is_required' => true],
            ['item_text' => 'ملاءمة الأنشطة', 'answer_type' => EvalAnswerType::Scale15, 'is_required' => true],
            ['item_text' => 'ملاحظات', 'answer_type' => EvalAnswerType::Text, 'is_required' => false],
        ] as $index => $item) {
            EvaluationItem::create([
                'form_id' => $form->id,
                'section_id' => $section->id,
                'item_text' => $item['item_text'],
                'answer_type' => $item['answer_type'],
                'weight' => 1,
                'is_required' => $item['is_required'],
                'sort_order' => $index + 1,
            ]);
        }

        return $form;
    }

    /** @return array<string, mixed> */
    private function formPayload(): array
    {
        return [
            'code' => 'EVAL-TEST-1',
            'title' => 'تقييم برنامج تدريبي',
            'target_type' => 'program',
            'evaluator_type' => 'trainee',
            'is_anonymous' => 1,
            'is_active' => 1,
            'version' => 1,
            'sections' => [[
                'title' => 'المحتوى',
                'weight' => 100,
                'items' => [
                    [
                        'item_text' => 'مدى ملاءمة المحتوى',
                        'answer_type' => 'scale_1_5',
                        'weight' => 1,
                        'is_required' => 1,
                    ],
                    [
                        'item_text' => 'ملاحظات إضافية',
                        'answer_type' => 'text',
                        'weight' => 1,
                        'is_required' => 0,
                    ],
                ],
            ]],
        ];
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
