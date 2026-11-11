<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Equipment;
use App\Models\Hall;
use App\Models\HallBlock;
use App\Models\SessionDay;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HallTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guests_are_redirected_from_halls(): void
    {
        $this->get(route('halls.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_access_halls(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('halls.index'))
            ->assertForbidden();
    }

    public function test_change_hall_user_can_list_and_view_but_cannot_manage(): void
    {
        $hall = Hall::factory()->create(['name' => 'قاعة العرض']);

        $this->actingAs($this->actor(['CHANGE_HALL']))
            ->get(route('halls.index'))
            ->assertOk()
            ->assertSee('قاعة العرض');

        $this->actingAs($this->actor(['CHANGE_HALL']))
            ->get(route('halls.show', $hall))
            ->assertOk()
            ->assertSee('قاعة العرض');

        $this->actingAs($this->actor(['CHANGE_HALL']))
            ->get(route('halls.create'))
            ->assertForbidden();

        // Equipment management tab is not exposed to hall-change users.
        $this->actingAs($this->actor(['CHANGE_HALL']))
            ->get(route('halls.index', ['tab' => 'equipment']))
            ->assertOk()
            ->assertDontSee('إضافة تجهيز');
    }

    public function test_halls_can_be_listed_and_filtered(): void
    {
        $branch = Branch::factory()->create(['name' => 'فرع الاختبار']);
        Hall::factory()->create(['branch_id' => $branch->id, 'name' => 'قاعة النشاط', 'code' => 'H-100']);
        Hall::factory()->inactive()->create(['name' => 'قاعة التوقف', 'code' => 'H-200']);

        $this->actingAs($this->actor())
            ->get(route('halls.index'))
            ->assertOk()
            ->assertSee('قاعة النشاط')
            ->assertSee('قاعة التوقف');

        $this->actingAs($this->actor())
            ->get(route('halls.index', ['is_active' => '1']))
            ->assertOk()
            ->assertSee('قاعة النشاط')
            ->assertDontSee('قاعة التوقف');

        $this->actingAs($this->actor())
            ->get(route('halls.index', ['q' => 'التوقف']))
            ->assertOk()
            ->assertSee('قاعة التوقف')
            ->assertDontSee('قاعة النشاط');

        $this->actingAs($this->actor())
            ->get(route('halls.index', ['branch_id' => $branch->id]))
            ->assertOk()
            ->assertSee('قاعة النشاط')
            ->assertDontSee('قاعة التوقف');
    }

    public function test_hall_can_be_created_with_equipment_quantities(): void
    {
        $branch = Branch::factory()->create();
        $projector = Equipment::factory()->create(['name' => 'بروجيكتور']);
        $whiteboard = Equipment::factory()->create(['name' => 'سبورة']);

        $response = $this->actingAs($this->actor())->post(route('halls.store'), $this->validPayload([
            'branch_id' => $branch->id,
            'equipment' => [
                ['equipment_id' => $projector->id, 'quantity' => 2],
                ['equipment_id' => $whiteboard->id, 'quantity' => 1],
            ],
        ]));

        $hall = Hall::where('code', 'H-100')->firstOrFail();

        $response->assertRedirect(route('halls.show', $hall));
        $this->assertDatabaseHas('halls', [
            'id' => $hall->id,
            'branch_id' => $branch->id,
            'name' => 'قاعة الاختبار',
            'capacity' => 25,
        ]);
        $this->assertDatabaseHas('hall_equipment', [
            'hall_id' => $hall->id,
            'equipment_id' => $projector->id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('hall_equipment', [
            'hall_id' => $hall->id,
            'equipment_id' => $whiteboard->id,
            'quantity' => 1,
        ]);
    }

    public function test_hall_creation_validates_required_fields_and_unique_code_per_branch(): void
    {
        $branch = Branch::factory()->create();
        Hall::factory()->create(['branch_id' => $branch->id, 'code' => 'H-100']);

        $response = $this->actingAs($this->actor())->post(route('halls.store'), $this->validPayload([
            'branch_id' => $branch->id,
            'code' => 'H-100',
            'name' => '',
            'capacity' => 0,
        ]));

        $response->assertSessionHasErrors(['code', 'name', 'capacity']);
        $this->assertDatabaseCount('halls', 1);
    }

    public function test_hall_can_be_updated_and_equipment_is_synced(): void
    {
        $hall = Hall::factory()->create(['name' => 'اسم قديم']);
        $oldEquipment = Equipment::factory()->create();
        $newEquipment = Equipment::factory()->create();
        $hall->equipment()->attach($oldEquipment->id, ['quantity' => 1]);

        $payload = $this->validPayload([
            'branch_id' => $hall->branch_id,
            'code' => $hall->code,
            'name' => 'اسم محدث',
            'equipment' => [
                ['equipment_id' => $newEquipment->id, 'quantity' => 3],
            ],
        ]);

        $response = $this->actingAs($this->actor())->put(route('halls.update', $hall), $payload);

        $response->assertRedirect(route('halls.show', $hall));
        $this->assertDatabaseHas('halls', ['id' => $hall->id, 'name' => 'اسم محدث']);
        $this->assertDatabaseMissing('hall_equipment', [
            'hall_id' => $hall->id,
            'equipment_id' => $oldEquipment->id,
        ]);
        $this->assertDatabaseHas('hall_equipment', [
            'hall_id' => $hall->id,
            'equipment_id' => $newEquipment->id,
            'quantity' => 3,
        ]);
    }

    public function test_hall_can_be_deleted_with_its_equipment_and_blocks(): void
    {
        $hall = Hall::factory()->create();
        $equipment = Equipment::factory()->create();
        $hall->equipment()->attach($equipment->id, ['quantity' => 2]);
        $hall->hallBlocks()->create([
            'start_at' => '2026-12-01 09:00:00',
            'end_at' => '2026-12-01 11:00:00',
            'reason' => 'maintenance',
        ]);

        $response = $this->actingAs($this->actor())->delete(route('halls.destroy', $hall));

        $response->assertRedirect(route('halls.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('halls', ['id' => $hall->id]);
        $this->assertDatabaseCount('hall_equipment', 0);
        $this->assertDatabaseCount('hall_blocks', 0);
    }

    public function test_hall_with_session_days_cannot_be_deleted(): void
    {
        $hall = Hall::factory()->create();
        $courseSession = CourseSession::create([
            'course_id' => Course::factory()->create()->id,
            'session_code' => 'SES-HALL-1',
            'delivery_mode' => 'onsite',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-03',
            'status' => 'draft',
        ]);
        SessionDay::create([
            'session_id' => $courseSession->id,
            'day_no' => 1,
            'day_date' => '2026-12-01',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'hall_id' => $hall->id,
        ]);

        $response = $this->actingAs($this->actor())->delete(route('halls.destroy', $hall));

        $response->assertRedirect(route('halls.show', $hall));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('halls', ['id' => $hall->id]);
    }

    public function test_hall_show_and_edit_pages_render_related_data(): void
    {
        $hall = Hall::factory()->create(['name' => 'قاعة العرض', 'code' => 'H-777']);
        $equipment = Equipment::factory()->create(['name' => 'شاشة العرض']);
        $hall->equipment()->attach($equipment->id, ['quantity' => 4]);
        $hall->hallBlocks()->create([
            'start_at' => '2026-12-05 10:00:00',
            'end_at' => '2026-12-05 12:00:00',
            'reason' => 'maintenance',
        ]);

        $courseSession = CourseSession::create([
            'course_id' => Course::factory()->create(['title' => 'دورة القاعة'])->id,
            'session_code' => 'SES-HALL-SHOW',
            'delivery_mode' => 'onsite',
            'start_date' => '2026-12-05',
            'end_date' => '2026-12-05',
            'status' => 'draft',
        ]);
        SessionDay::create([
            'session_id' => $courseSession->id,
            'day_no' => 1,
            'day_date' => '2026-12-05',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'hall_id' => $hall->id,
        ]);

        $this->actingAs($this->actor())
            ->get(route('halls.show', $hall))
            ->assertOk()
            ->assertSee('قاعة العرض')
            ->assertSee('شاشة العرض')
            ->assertSee('صيانة')
            ->assertSee('SES-HALL-SHOW')
            ->assertSee('دورة القاعة');

        $this->actingAs($this->actor())
            ->get(route('halls.edit', $hall))
            ->assertOk()
            ->assertSee('قاعة العرض')
            ->assertSee('H-777')
            ->assertSee('شاشة العرض');
    }

    public function test_hall_block_can_be_added_and_deleted(): void
    {
        $actor = $this->actor(['HALL_BLOCK']);
        $hall = Hall::factory()->create();

        $this->actingAs($actor)->post(route('halls.blocks.store', $hall), [
            'start_at' => '2026-12-01T09:00',
            'end_at' => '2026-12-01T11:00',
            'reason' => 'maintenance',
            'notes' => 'صيانة دورية',
        ])->assertRedirect();

        $block = HallBlock::where('hall_id', $hall->id)->firstOrFail();
        $this->assertDatabaseHas('hall_blocks', [
            'id' => $block->id,
            'hall_id' => $hall->id,
            'reason' => 'maintenance',
            'notes' => 'صيانة دورية',
        ]);

        $this->actingAs($actor)
            ->delete(route('halls.blocks.destroy', [$hall, $block]))
            ->assertRedirect();

        $this->assertDatabaseMissing('hall_blocks', ['id' => $block->id]);
    }

    public function test_hall_block_end_must_be_after_start(): void
    {
        $hall = Hall::factory()->create();

        $this->actingAs($this->actor(['HALL_BLOCK']))
            ->post(route('halls.blocks.store', $hall), [
                'start_at' => '2026-12-01T11:00',
                'end_at' => '2026-12-01T09:00',
            ])
            ->assertSessionHasErrors('end_at');

        $this->assertDatabaseCount('hall_blocks', 0);
    }

    public function test_hall_block_requires_block_permission(): void
    {
        $hall = Hall::factory()->create();

        $this->actingAs($this->actor(['HALLS_MANAGE']))
            ->post(route('halls.blocks.store', $hall), [
                'start_at' => '2026-12-01T09:00',
                'end_at' => '2026-12-01T11:00',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('hall_blocks', 0);
    }

    public function test_equipment_can_be_created_updated_and_deleted(): void
    {
        $actor = $this->actor();

        $this->actingAs($actor)
            ->post(route('equipment.store'), ['name' => 'بروجيكتور جديد'])
            ->assertRedirect();

        $equipment = Equipment::where('name', 'بروجيكتور جديد')->firstOrFail();

        $this->actingAs($actor)
            ->put(route('equipment.update', $equipment), ['name' => 'شاشة جديدة'])
            ->assertRedirect();

        $this->assertDatabaseHas('equipment', ['id' => $equipment->id, 'name' => 'شاشة جديدة']);

        $this->actingAs($actor)
            ->delete(route('equipment.destroy', $equipment))
            ->assertRedirect();

        $this->assertDatabaseMissing('equipment', ['id' => $equipment->id]);
    }

    public function test_equipment_attached_to_a_hall_cannot_be_deleted(): void
    {
        $equipment = Equipment::factory()->create();
        $hall = Hall::factory()->create();
        $hall->equipment()->attach($equipment->id, ['quantity' => 1]);

        $response = $this->actingAs($this->actor())->delete(route('equipment.destroy', $equipment));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('equipment', ['id' => $equipment->id]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function actor(array $permissions = ['HALLS_MANAGE']): User
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
            'branch_id' => Branch::factory()->create()->id,
            'code' => 'H-100',
            'name' => 'قاعة الاختبار',
            'capacity' => 25,
            'floor' => 'الأول',
            'location_description' => 'بجوار المدخل الرئيسي',
            'is_active' => 1,
            'equipment' => [],
        ], $overrides);
    }
}
