<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Trainer;
use App\Models\TrainingProvider;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guests_are_redirected_from_providers(): void
    {
        $this->get(route('providers.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_access_providers(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('providers.index'))
            ->assertForbidden();
    }

    public function test_providers_can_be_listed_and_filtered(): void
    {
        TrainingProvider::factory()->create(['name' => 'جهة النشاط']);
        TrainingProvider::factory()->inactive()->create(['name' => 'جهة التوقف']);

        $this->actingAs($this->actor())
            ->get(route('providers.index'))
            ->assertOk()
            ->assertSee('جهة النشاط')
            ->assertSee('جهة التوقف');

        $this->actingAs($this->actor())
            ->get(route('providers.index', ['is_active' => '1']))
            ->assertOk()
            ->assertSee('جهة النشاط')
            ->assertDontSee('جهة التوقف');

        $this->actingAs($this->actor())
            ->get(route('providers.index', ['q' => 'التوقف']))
            ->assertOk()
            ->assertSee('جهة التوقف')
            ->assertDontSee('جهة النشاط');
    }

    public function test_provider_can_be_created(): void
    {
        $response = $this->actingAs($this->actor())
            ->post(route('providers.store'), $this->validPayload());

        $provider = TrainingProvider::where('name', 'جهة تدريب الاختبار')->firstOrFail();

        $response->assertRedirect(route('providers.show', $provider));
        $this->assertDatabaseHas('training_providers', [
            'id' => $provider->id,
            'name' => 'جهة تدريب الاختبار',
            'contact_person' => 'أحمد مسؤول التواصل',
            'is_active' => 1,
        ]);
    }

    public function test_provider_creation_validates_required_name_and_email(): void
    {
        $response = $this->actingAs($this->actor())
            ->post(route('providers.store'), $this->validPayload([
                'name' => '',
                'email' => 'not-an-email',
            ]));

        $response->assertSessionHasErrors(['name', 'email']);
        $this->assertDatabaseCount('training_providers', 0);
    }

    public function test_provider_can_be_updated(): void
    {
        $provider = TrainingProvider::factory()->create(['name' => 'اسم قديم']);

        $response = $this->actingAs($this->actor())
            ->put(route('providers.update', $provider), $this->validPayload([
                'name' => 'اسم محدث',
                'contact_person' => 'مسؤول جديد',
                'is_active' => 0,
            ]));

        $response->assertRedirect(route('providers.show', $provider));
        $this->assertDatabaseHas('training_providers', [
            'id' => $provider->id,
            'name' => 'اسم محدث',
            'contact_person' => 'مسؤول جديد',
            'is_active' => 0,
        ]);
    }

    public function test_provider_can_be_deleted_when_not_linked(): void
    {
        $provider = TrainingProvider::factory()->create();

        $response = $this->actingAs($this->actor())->delete(route('providers.destroy', $provider));

        $response->assertRedirect(route('providers.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('training_providers', ['id' => $provider->id]);
    }

    public function test_provider_with_trainers_cannot_be_deleted(): void
    {
        $provider = TrainingProvider::factory()->create();
        Trainer::factory()->create(['provider_id' => $provider->id]);

        $response = $this->actingAs($this->actor())->delete(route('providers.destroy', $provider));

        $response->assertRedirect(route('providers.show', $provider));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('training_providers', ['id' => $provider->id]);
    }

    public function test_provider_show_and_edit_pages_render_related_data(): void
    {
        $provider = TrainingProvider::factory()->create([
            'name' => 'جهة العرض',
            'contact_person' => 'مسؤول العرض',
        ]);
        Trainer::factory()->create([
            'provider_id' => $provider->id,
            'full_name' => 'مدرب الجهة',
        ]);
        CourseSession::create([
            'course_id' => Course::factory()->create()->id,
            'provider_id' => $provider->id,
            'session_code' => 'SES-PROVIDER-1',
            'delivery_mode' => 'onsite',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-03',
            'status' => 'draft',
        ]);

        $this->actingAs($this->actor())
            ->get(route('providers.show', $provider))
            ->assertOk()
            ->assertSee('جهة العرض')
            ->assertSee('مسؤول العرض')
            ->assertSee('مدرب الجهة')
            ->assertSee('SES-PROVIDER-1');

        $this->actingAs($this->actor())
            ->get(route('providers.edit', $provider))
            ->assertOk()
            ->assertSee('جهة العرض')
            ->assertSee('مسؤول العرض');
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function actor(array $permissions = ['PROVIDERS_MANAGE']): User
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
            'name' => 'جهة تدريب الاختبار',
            'contact_person' => 'أحمد مسؤول التواصل',
            'phone' => '0223456789',
            'email' => 'provider@example.com',
            'address' => 'القاهرة',
            'is_active' => 1,
        ], $overrides);
    }
}
