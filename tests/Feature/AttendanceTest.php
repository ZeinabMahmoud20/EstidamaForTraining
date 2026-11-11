<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\ExcuseStatus;
use App\Models\AttendanceExcuse;
use App\Models\CourseSession;
use App\Models\Enrollment;
use App\Models\SessionDay;
use App\Models\SessionDayTrainer;
use App\Models\SessionTrainer;
use App\Models\Trainee;
use App\Models\TraineeAttendance;
use App\Models\Trainer;
use App\Models\TrainerAttendance;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guests_are_redirected_from_attendance(): void
    {
        $this->get(route('attendance.index'))->assertRedirect(route('login'));
    }

    public function test_users_without_permission_cannot_access_attendance(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('attendance.index'))->assertForbidden();
        $this->actingAs($user)->get(route('attendance.own'))->assertForbidden();
    }

    public function test_attendance_can_be_recorded_for_trainees_and_trainers(): void
    {
        $session = CourseSession::factory()->create(['session_code' => 'SES-ATT-1']);
        $day = SessionDay::factory()->create([
            'session_id' => $session->id,
            'day_no' => 1,
            'day_date' => '2026-02-10',
            'start_time' => '09:00',
            'end_time' => '12:00',
        ]);
        $enrollment = $this->enrollmentFor($session);
        $trainer = Trainer::factory()->create();
        SessionTrainer::create([
            'session_id' => $session->id,
            'trainer_id' => $trainer->id,
            'planned_role' => 'primary',
        ]);

        $this->actingAs($this->actor(['ATTENDANCE_RECORD']))
            ->post(route('attendance.record', ['session' => $session, 'day' => $day]), [
                'trainees' => [
                    $enrollment->id => ['status' => 'present', 'notes' => 'وصل مبكرًا'],
                ],
                'trainers' => [
                    $trainer->id => ['status' => 'late', 'late_minutes' => 15],
                ],
            ])
            ->assertRedirect(route('attendance.session', ['session' => $session, 'day' => $day->id]))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('trainee_attendance', [
            'enrollment_id' => $enrollment->id,
            'session_day_id' => $day->id,
            'status' => 'present',
            'notes' => 'وصل مبكرًا',
        ]);
        $this->assertDatabaseHas('trainer_attendance', [
            'trainer_id' => $trainer->id,
            'session_day_id' => $day->id,
            'status' => 'late',
            'late_minutes' => 15,
        ]);
        $this->assertSame('100.00', $enrollment->refresh()->attendance_pct);
    }

    public function test_attendance_can_be_recorded_for_a_day_trainer_override(): void
    {
        $session = CourseSession::factory()->create();
        $day = SessionDay::factory()->create(['session_id' => $session->id]);
        $planned = Trainer::factory()->create();
        $replacement = Trainer::factory()->create();
        SessionTrainer::create([
            'session_id' => $session->id,
            'trainer_id' => $planned->id,
            'planned_role' => 'primary',
        ]);
        SessionDayTrainer::create([
            'session_day_id' => $day->id,
            'trainer_id' => $replacement->id,
            'actual_role' => 'primary',
            'actual_hours' => 3,
        ]);

        $this->actingAs($this->actor(['ATTENDANCE_RECORD']))
            ->post(route('attendance.record', ['session' => $session, 'day' => $day]), [
                'trainers' => [$replacement->id => ['status' => 'present']],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('trainer_attendance', [
            'trainer_id' => $replacement->id,
            'session_day_id' => $day->id,
            'status' => 'present',
        ]);
    }

    public function test_recording_for_a_day_from_another_session_is_rejected(): void
    {
        $session = CourseSession::factory()->create();
        $otherSession = CourseSession::factory()->create();
        $day = SessionDay::factory()->create(['session_id' => $otherSession->id]);
        $enrollment = $this->enrollmentFor($session);

        $this->actingAs($this->actor(['ATTENDANCE_RECORD']))
            ->post(route('attendance.record', ['session' => $session, 'day' => $day]), [
                'trainees' => [$enrollment->id => ['status' => 'present']],
            ])
            ->assertSessionHasErrors('session_day_id');

        $this->assertDatabaseCount('trainee_attendance', 0);
    }

    public function test_absent_excused_requires_a_reason_and_creates_a_pending_excuse(): void
    {
        $session = CourseSession::factory()->create();
        $day = SessionDay::factory()->create(['session_id' => $session->id]);
        $enrollment = $this->enrollmentFor($session);
        $actor = $this->actor(['ATTENDANCE_RECORD']);

        $this->actingAs($actor)
            ->post(route('attendance.record', ['session' => $session, 'day' => $day]), [
                'trainees' => [$enrollment->id => ['status' => 'absent_excused']],
            ])
            ->assertSessionHasErrors('excuse_reason');

        $this->assertDatabaseCount('trainee_attendance', 0);

        $this->actingAs($actor)
            ->post(route('attendance.record', ['session' => $session, 'day' => $day]), [
                'trainees' => [$enrollment->id => [
                    'status' => 'absent_excused',
                    'excuse_reason' => 'ظرف صحي طارئ',
                ]],
            ])
            ->assertSessionHasNoErrors();

        $attendance = TraineeAttendance::firstOrFail();
        $this->assertSame(AttendanceStatus::AbsentExcused, $attendance->status);
        $this->assertNotNull($attendance->excuse_id);

        $excuse = AttendanceExcuse::findOrFail($attendance->excuse_id);
        $this->assertSame(ExcuseStatus::Pending, $excuse->status);
        $this->assertSame('ظرف صحي طارئ', $excuse->reason);
    }

    public function test_excuse_review_approval_and_rejection_update_linked_attendance(): void
    {
        $session = CourseSession::factory()->create();
        $day = SessionDay::factory()->create(['session_id' => $session->id]);
        $enrollment = $this->enrollmentFor($session);
        $reviewer = $this->actor(['ATTENDANCE_EXCUSE_REVIEW']);

        $excuse = AttendanceExcuse::create([
            'reason' => 'عذر اختباري',
            'status' => ExcuseStatus::Pending,
            'submitted_at' => now(),
        ]);
        $attendance = TraineeAttendance::create([
            'enrollment_id' => $enrollment->id,
            'session_day_id' => $day->id,
            'status' => 'absent_excused',
            'excuse_id' => $excuse->id,
        ]);

        $this->actingAs($reviewer)
            ->patch(route('attendance.excuses.review', $excuse), [
                'status' => 'approved',
                'review_notes' => 'العذر مقبول',
            ])
            ->assertRedirect();

        $this->assertSame(ExcuseStatus::Approved, $excuse->refresh()->status);
        $this->assertSame($reviewer->id, $excuse->reviewed_by);
        $this->assertNotNull($excuse->reviewed_at);
        $this->assertSame(AttendanceStatus::AbsentExcused, $attendance->refresh()->status);

        $this->actingAs($reviewer)
            ->patch(route('attendance.excuses.review', $excuse), [
                'status' => 'rejected',
                'review_notes' => 'لم تُرفق مستندات',
            ])
            ->assertRedirect();

        $this->assertSame(ExcuseStatus::Rejected, $excuse->refresh()->status);
        $this->assertSame(AttendanceStatus::AbsentUnexcused, $attendance->refresh()->status);
    }

    public function test_rejecting_an_excuse_requires_review_notes(): void
    {
        $excuse = AttendanceExcuse::create([
            'reason' => 'عذر بدون مستندات',
            'status' => ExcuseStatus::Pending,
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->actor(['ATTENDANCE_EXCUSE_REVIEW']))
            ->patch(route('attendance.excuses.review', $excuse), ['status' => 'rejected'])
            ->assertSessionHasErrors('review_notes');

        $this->assertSame(ExcuseStatus::Pending, $excuse->refresh()->status);
    }

    public function test_excuse_review_permission_is_required(): void
    {
        $excuse = AttendanceExcuse::create([
            'reason' => 'عذر',
            'status' => ExcuseStatus::Pending,
            'submitted_at' => now(),
        ]);

        $this->actingAs($this->actor(['ATTENDANCE_RECORD']))
            ->get(route('attendance.excuses.index'))
            ->assertForbidden();

        $this->actingAs($this->actor(['ATTENDANCE_RECORD']))
            ->patch(route('attendance.excuses.review', $excuse), ['status' => 'approved'])
            ->assertForbidden();
    }

    public function test_trainee_can_view_own_attendance(): void
    {
        $trainee = Trainee::factory()->create();
        $user = User::factory()->create(['trainee_id' => $trainee->id]);
        $user->givePermissionTo('ATTENDANCE_VIEW_OWN');

        $session = CourseSession::factory()->create(['session_code' => 'SES-OWN-1']);
        $enrollment = Enrollment::factory()->create([
            'session_id' => $session->id,
            'trainee_id' => $trainee->id,
        ]);
        $day = SessionDay::factory()->create(['session_id' => $session->id, 'day_no' => 1]);
        TraineeAttendance::create([
            'enrollment_id' => $enrollment->id,
            'session_day_id' => $day->id,
            'status' => 'present',
        ]);

        $this->actingAs($user)
            ->get(route('attendance.own'))
            ->assertOk()
            ->assertSee('SES-OWN-1')
            ->assertSee('حاضر');
    }

    public function test_trainer_can_view_own_attendance(): void
    {
        $trainer = Trainer::factory()->create();
        $user = User::factory()->create(['trainer_id' => $trainer->id]);
        $user->givePermissionTo('ATTENDANCE_VIEW_OWN');

        $session = CourseSession::factory()->create(['session_code' => 'SES-OWN-2']);
        $day = SessionDay::factory()->create(['session_id' => $session->id, 'day_no' => 1]);
        TrainerAttendance::create([
            'trainer_id' => $trainer->id,
            'session_day_id' => $day->id,
            'status' => 'late',
            'late_minutes' => 10,
        ]);

        $this->actingAs($user)
            ->get(route('attendance.own'))
            ->assertOk()
            ->assertSee('SES-OWN-2')
            ->assertSee('متأخر');
    }

    private function enrollmentFor(CourseSession $session): Enrollment
    {
        return Enrollment::factory()->create([
            'session_id' => $session->id,
            'trainee_id' => Trainee::factory()->create()->id,
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
