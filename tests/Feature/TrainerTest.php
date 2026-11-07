<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Trainer;
use App\Models\TrainingProvider;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guests_are_redirected_from_trainers(): void
    {
        $this->get(route('trainers.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_access_trainers(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('trainers.index'))->assertForbidden();
    }

    public function test_trainers_can_be_listed_and_filtered(): void
    {
        Trainer::factory()->create(['full_name' => 'د. أحمد الداخلي']);
        Trainer::factory()->external()->create(['full_name' => 'م. سارة الخارجية']);

        $this->actingAs($this->actor())
            ->get(route('trainers.index'))
            ->assertOk()
            ->assertSee('د. أحمد الداخلي')
            ->assertSee('م. سارة الخارجية');

        $this->actingAs($this->actor())
            ->get(route('trainers.index', ['trainer_type' => 'external']))
            ->assertOk()
            ->assertSee('م. سارة الخارجية')
            ->assertDontSee('د. أحمد الداخلي');

        $this->actingAs($this->actor())
            ->get(route('trainers.index', ['q' => 'أحمد']))
            ->assertOk()
            ->assertSee('د. أحمد الداخلي')
            ->assertDontSee('م. سارة الخارجية');
    }

    public function test_trainer_can_be_created_with_specialties_certifications_and_experiences(): void
    {
        $provider = TrainingProvider::create(['name' => 'مركز الخبراء']);
        $category = CourseCategory::create(['name' => 'القيادة والإدارة']);

        $payload = $this->validPayload([
            'full_name' => 'د. محمد الخبير',
            'trainer_type' => 'external',
            'provider_id' => $provider->id,
            'specialties' => [$category->id],
            'certifications' => [
                ['title' => 'PMP', 'issuer' => 'PMI', 'issue_date' => '2024-01-01'],
            ],
            'experiences' => [
                ['organization' => 'شركة النجاح', 'position' => 'مدير تدريب', 'from_date' => '2020-01-01'],
            ],
        ]);

        $response = $this->actingAs($this->actor())->post(route('trainers.store'), $payload);

        $trainer = Trainer::where('full_name', 'د. محمد الخبير')->firstOrFail();

        $response->assertRedirect(route('trainers.show', $trainer));
        $this->assertDatabaseHas('trainers', [
            'id' => $trainer->id,
            'trainer_type' => 'external',
            'provider_id' => $provider->id,
        ]);
        $this->assertDatabaseHas('trainer_specialties', [
            'trainer_id' => $trainer->id,
            'category_id' => $category->id,
        ]);
        $this->assertDatabaseHas('trainer_certifications', [
            'trainer_id' => $trainer->id,
            'title' => 'PMP',
            'issuer' => 'PMI',
        ]);
        $this->assertDatabaseHas('trainer_experiences', [
            'trainer_id' => $trainer->id,
            'organization' => 'شركة النجاح',
            'position' => 'مدير تدريب',
        ]);
    }

    public function test_trainer_creation_validates_required_unique_and_enum_fields(): void
    {
        Trainer::factory()->create(['national_id' => '12345678901234']);

        $response = $this->actingAs($this->actor())
            ->post(route('trainers.store'), $this->validPayload([
                'full_name' => '',
                'national_id' => '12345678901234',
                'trainer_type' => 'invalid',
            ]));

        $response->assertSessionHasErrors(['full_name', 'national_id', 'trainer_type']);
        $this->assertDatabaseCount('trainers', 1);
    }

    public function test_trainer_can_be_updated_and_nested_rows_are_synced(): void
    {
        $trainer = Trainer::factory()->create(['full_name' => 'اسم قديم']);
        $trainer->trainerCertifications()->create(['title' => 'شهادة قديمة']);
        $trainer->trainerExperiences()->create(['organization' => 'جهة قديمة']);
        $category = CourseCategory::create(['name' => 'إدارة المشاريع']);

        $payload = $this->validPayload([
            'full_name' => 'اسم محدث',
            'specialties' => [$category->id],
            'certifications' => [
                ['title' => 'شهادة محدثة', 'issuer' => 'جهة الاعتماد', 'expiry_date' => '2028-01-01'],
            ],
            'experiences' => [
                ['organization' => 'جهة محدثة', 'position' => 'مدرب أول', 'to_date' => '2025-01-01'],
            ],
        ]);

        $response = $this->actingAs($this->actor())->put(route('trainers.update', $trainer), $payload);

        $response->assertRedirect(route('trainers.show', $trainer));
        $this->assertDatabaseHas('trainers', ['id' => $trainer->id, 'full_name' => 'اسم محدث']);
        $this->assertDatabaseHas('trainer_specialties', [
            'trainer_id' => $trainer->id,
            'category_id' => $category->id,
        ]);
        $this->assertDatabaseCount('trainer_certifications', 1);
        $this->assertDatabaseHas('trainer_certifications', [
            'trainer_id' => $trainer->id,
            'title' => 'شهادة محدثة',
        ]);
        $this->assertDatabaseMissing('trainer_certifications', [
            'trainer_id' => $trainer->id,
            'title' => 'شهادة قديمة',
        ]);
        $this->assertDatabaseCount('trainer_experiences', 1);
        $this->assertDatabaseHas('trainer_experiences', [
            'trainer_id' => $trainer->id,
            'organization' => 'جهة محدثة',
            'position' => 'مدرب أول',
        ]);
    }

    public function test_trainer_can_be_deleted_with_its_nested_rows(): void
    {
        $trainer = Trainer::factory()->create();
        $category = CourseCategory::create(['name' => 'التخصص']);
        $trainer->courseCategories()->attach($category->id);
        $trainer->trainerCertifications()->create(['title' => 'شهادة']);
        $trainer->trainerExperiences()->create(['organization' => 'خبرة']);

        $response = $this->actingAs($this->actor())->delete(route('trainers.destroy', $trainer));

        $response->assertRedirect(route('trainers.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('trainers', ['id' => $trainer->id]);
        $this->assertDatabaseCount('trainer_certifications', 0);
        $this->assertDatabaseCount('trainer_experiences', 0);
        $this->assertDatabaseCount('trainer_specialties', 0);
    }

    public function test_trainer_linked_to_a_user_account_cannot_be_deleted(): void
    {
        $trainer = Trainer::factory()->create();
        User::factory()->create(['trainer_id' => $trainer->id]);

        $response = $this->actingAs($this->actor())->delete(route('trainers.destroy', $trainer));

        $response->assertRedirect(route('trainers.show', $trainer));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('trainers', ['id' => $trainer->id]);
    }

    public function test_trainer_show_and_edit_pages_render_nested_data(): void
    {
        $trainer = Trainer::factory()->create(['full_name' => 'مدرب العرض']);
        $category = CourseCategory::create(['name' => 'تخصص العرض']);
        $trainer->courseCategories()->attach($category->id);
        $trainer->trainerCertifications()->create(['title' => 'شهادة العرض']);
        $trainer->trainerExperiences()->create(['organization' => 'شركة العرض']);

        $this->actingAs($this->actor())
            ->get(route('trainers.show', $trainer))
            ->assertOk()
            ->assertSee('مدرب العرض')
            ->assertSee('شهادة العرض')
            ->assertSee('شركة العرض')
            ->assertSee('تخصص العرض');

        $this->actingAs($this->actor())
            ->get(route('trainers.edit', $trainer))
            ->assertOk()
            ->assertSee('مدرب العرض')
            ->assertSee('شهادة العرض')
            ->assertSee('شركة العرض')
            ->assertSee('تخصص العرض');
    }

    public function test_course_approval_can_be_added_updated_and_deleted(): void
    {
        $actor = $this->actor(['TRAINER_APPROVE_COURSE']);
        $trainer = Trainer::factory()->create();
        $course = Course::factory()->create();

        $response = $this->actingAs($actor)->post(route('trainers.approvals.store', $trainer), [
            'course_id' => $course->id,
            'is_approved' => 1,
            'notes' => 'اعتماد مبدئي',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('trainer_course_approvals', [
            'trainer_id' => $trainer->id,
            'course_id' => $course->id,
            'is_approved' => 1,
            'approved_by' => $actor->id,
            'notes' => 'اعتماد مبدئي',
        ]);

        $approval = $trainer->trainerCourseApprovals()->firstOrFail();
        $this->assertSame(now()->toDateString(), $approval->approved_at?->toDateString());

        $this->actingAs($actor)->post(route('trainers.approvals.store', $trainer), [
            'course_id' => $course->id,
            'is_approved' => 0,
            'notes' => 'إيقاف الاعتماد',
        ])->assertRedirect();

        $this->assertDatabaseCount('trainer_course_approvals', 1);
        $this->assertDatabaseHas('trainer_course_approvals', [
            'trainer_id' => $trainer->id,
            'course_id' => $course->id,
            'is_approved' => 0,
            'approved_by' => null,
            'approved_at' => null,
            'notes' => 'إيقاف الاعتماد',
        ]);

        $approval = $trainer->trainerCourseApprovals()->firstOrFail();

        $this->actingAs($actor)
            ->delete(route('trainers.approvals.destroy', [$trainer, $approval]))
            ->assertRedirect();

        $this->assertDatabaseCount('trainer_course_approvals', 0);
    }

    public function test_course_approval_requires_approval_permission(): void
    {
        $trainer = Trainer::factory()->create();
        $course = Course::factory()->create();

        $this->actingAs($this->actor(['TRAINERS_MANAGE']))
            ->post(route('trainers.approvals.store', $trainer), [
                'course_id' => $course->id,
                'is_approved' => 1,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('trainer_course_approvals', 0);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function actor(array $permissions = ['TRAINERS_MANAGE']): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'trainer_type' => 'internal',
            'provider_id' => null,
            'branch_id' => null,
            'national_id' => null,
            'full_name' => 'مدرب تجريبي',
            'gender' => 'male',
            'birth_date' => '1990-01-01',
            'phone' => '01000000000',
            'email' => 'trainer@example.com',
            'address' => 'القاهرة',
            'highest_qualification' => 'ماجستير',
            'years_experience' => 5,
            'bio' => 'نبذة عن المدرب',
            'hourly_rate' => 500,
            'bank_account' => null,
            'is_active' => 1,
            'specialties' => [],
            'certifications' => [],
            'experiences' => [],
        ], $overrides);
    }
}
