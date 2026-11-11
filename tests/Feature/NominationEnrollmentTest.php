<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Enums\NominationStatus;
use App\Models\CourseCriteria;
use App\Models\CourseSession;
use App\Models\CriteriaType;
use App\Models\Enrollment;
use App\Models\Nomination;
use App\Models\SessionDay;
use App\Models\Trainee;
use App\Models\TraineeAttendance;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NominationEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guests_are_redirected_from_nominations_and_enrollments(): void
    {
        $this->get(route('nominations.index'))->assertRedirect(route('login'));
        $this->get(route('enrollments.index'))->assertRedirect(route('login'));
    }

    public function test_users_without_permission_cannot_access_nominations_or_enrollments(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('nominations.index'))->assertForbidden();
        $this->actingAs($user)->get(route('enrollments.index'))->assertForbidden();
    }

    public function test_nomination_can_be_created_and_history_is_recorded(): void
    {
        $actor = $this->actor(['NOMINATE_TRAINEE']);
        $session = CourseSession::factory()->create();
        $trainee = Trainee::factory()->create();

        $response = $this->actingAs($actor)->post(route('nominations.store'), $this->nominationPayload($session, $trainee));

        $nomination = Nomination::firstOrFail();

        $response->assertRedirect(route('nominations.show', $nomination));
        $this->assertDatabaseHas('nominations', [
            'id' => $nomination->id,
            'session_id' => $session->id,
            'trainee_id' => $trainee->id,
            'source' => 'self',
            'status' => 'submitted',
        ]);
        $this->assertDatabaseHas('nomination_status_history', [
            'nomination_id' => $nomination->id,
            'from_status' => null,
            'to_status' => 'submitted',
            'changed_by' => $actor->id,
        ]);
    }

    public function test_nomination_blocks_mandatory_criteria_unless_overridden(): void
    {
        $session = CourseSession::factory()->create();
        $trainee = Trainee::factory()->create(['years_experience' => 1]);
        $criteriaType = CriteriaType::create(['code' => 'MIN_EXPERIENCE', 'name' => 'الحد الأدنى للخبرة']);

        CourseCriteria::create([
            'course_id' => $session->course_id,
            'criteria_type_id' => $criteriaType->id,
            'operator' => 'gte',
            'value' => '5',
            'is_mandatory' => true,
        ]);

        $nominator = $this->actor(['NOMINATE_TRAINEE']);

        $this->actingAs($nominator)
            ->post(route('nominations.store'), $this->nominationPayload($session, $trainee))
            ->assertSessionHasErrors('override');

        $this->assertDatabaseCount('nominations', 0);

        $overrider = $this->actor(['NOMINATE_TRAINEE', 'OVERRIDE_CRITERIA']);

        $this->actingAs($overrider)
            ->post(route('nominations.store'), $this->nominationPayload($session, $trainee, ['override' => 1]))
            ->assertRedirect();

        $nomination = Nomination::firstOrFail();
        $this->assertTrue((bool) ($nomination->application_data['criteria_override'] ?? false));
        $this->assertCount(1, $nomination->application_data['criteria']);
    }

    public function test_nomination_can_be_reviewed_and_approved_to_create_enrollment(): void
    {
        $nomination = Nomination::factory()->create();
        $reviewer = $this->actor(['REVIEW_NOMINATION']);

        $this->actingAs($reviewer)
            ->patch(route('nominations.transition', $nomination), [
                'status' => 'under_review',
                'reason' => 'مراجعة مبدئية',
            ])
            ->assertRedirect(route('nominations.show', $nomination));

        $nomination->refresh();
        $this->assertSame(NominationStatus::UnderReview, $nomination->status);

        $approver = $this->actor(['APPROVE_NOMINATION']);

        $this->actingAs($approver)
            ->patch(route('nominations.transition', $nomination), [
                'status' => 'approved',
                'reason' => 'مطابق للمعايير',
            ])
            ->assertRedirect(route('nominations.show', $nomination));

        $nomination->refresh();
        $this->assertSame(NominationStatus::Approved, $nomination->status);
        $this->assertSame($approver->id, $nomination->decision_by);
        $this->assertDatabaseHas('enrollments', [
            'nomination_id' => $nomination->id,
            'session_id' => $nomination->session_id,
            'trainee_id' => $nomination->trainee_id,
            'status' => 'enrolled',
        ]);
    }

    public function test_transition_permissions_are_enforced_per_target_status(): void
    {
        $nomination = Nomination::factory()->create();
        $reviewer = $this->actor(['REVIEW_NOMINATION']);
        $approver = $this->actor(['APPROVE_NOMINATION']);

        $this->actingAs($reviewer)
            ->patch(route('nominations.transition', $nomination), ['status' => 'approved'])
            ->assertForbidden();

        $this->actingAs($approver)
            ->patch(route('nominations.transition', $nomination), ['status' => 'under_review'])
            ->assertForbidden();

        $this->actingAs($reviewer)
            ->patch(route('nominations.transition', $nomination), ['status' => 'under_review'])
            ->assertRedirect();

        $this->actingAs($approver)
            ->patch(route('nominations.transition', $nomination), ['status' => 'waitlisted'])
            ->assertRedirect();

        $nomination->refresh();
        $this->assertSame(NominationStatus::Waitlisted, $nomination->status);
        $this->assertSame(1, $nomination->waitlist_order);
    }

    public function test_withdrawing_an_approved_nomination_withdraws_its_enrollment(): void
    {
        $nomination = Nomination::factory()->approved()->create();
        $enrollment = Enrollment::factory()->create([
            'nomination_id' => $nomination->id,
            'session_id' => $nomination->session_id,
            'trainee_id' => $nomination->trainee_id,
            'status' => EnrollmentStatus::Enrolled,
        ]);

        $this->actingAs($this->actor(['APPROVE_NOMINATION']))
            ->patch(route('nominations.transition', $nomination), [
                'status' => 'withdrawn',
                'reason' => 'بناءً على طلب المتدرب',
            ])
            ->assertRedirect();

        $this->assertSame(NominationStatus::Withdrawn, $nomination->refresh()->status);
        $this->assertSame(EnrollmentStatus::Withdrawn, $enrollment->refresh()->status);
    }

    public function test_nomination_with_enrollment_cannot_be_deleted(): void
    {
        $nomination = Nomination::factory()->approved()->create();
        Enrollment::factory()->create([
            'nomination_id' => $nomination->id,
            'session_id' => $nomination->session_id,
            'trainee_id' => $nomination->trainee_id,
        ]);

        $this->actingAs($this->actor(['REVIEW_NOMINATION']))
            ->delete(route('nominations.destroy', $nomination))
            ->assertRedirect(route('nominations.show', $nomination))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('nominations', ['id' => $nomination->id]);
    }

    public function test_nomination_without_enrollment_can_be_deleted_with_history(): void
    {
        $nomination = Nomination::factory()->create();
        $nomination->nominationStatusHistory()->create([
            'from_status' => null,
            'to_status' => 'submitted',
            'changed_at' => now(),
        ]);

        $this->actingAs($this->actor(['REVIEW_NOMINATION']))
            ->delete(route('nominations.destroy', $nomination))
            ->assertRedirect(route('nominations.index'));

        $this->assertDatabaseMissing('nominations', ['id' => $nomination->id]);
        $this->assertDatabaseMissing('nomination_status_history', ['nomination_id' => $nomination->id]);
    }

    public function test_enrollment_can_be_created_from_an_approved_nomination(): void
    {
        $nomination = Nomination::factory()->approved()->create();
        $actor = $this->actor(['ENROLLMENTS_MANAGE']);

        $response = $this->actingAs($actor)->post(route('enrollments.store'), [
            'nomination_id' => $nomination->id,
            'status' => 'enrolled',
        ]);

        $enrollment = Enrollment::firstOrFail();
        $response->assertRedirect(route('enrollments.show', $enrollment));
        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'nomination_id' => $nomination->id,
            'status' => 'enrolled',
        ]);

        $this->actingAs($actor)
            ->post(route('enrollments.store'), [
                'nomination_id' => $nomination->id,
                'status' => 'enrolled',
            ])
            ->assertSessionHasErrors('nomination_id');
    }

    public function test_enrollment_can_be_updated_and_marks_completion_date(): void
    {
        $enrollment = Enrollment::factory()->create();
        $actor = $this->actor(['ENROLLMENTS_MANAGE']);

        $this->actingAs($actor)
            ->put(route('enrollments.update', $enrollment), [
                'status' => 'completed',
                'attendance_pct' => 85,
                'final_score_pct' => 78,
                'is_passed' => 1,
                'is_certificate_eligible' => 1,
            ])
            ->assertRedirect(route('enrollments.show', $enrollment));

        $enrollment->refresh();
        $this->assertSame(EnrollmentStatus::Completed, $enrollment->status);
        $this->assertSame('85.00', $enrollment->attendance_pct);
        $this->assertSame('78.00', $enrollment->final_score_pct);
        $this->assertTrue($enrollment->is_passed);
        $this->assertTrue($enrollment->is_certificate_eligible);
        $this->assertNotNull($enrollment->completed_at);
    }

    public function test_attendance_percentage_can_be_recalculated_from_attendance_records(): void
    {
        $nomination = Nomination::factory()->approved()->create();
        $enrollment = Enrollment::factory()->create([
            'nomination_id' => $nomination->id,
            'session_id' => $nomination->session_id,
            'trainee_id' => $nomination->trainee_id,
        ]);

        $dayOne = SessionDay::factory()->create(['session_id' => $nomination->session_id, 'day_no' => 1]);
        $dayTwo = SessionDay::factory()->create(['session_id' => $nomination->session_id, 'day_no' => 2]);

        TraineeAttendance::create([
            'enrollment_id' => $enrollment->id,
            'session_day_id' => $dayOne->id,
            'status' => 'present',
        ]);
        TraineeAttendance::create([
            'enrollment_id' => $enrollment->id,
            'session_day_id' => $dayTwo->id,
            'status' => 'absent_unexcused',
        ]);

        $this->actingAs($this->actor(['ENROLLMENTS_MANAGE']))
            ->post(route('enrollments.recalculate', $enrollment))
            ->assertRedirect(route('enrollments.show', $enrollment));

        $this->assertSame('50.00', $enrollment->refresh()->attendance_pct);
    }

    public function test_enrollment_deletion_is_blocked_by_attendance_and_allowed_without_children(): void
    {
        $blocked = Enrollment::factory()->create();
        $day = SessionDay::factory()->create(['session_id' => $blocked->session_id]);
        TraineeAttendance::create([
            'enrollment_id' => $blocked->id,
            'session_day_id' => $day->id,
            'status' => 'present',
        ]);

        $actor = $this->actor(['ENROLLMENTS_MANAGE']);

        $this->actingAs($actor)
            ->delete(route('enrollments.destroy', $blocked))
            ->assertRedirect(route('enrollments.show', $blocked))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('enrollments', ['id' => $blocked->id]);

        $deletable = Enrollment::factory()->create();

        $this->actingAs($actor)
            ->delete(route('enrollments.destroy', $deletable))
            ->assertRedirect(route('enrollments.index'));

        $this->assertDatabaseMissing('enrollments', ['id' => $deletable->id]);
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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function nominationPayload(CourseSession $session, Trainee $trainee, array $overrides = []): array
    {
        return array_merge([
            'session_id' => $session->id,
            'trainee_id' => $trainee->id,
            'source' => 'self',
            'nominated_by_provider_id' => null,
            'notes' => 'ترشيح اختباري',
        ], $overrides);
    }
}
