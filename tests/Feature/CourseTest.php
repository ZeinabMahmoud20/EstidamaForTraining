<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseSession;
use App\Models\CourseType;
use App\Models\CriteriaType;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guests_are_redirected_from_courses(): void
    {
        $this->get(route('courses.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_access_courses(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('courses.index'))->assertForbidden();
    }

    public function test_view_only_user_can_list_courses_but_cannot_create(): void
    {
        $user = $this->actor(['COURSES_VIEW']);
        Course::factory()->create(['title' => 'دورة القيادة']);

        $this->actingAs($user)
            ->get(route('courses.index'))
            ->assertOk()
            ->assertSee('دورة القيادة');

        $this->actingAs($user)->get(route('courses.create'))->assertForbidden();
    }

    public function test_super_admin_can_access_courses_without_direct_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user)->get(route('courses.index'))->assertOk();
    }

    public function test_course_can_be_created_with_nested_rows(): void
    {
        $category = CourseCategory::create(['name' => 'الإدارة والقيادة']);
        $type = CourseType::create(['name' => 'دورة تدريبية']);
        $criteriaType = CriteriaType::create(['code' => 'MIN_EXPERIENCE', 'name' => 'الحد الأدنى للخبرة']);
        $prerequisite = Course::factory()->create();

        $payload = $this->validPayload([
            'code' => 'CRS-101',
            'title' => 'إدارة المشاريع الاحترافية',
            'category_id' => $category->id,
            'course_type_id' => $type->id,
            'modules' => [
                ['title' => 'مقدمة عن إدارة المشاريع', 'description' => 'تعريف بالمفاهيم', 'hours' => 3, 'sort_order' => 1],
                ['title' => 'التخطيط والتنفيذ', 'hours' => 4, 'sort_order' => 2],
            ],
            'criteria' => [
                ['criteria_type_id' => $criteriaType->id, 'operator' => 'gte', 'value' => '2', 'is_mandatory' => 1],
            ],
            'prerequisites' => [$prerequisite->id],
        ]);

        $response = $this->actingAs($this->actor())->post(route('courses.store'), $payload);

        $course = Course::where('code', 'CRS-101')->firstOrFail();

        $response->assertRedirect(route('courses.show', $course));
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'إدارة المشاريع الاحترافية',
            'level' => 'beginner',
        ]);
        $this->assertDatabaseCount('course_modules', 2);
        $this->assertDatabaseHas('course_criteria', [
            'course_id' => $course->id,
            'criteria_type_id' => $criteriaType->id,
            'operator' => 'gte',
            'value' => '2',
        ]);
        $this->assertDatabaseHas('course_prerequisites', [
            'course_id' => $course->id,
            'prerequisite_course_id' => $prerequisite->id,
        ]);
    }

    public function test_course_creation_validates_required_fields_and_unique_code(): void
    {
        Course::factory()->create(['code' => 'CRS-200']);

        $response = $this->actingAs($this->actor())
            ->post(route('courses.store'), $this->validPayload([
                'code' => 'CRS-200',
                'title' => '',
            ]));

        $response->assertSessionHasErrors(['code', 'title']);
        $this->assertDatabaseCount('courses', 1);
    }

    public function test_course_can_be_updated_and_nested_rows_are_synced(): void
    {
        $course = Course::factory()->create(['title' => 'اسم قديم']);
        $module = $course->courseModules()->create(['title' => 'وحدة قديمة', 'sort_order' => 1]);
        $criteriaType = CriteriaType::create(['code' => 'JOB_TITLE', 'name' => 'المسمى الوظيفي']);

        $payload = $this->validPayload([
            'code' => $course->code,
            'title' => 'اسم محدث',
            'modules' => [
                ['id' => $module->id, 'title' => 'وحدة محدثة', 'hours' => 2, 'sort_order' => 1],
                ['title' => 'وحدة جديدة', 'hours' => 3, 'sort_order' => 2],
            ],
            'criteria' => [
                ['criteria_type_id' => $criteriaType->id, 'operator' => 'eq', 'value' => 'مدير', 'is_mandatory' => 0],
            ],
        ]);

        $response = $this->actingAs($this->actor())->put(route('courses.update', $course), $payload);

        $response->assertRedirect(route('courses.show', $course));
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'title' => 'اسم محدث']);
        $this->assertDatabaseHas('course_modules', ['id' => $module->id, 'title' => 'وحدة محدثة']);
        $this->assertDatabaseCount('course_modules', 2);
        $this->assertDatabaseHas('course_criteria', [
            'course_id' => $course->id,
            'operator' => 'eq',
            'value' => 'مدير',
            'is_mandatory' => 0,
        ]);
    }

    public function test_course_cannot_be_its_own_prerequisite(): void
    {
        $course = Course::factory()->create();

        $response = $this->actingAs($this->actor())->put(
            route('courses.update', $course),
            $this->validPayload([
                'code' => $course->code,
                'title' => $course->title,
                'prerequisites' => [$course->id],
            ])
        );

        $response->assertSessionHasErrors('prerequisites.0');
    }

    public function test_course_with_sessions_cannot_be_deleted(): void
    {
        $course = Course::factory()->create();

        CourseSession::create([
            'course_id' => $course->id,
            'session_code' => 'SES-001',
            'delivery_mode' => 'onsite',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-03',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->actor())->delete(route('courses.destroy', $course));

        $response->assertRedirect(route('courses.show', $course));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    public function test_course_can_be_deleted_with_its_nested_rows(): void
    {
        $course = Course::factory()->create();
        $criteriaType = CriteriaType::create(['code' => 'MIN_AGE', 'name' => 'الحد الأدنى للسن']);
        $course->courseModules()->create(['title' => 'وحدة']);
        $course->courseCriteria()->create([
            'criteria_type_id' => $criteriaType->id,
            'operator' => 'gte',
            'value' => '18',
            'is_mandatory' => true,
        ]);

        $response = $this->actingAs($this->actor())->delete(route('courses.destroy', $course));

        $response->assertRedirect(route('courses.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
        $this->assertDatabaseCount('course_modules', 0);
        $this->assertDatabaseCount('course_criteria', 0);
    }

    public function test_manage_user_can_open_the_create_form(): void
    {
        $this->actingAs($this->actor())
            ->get(route('courses.create'))
            ->assertOk()
            ->assertSee('كود الدورة')
            ->assertSee('الوحدات التدريبية');
    }

    public function test_course_show_and_edit_pages_render_nested_data(): void
    {
        $criteriaType = CriteriaType::create(['code' => 'MIN_EXPERIENCE', 'name' => 'الحد الأدنى للخبرة']);
        $course = Course::factory()->create(['title' => 'دورة العرض']);
        $course->courseModules()->create(['title' => 'وحدة العرض', 'sort_order' => 1]);
        $course->courseCriteria()->create([
            'criteria_type_id' => $criteriaType->id,
            'operator' => 'gte',
            'value' => '2',
            'is_mandatory' => true,
        ]);

        $this->actingAs($this->actor())
            ->get(route('courses.show', $course))
            ->assertOk()
            ->assertSee('دورة العرض')
            ->assertSee('وحدة العرض');

        $this->actingAs($this->actor())
            ->get(route('courses.edit', $course))
            ->assertOk()
            ->assertSee('وحدة العرض')
            ->assertSee('الحد الأدنى للخبرة');
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function actor(array $permissions = ['COURSES_VIEW', 'COURSES_MANAGE']): User
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
            'code' => 'CRS-'.strtoupper(Str::random(6)),
            'title' => 'دورة تجريبية',
            'description' => 'وصف الدورة',
            'objectives' => 'أهداف الدورة',
            'content_outline' => 'محتوى الدورة',
            'target_audience' => 'الفئة المستهدفة',
            'category_id' => null,
            'course_type_id' => null,
            'level' => 'beginner',
            'total_hours' => 12,
            'days_count' => 2,
            'default_delivery_mode' => 'onsite',
            'price' => 1000,
            'min_trainees' => 5,
            'max_trainees' => 20,
            'min_attendance_pct' => 75,
            'pass_score_pct' => 60,
            'is_active' => 1,
            'modules' => [],
            'criteria' => [],
            'prerequisites' => [],
        ], $overrides);
    }
}
