<?php

namespace Tests\Feature;

use App\Enums\SessionStatus;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Hall;
use App\Models\HallBlock;
use App\Models\SessionDay;
use App\Models\Trainer;
use App\Models\TrainingKit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CourseSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guests_are_redirected_from_sessions(): void
    {
        $this->get(route('sessions.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_access_sessions(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('sessions.index'))
            ->assertForbidden();
    }

    public function test_change_hall_user_can_view_sessions_but_cannot_manage_them(): void
    {
        $session = CourseSession::factory()->create(['session_code' => 'SES-VIEW-1']);

        $this->actingAs($this->actor(['CHANGE_HALL']))
            ->get(route('sessions.index'))
            ->assertOk()
            ->assertSee('SES-VIEW-1');

        $this->actingAs($this->actor(['CHANGE_HALL']))
            ->get(route('sessions.show', $session))
            ->assertOk()
            ->assertSee('SES-VIEW-1');

        $this->actingAs($this->actor(['CHANGE_HALL']))
            ->get(route('sessions.create'))
            ->assertForbidden();

        $this->actingAs($this->actor(['CHANGE_HALL']))
            ->get(route('sessions.edit', $session))
            ->assertForbidden();
    }

    public function test_session_can_be_created_with_days_and_planned_trainers(): void
    {
        $hall = Hall::factory()->create();
        $trainer = Trainer::factory()->create();
        $actor = $this->actor();

        $payload = $this->validPayload([
            'session_code' => 'SES-CREATE-1',
            'days' => [
                [
                    'day_no' => 1,
                    'day_date' => '2026-12-01',
                    'start_time' => '09:00',
                    'end_time' => '12:00',
                    'hall_id' => $hall->id,
                ],
                [
                    'day_no' => 2,
                    'day_date' => '2026-12-02',
                    'start_time' => '13:00',
                    'end_time' => '15:00',
                    'hall_id' => $hall->id,
                    'online_url' => 'https://meet.example.com/day-2',
                ],
            ],
            'trainers' => [
                ['trainer_id' => $trainer->id, 'planned_role' => 'primary'],
            ],
        ]);

        $response = $this->actingAs($actor)->post(route('sessions.store'), $payload);

        $session = CourseSession::where('session_code', 'SES-CREATE-1')->firstOrFail();

        $response->assertRedirect(route('sessions.show', $session));
        $this->assertDatabaseHas('course_sessions', [
            'id' => $session->id,
            'course_id' => $payload['course_id'],
            'status' => 'draft',
            'created_by' => $actor->id,
        ]);
        $this->assertDatabaseCount('session_days', 2);
        $this->assertDatabaseHas('session_days', [
            'session_id' => $session->id,
            'day_no' => 1,
            'day_date' => '2026-12-01 00:00:00',
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'hall_id' => $hall->id,
        ]);
        $this->assertDatabaseHas('session_trainers', [
            'session_id' => $session->id,
            'trainer_id' => $trainer->id,
            'planned_role' => 'primary',
            'assigned_by' => $actor->id,
        ]);
    }

    public function test_session_creation_validates_required_fields_unique_code_and_online_url(): void
    {
        CourseSession::factory()->create(['session_code' => 'SES-EXISTS-1']);

        $response = $this->actingAs($this->actor())->post(route('sessions.store'), $this->validPayload([
            'course_id' => '',
            'session_code' => 'SES-EXISTS-1',
            'delivery_mode' => 'online',
            'online_url' => null,
            'end_date' => '2026-11-30',
        ]));

        $response->assertSessionHasErrors(['course_id', 'session_code', 'online_url', 'end_date']);
        $this->assertDatabaseCount('course_sessions', 1);
    }

    public function test_session_days_are_numbered_automatically_when_left_blank(): void
    {
        $response = $this->actingAs($this->actor())->post(route('sessions.store'), $this->validPayload([
            'days' => [
                ['day_date' => '2026-12-01', 'start_time' => '09:00', 'end_time' => '11:00'],
                ['day_date' => '2026-12-02', 'start_time' => '09:00', 'end_time' => '11:00'],
            ],
        ]));

        $session = CourseSession::firstOrFail();

        $response->assertRedirect(route('sessions.show', $session));
        $this->assertDatabaseHas('session_days', ['session_id' => $session->id, 'day_no' => 1]);
        $this->assertDatabaseHas('session_days', ['session_id' => $session->id, 'day_no' => 2]);
    }

    public function test_session_days_must_be_within_session_dates_and_end_after_start(): void
    {
        $response = $this->actingAs($this->actor())->post(route('sessions.store'), $this->validPayload([
            'days' => [
                ['day_no' => 1, 'day_date' => '2026-12-10', 'start_time' => '09:00', 'end_time' => '12:00'],
            ],
        ]));

        $response->assertSessionHasErrors('days.0.day_date');

        $response = $this->actingAs($this->actor())->post(route('sessions.store'), $this->validPayload([
            'days' => [
                ['day_no' => 1, 'day_date' => '2026-12-02', 'start_time' => '09:00', 'end_time' => '08:00'],
            ],
        ]));

        $response->assertSessionHasErrors('days.0.end_time');
        $this->assertDatabaseCount('course_sessions', 0);
    }

    public function test_hall_double_booking_is_rejected(): void
    {
        $hall = Hall::factory()->create();
        $existingSession = CourseSession::factory()->create([
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-05',
        ]);
        SessionDay::factory()->create([
            'session_id' => $existingSession->id,
            'day_no' => 1,
            'day_date' => '2026-12-01',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'hall_id' => $hall->id,
        ]);

        $response = $this->actingAs($this->actor())->post(route('sessions.store'), $this->validPayload([
            'days' => [
                [
                    'day_no' => 1,
                    'day_date' => '2026-12-01',
                    'start_time' => '10:00',
                    'end_time' => '13:00',
                    'hall_id' => $hall->id,
                ],
            ],
        ]));

        $response->assertSessionHasErrors('days.0.hall_id');
        $this->assertDatabaseCount('course_sessions', 1);
        $this->assertDatabaseCount('session_days', 1);
    }

    public function test_hall_block_conflict_is_rejected(): void
    {
        $hall = Hall::factory()->create();
        HallBlock::create([
            'hall_id' => $hall->id,
            'start_at' => '2026-12-01 10:00:00',
            'end_at' => '2026-12-01 11:00:00',
            'reason' => 'maintenance',
        ]);

        $response = $this->actingAs($this->actor())->post(route('sessions.store'), $this->validPayload([
            'days' => [
                [
                    'day_no' => 1,
                    'day_date' => '2026-12-01',
                    'start_time' => '09:00',
                    'end_time' => '12:00',
                    'hall_id' => $hall->id,
                ],
            ],
        ]));

        $response->assertSessionHasErrors('days.0.hall_id');
        $this->assertDatabaseCount('course_sessions', 0);
    }

    public function test_overlapping_days_in_one_payload_are_rejected(): void
    {
        $hall = Hall::factory()->create();

        $response = $this->actingAs($this->actor())->post(route('sessions.store'), $this->validPayload([
            'days' => [
                ['day_no' => 1, 'day_date' => '2026-12-01', 'start_time' => '09:00', 'end_time' => '12:00', 'hall_id' => $hall->id],
                ['day_no' => 2, 'day_date' => '2026-12-01', 'start_time' => '11:00', 'end_time' => '13:00', 'hall_id' => $hall->id],
            ],
        ]));

        $response->assertSessionHasErrors('days.1.hall_id');
        $this->assertDatabaseCount('course_sessions', 0);
    }

    public function test_session_update_syncs_days_trainers_and_logs_hall_changes(): void
    {
        $actor = $this->actor();
        $session = CourseSession::factory()->create([
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-05',
        ]);
        $hallA = Hall::factory()->create();
        $hallB = Hall::factory()->create();
        $oldTrainer = Trainer::factory()->create();
        $newTrainer = Trainer::factory()->create();

        $existingDay = SessionDay::factory()->create([
            'session_id' => $session->id,
            'day_no' => 1,
            'day_date' => '2026-12-01',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'hall_id' => $hallA->id,
        ]);
        $session->sessionTrainers()->create([
            'trainer_id' => $oldTrainer->id,
            'planned_role' => 'primary',
            'assigned_by' => $actor->id,
        ]);

        $payload = $this->validPayload([
            'course_id' => $session->course_id,
            'session_code' => $session->session_code,
            'days' => [
                [
                    'id' => $existingDay->id,
                    'day_no' => 1,
                    'day_date' => '2026-12-01',
                    'start_time' => '10:00',
                    'end_time' => '12:00',
                    'hall_id' => $hallB->id,
                ],
                [
                    'day_no' => 2,
                    'day_date' => '2026-12-02',
                    'start_time' => '09:00',
                    'end_time' => '11:00',
                    'hall_id' => $hallA->id,
                ],
            ],
            'trainers' => [
                ['trainer_id' => $newTrainer->id, 'planned_role' => 'backup'],
            ],
        ]);

        $response = $this->actingAs($actor)->put(route('sessions.update', $session), $payload);

        $response->assertRedirect(route('sessions.show', $session));

        $existingDay->refresh();
        $this->assertSame($hallB->id, $existingDay->hall_id);
        $this->assertSame(1, $existingDay->day_no);
        $this->assertSame('10:00:00', $existingDay->start_time);
        $this->assertDatabaseCount('session_days', 2);
        $this->assertDatabaseHas('session_trainers', [
            'session_id' => $session->id,
            'trainer_id' => $newTrainer->id,
            'planned_role' => 'backup',
        ]);
        $this->assertDatabaseMissing('session_trainers', [
            'session_id' => $session->id,
            'trainer_id' => $oldTrainer->id,
        ]);
        $this->assertDatabaseHas('hall_change_log', [
            'session_day_id' => $existingDay->id,
            'old_hall_id' => $hallA->id,
            'new_hall_id' => $hallB->id,
            'changed_by' => $actor->id,
        ]);
    }

    public function test_hall_can_be_changed_with_change_hall_permission_and_is_logged(): void
    {
        $actor = $this->actor(['CHANGE_HALL']);
        $session = CourseSession::factory()->create([
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-05',
        ]);
        $oldHall = Hall::factory()->create();
        $newHall = Hall::factory()->create();
        $day = SessionDay::factory()->create([
            'session_id' => $session->id,
            'day_no' => 1,
            'day_date' => '2026-12-01',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'hall_id' => $oldHall->id,
        ]);

        $response = $this->actingAs($actor)->patch(route('sessions.hall.update', $session), [
            'day_id' => $day->id,
            'hall_id' => $newHall->id,
            'reason' => 'تعارض طارئ',
        ]);

        $response->assertRedirect(route('sessions.show', $session));
        $day->refresh();
        $this->assertSame($newHall->id, $day->hall_id);
        $this->assertDatabaseHas('hall_change_log', [
            'session_day_id' => $day->id,
            'old_hall_id' => $oldHall->id,
            'new_hall_id' => $newHall->id,
            'reason' => 'تعارض طارئ',
            'changed_by' => $actor->id,
        ]);
    }

    public function test_hall_change_requires_change_hall_permission(): void
    {
        $session = CourseSession::factory()->create([
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-05',
        ]);
        $day = SessionDay::factory()->create(['session_id' => $session->id]);
        $hall = Hall::factory()->create();

        $this->actingAs($this->actor(['SESSIONS_MANAGE']))
            ->patch(route('sessions.hall.update', $session), [
                'day_id' => $day->id,
                'hall_id' => $hall->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('hall_change_log', 0);
    }

    public function test_hall_change_rejects_double_booking(): void
    {
        $actor = $this->actor(['CHANGE_HALL']);
        $hallA = Hall::factory()->create();
        $hallB = Hall::factory()->create();

        $otherSession = CourseSession::factory()->create([
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-05',
        ]);
        SessionDay::factory()->create([
            'session_id' => $otherSession->id,
            'day_no' => 1,
            'day_date' => '2026-12-01',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'hall_id' => $hallB->id,
        ]);

        $session = CourseSession::factory()->create([
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-05',
        ]);
        $targetDay = SessionDay::factory()->create([
            'session_id' => $session->id,
            'day_no' => 1,
            'day_date' => '2026-12-01',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'hall_id' => $hallA->id,
        ]);

        $response = $this->actingAs($actor)->patch(route('sessions.hall.update', $session), [
            'day_id' => $targetDay->id,
            'hall_id' => $hallB->id,
            'reason' => 'محاولة تعارض',
        ]);

        $response->assertSessionHasErrors('hall_id');
        $this->assertSame($hallA->id, $targetDay->fresh()->hall_id);
        $this->assertDatabaseCount('hall_change_log', 0);
    }

    public function test_session_can_be_deleted_with_its_days_trainers_and_change_log(): void
    {
        $actor = $this->actor();
        $session = CourseSession::factory()->create();
        $hallA = Hall::factory()->create();
        $hallB = Hall::factory()->create();
        $trainer = Trainer::factory()->create();
        $day = SessionDay::factory()->create([
            'session_id' => $session->id,
            'hall_id' => $hallA->id,
        ]);
        $day->hallChangeLog()->create([
            'old_hall_id' => $hallA->id,
            'new_hall_id' => $hallB->id,
            'changed_by' => $actor->id,
            'changed_at' => now(),
        ]);
        $session->sessionTrainers()->create([
            'trainer_id' => $trainer->id,
            'planned_role' => 'primary',
        ]);

        $response = $this->actingAs($actor)->delete(route('sessions.destroy', $session));

        $response->assertRedirect(route('sessions.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('course_sessions', ['id' => $session->id]);
        $this->assertDatabaseCount('session_days', 0);
        $this->assertDatabaseCount('session_trainers', 0);
        $this->assertDatabaseCount('hall_change_log', 0);
    }

    public function test_session_with_a_training_kit_cannot_be_deleted(): void
    {
        $session = CourseSession::factory()->create();
        $trainer = Trainer::factory()->create();
        TrainingKit::create([
            'session_id' => $session->id,
            'trainer_id' => $trainer->id,
            'version' => 1,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->actor())->delete(route('sessions.destroy', $session));

        $response->assertRedirect(route('sessions.show', $session));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('course_sessions', ['id' => $session->id]);
    }

    public function test_status_transitions_are_guarded_by_publish_permission(): void
    {
        $session = CourseSession::factory()->create(['status' => SessionStatus::Draft]);

        $this->actingAs($this->actor(['SESSIONS_MANAGE']))
            ->patch(route('sessions.status.update', $session), ['status' => 'open_for_registration'])
            ->assertForbidden();

        $this->actingAs($this->actor(['SESSIONS_PUBLISH']))
            ->patch(route('sessions.status.update', $session), ['status' => 'open_for_registration'])
            ->assertRedirect(route('sessions.show', $session));

        $this->assertSame(SessionStatus::OpenForRegistration, $session->fresh()->status);

        $response = $this->actingAs($this->actor(['SESSIONS_PUBLISH']))
            ->patch(route('sessions.status.update', $session), ['status' => 'completed']);

        $response->assertSessionHasErrors('status');
        $this->assertSame(SessionStatus::OpenForRegistration, $session->fresh()->status);
    }

    public function test_session_show_and_edit_pages_render_related_data(): void
    {
        $session = CourseSession::factory()->create([
            'session_code' => 'SES-RENDER-1',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-05',
        ]);
        $session->course->update(['title' => 'دورة عرض الجلسة']);
        $hall = Hall::factory()->create(['name' => 'قاعة عرض الجلسة']);
        $trainer = Trainer::factory()->create(['full_name' => 'مدرب عرض الجلسة']);
        SessionDay::factory()->create([
            'session_id' => $session->id,
            'day_no' => 1,
            'day_date' => '2026-12-01',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'hall_id' => $hall->id,
        ]);
        $session->sessionTrainers()->create([
            'trainer_id' => $trainer->id,
            'planned_role' => 'primary',
        ]);

        $this->actingAs($this->actor())
            ->get(route('sessions.show', $session))
            ->assertOk()
            ->assertSee('SES-RENDER-1')
            ->assertSee('دورة عرض الجلسة')
            ->assertSee('قاعة عرض الجلسة')
            ->assertSee('مدرب عرض الجلسة')
            ->assertSee('مسودة');

        $this->actingAs($this->actor())
            ->get(route('sessions.edit', $session))
            ->assertOk()
            ->assertSee('SES-RENDER-1')
            ->assertSee('قاعة عرض الجلسة')
            ->assertSee('مدرب عرض الجلسة');
    }

    public function test_session_index_can_be_filtered_by_status_and_delivery_mode(): void
    {
        CourseSession::factory()->create([
            'session_code' => 'SES-ONSITE-1',
            'delivery_mode' => 'onsite',
            'status' => SessionStatus::Draft,
        ]);
        CourseSession::factory()->online()->create([
            'session_code' => 'SES-ONLINE-1',
            'status' => SessionStatus::OpenForRegistration,
        ]);

        $this->actingAs($this->actor())
            ->get(route('sessions.index'))
            ->assertOk()
            ->assertSee('SES-ONSITE-1')
            ->assertSee('SES-ONLINE-1');

        $this->actingAs($this->actor())
            ->get(route('sessions.index', ['status' => 'open_for_registration']))
            ->assertOk()
            ->assertSee('SES-ONLINE-1')
            ->assertDontSee('SES-ONSITE-1');

        $this->actingAs($this->actor())
            ->get(route('sessions.index', ['delivery_mode' => 'onsite']))
            ->assertOk()
            ->assertSee('SES-ONSITE-1')
            ->assertDontSee('SES-ONLINE-1');
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function actor(array $permissions = ['SESSIONS_MANAGE']): User
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
            'course_id' => Course::factory()->create()->id,
            'session_code' => 'SES-'.strtoupper(Str::random(6)),
            'provider_id' => null,
            'branch_id' => null,
            'accreditation_id' => null,
            'delivery_mode' => 'onsite',
            'online_url' => null,
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-05',
            'registration_open' => null,
            'registration_close' => null,
            'max_trainees' => 20,
            'price' => 1000,
            'days' => [],
            'trainers' => [],
        ], $overrides);
    }
}
