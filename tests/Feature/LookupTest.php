<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseType;
use App\Models\CriteriaType;
use App\Models\EducationLevel;
use App\Models\JobGrade;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LookupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guests_are_redirected_from_lookups(): void
    {
        $this->get(route('lookups.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_access_lookups(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('lookups.index'))->assertForbidden();
    }

    public function test_all_tabs_render_their_own_items(): void
    {
        CourseCategory::create(['name' => 'تصنيف الاختبار']);
        CourseType::create(['name' => 'نوع الاختبار']);
        CriteriaType::create(['code' => 'TEST_CRITERIA', 'name' => 'معيار الاختبار']);
        EducationLevel::create(['name' => 'مستوى الاختبار', 'rank_order' => 1]);
        JobGrade::create(['name' => 'درجة الاختبار', 'rank_order' => 1]);

        $tabs = [
            'categories' => 'تصنيف الاختبار',
            'course-types' => 'نوع الاختبار',
            'criteria-types' => 'معيار الاختبار',
            'education-levels' => 'مستوى الاختبار',
            'job-grades' => 'درجة الاختبار',
        ];

        $user = $this->actor();

        foreach ($tabs as $tab => $name) {
            $this->actingAs($user)
                ->get(route('lookups.index', ['tab' => $tab]))
                ->assertOk()
                ->assertSee($name);
        }
    }

    public function test_course_category_can_be_created_updated_and_deleted(): void
    {
        $user = $this->actor();

        $this->actingAs($user)
            ->post(route('lookups.store', 'categories'), ['name' => 'القيادة'])
            ->assertRedirect(route('lookups.index', ['tab' => 'categories']))
            ->assertSessionHas('success');

        $category = CourseCategory::where('name', 'القيادة')->firstOrFail();

        $this->actingAs($user)
            ->put(route('lookups.update', ['type' => 'categories', 'lookup' => $category->id]), ['name' => 'القيادة المتقدمة'])
            ->assertRedirect(route('lookups.index', ['tab' => 'categories']));

        $this->assertDatabaseHas('course_categories', ['id' => $category->id, 'name' => 'القيادة المتقدمة']);

        $this->actingAs($user)
            ->delete(route('lookups.destroy', ['type' => 'categories', 'lookup' => $category->id]))
            ->assertRedirect(route('lookups.index', ['tab' => 'categories']));

        $this->assertDatabaseMissing('course_categories', ['id' => $category->id]);
    }

    public function test_category_with_children_or_courses_cannot_be_deleted(): void
    {
        $user = $this->actor();

        $parent = CourseCategory::create(['name' => 'الأب']);
        CourseCategory::create(['name' => 'الابن', 'parent_id' => $parent->id]);

        $this->actingAs($user)
            ->delete(route('lookups.destroy', ['type' => 'categories', 'lookup' => $parent->id]))
            ->assertRedirect(route('lookups.index', ['tab' => 'categories']))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('course_categories', ['id' => $parent->id]);

        $used = CourseCategory::create(['name' => 'مرتبط بدورة']);
        Course::factory()->create(['category_id' => $used->id]);

        $this->actingAs($user)
            ->delete(route('lookups.destroy', ['type' => 'categories', 'lookup' => $used->id]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('course_categories', ['id' => $used->id]);
    }

    public function test_category_cannot_be_its_own_parent_or_a_descendant_parent(): void
    {
        $user = $this->actor();

        $parent = CourseCategory::create(['name' => 'الأب']);
        $child = CourseCategory::create(['name' => 'الابن', 'parent_id' => $parent->id]);

        $this->actingAs($user)
            ->put(route('lookups.update', ['type' => 'categories', 'lookup' => $parent->id]), [
                'name' => $parent->name,
                'parent_id' => $parent->id,
            ])
            ->assertSessionHasErrors('parent_id');

        $this->actingAs($user)
            ->put(route('lookups.update', ['type' => 'categories', 'lookup' => $parent->id]), [
                'name' => $parent->name,
                'parent_id' => $child->id,
            ])
            ->assertSessionHasErrors('parent_id');
    }

    public function test_course_type_is_validated_and_cannot_be_deleted_when_used(): void
    {
        $user = $this->actor();

        $this->actingAs($user)
            ->post(route('lookups.store', 'course-types'), ['name' => 'ورشة عمل جديدة', 'description' => 'وصف'])
            ->assertRedirect(route('lookups.index', ['tab' => 'course-types']));

        $type = CourseType::where('name', 'ورشة عمل جديدة')->firstOrFail();

        $this->actingAs($user)
            ->post(route('lookups.store', 'course-types'), ['name' => 'ورشة عمل جديدة'])
            ->assertSessionHasErrors('name');

        Course::factory()->create(['course_type_id' => $type->id]);

        $this->actingAs($user)
            ->delete(route('lookups.destroy', ['type' => 'course-types', 'lookup' => $type->id]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('course_types', ['id' => $type->id]);
    }

    public function test_criteria_type_code_is_normalized_and_unique(): void
    {
        $user = $this->actor();

        $this->actingAs($user)
            ->post(route('lookups.store', 'criteria-types'), [
                'code' => 'min_age',
                'name' => 'الحد الأدنى للسن',
                'description' => 'لا يقل السن عن القيمة.',
            ])
            ->assertRedirect(route('lookups.index', ['tab' => 'criteria-types']));

        $this->assertDatabaseHas('criteria_types', ['code' => 'MIN_AGE', 'name' => 'الحد الأدنى للسن']);

        $this->actingAs($user)
            ->post(route('lookups.store', 'criteria-types'), [
                'code' => 'MIN_AGE',
                'name' => 'معيار آخر',
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_education_levels_and_job_grades_can_be_managed(): void
    {
        $user = $this->actor();

        $this->actingAs($user)
            ->post(route('lookups.store', 'education-levels'), ['name' => 'دبلوم دراسات عليا', 'rank_order' => 4])
            ->assertRedirect(route('lookups.index', ['tab' => 'education-levels']));

        $this->actingAs($user)
            ->post(route('lookups.store', 'job-grades'), ['name' => 'الدرجة الأولى', 'rank_order' => 3])
            ->assertRedirect(route('lookups.index', ['tab' => 'job-grades']));

        $level = EducationLevel::where('name', 'دبلوم دراسات عليا')->firstOrFail();
        $grade = JobGrade::where('name', 'الدرجة الأولى')->firstOrFail();

        $this->assertDatabaseHas('education_levels', ['id' => $level->id, 'rank_order' => 4]);
        $this->assertDatabaseHas('job_grades', ['id' => $grade->id, 'rank_order' => 3]);

        $this->actingAs($user)
            ->put(route('lookups.update', ['type' => 'education-levels', 'lookup' => $level->id]), [
                'name' => 'دبلوم الدراسات العليا',
                'rank_order' => 5,
            ])
            ->assertRedirect(route('lookups.index', ['tab' => 'education-levels']));

        $this->assertDatabaseHas('education_levels', ['id' => $level->id, 'name' => 'دبلوم الدراسات العليا']);

        $this->actingAs($user)
            ->delete(route('lookups.destroy', ['type' => 'education-levels', 'lookup' => $level->id]))
            ->assertRedirect(route('lookups.index', ['tab' => 'education-levels']));

        $this->actingAs($user)
            ->delete(route('lookups.destroy', ['type' => 'job-grades', 'lookup' => $grade->id]))
            ->assertRedirect(route('lookups.index', ['tab' => 'job-grades']));

        $this->assertDatabaseMissing('education_levels', ['id' => $level->id]);
        $this->assertDatabaseMissing('job_grades', ['id' => $grade->id]);
    }

    public function test_edit_mode_loads_the_selected_item_into_the_form(): void
    {
        $type = CourseType::create(['name' => 'نوع للتعديل', 'description' => 'وصف النوع']);

        $this->actingAs($this->actor())
            ->get(route('lookups.index', ['tab' => 'course-types', 'edit' => $type->id]))
            ->assertOk()
            ->assertSee('تعديل النوع')
            ->assertSee('نوع للتعديل');
    }

    public function test_unknown_lookup_type_returns_not_found(): void
    {
        $this->actingAs($this->actor())
            ->post('/lookups/unknown-type', ['name' => 'أي شيء'])
            ->assertNotFound();
    }

    private function actor(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo('LOOKUPS_MANAGE');

        return $user;
    }
}
