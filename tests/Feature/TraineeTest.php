<?php

namespace Tests\Feature;

use App\Models\CourseSession;
use App\Models\EducationLevel;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\JobGrade;
use App\Models\Nomination;
use App\Models\Trainee;
use App\Models\TrainingProvider;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TraineeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guests_are_redirected_from_trainees(): void
    {
        $this->get(route('trainees.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_access_trainees(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('trainees.index'))
            ->assertForbidden();
    }

    public function test_warning_manager_can_view_trainees_but_cannot_create_them(): void
    {
        $trainee = Trainee::factory()->create();
        $actor = $this->actor(['WARNINGS_MANAGE']);

        $this->actingAs($actor)->get(route('trainees.index'))->assertOk();
        $this->actingAs($actor)->get(route('trainees.show', $trainee))->assertOk();
        $this->actingAs($actor)->get(route('trainees.create'))->assertForbidden();

        $this->assertSame(
            $trainee->id,
            Trainee::query()->firstOrFail()->id
        );
    }

    public function test_trainees_can_be_listed_and_filtered(): void
    {
        $provider = TrainingProvider::factory()->create(['name' => 'جهة المتدربين']);
        $educationLevel = EducationLevel::create(['name' => 'بكالوريوس', 'rank_order' => 1]);

        $active = Trainee::factory()->create([
            'full_name' => 'متدرب نشط',
            'education_level_id' => $educationLevel->id,
        ]);
        $active->traineeProviders()->create([
            'provider_id' => $provider->id,
            'is_primary' => true,
        ]);

        $inactive = Trainee::factory()->inactive()->create(['full_name' => 'متدرب متوقف']);
        $inactive->traineeProviders()->create([
            'provider_id' => $provider->id,
            'is_primary' => true,
        ]);

        $this->actingAs($this->actor())
            ->get(route('trainees.index'))
            ->assertOk()
            ->assertSee('متدرب نشط')
            ->assertSee('متدرب متوقف');

        $this->actingAs($this->actor())
            ->get(route('trainees.index', ['is_active' => '1']))
            ->assertOk()
            ->assertSee('متدرب نشط')
            ->assertDontSee('متدرب متوقف');

        $this->actingAs($this->actor())
            ->get(route('trainees.index', ['q' => 'متوقف']))
            ->assertOk()
            ->assertSee('متدرب متوقف')
            ->assertDontSee('متدرب نشط');

        $this->actingAs($this->actor())
            ->get(route('trainees.index', ['provider_id' => $provider->id]))
            ->assertOk()
            ->assertSee('متدرب نشط')
            ->assertSee('متدرب متوقف');

        $this->actingAs($this->actor())
            ->get(route('trainees.index', ['education_level_id' => $educationLevel->id]))
            ->assertOk()
            ->assertSee('متدرب نشط')
            ->assertDontSee('متدرب متوقف');
    }

    public function test_trainee_can_be_created_with_provider_affiliations(): void
    {
        $educationLevel = EducationLevel::create(['name' => 'ماجستير', 'rank_order' => 2]);
        $jobGrade = JobGrade::create(['name' => 'الدرجة الثانية', 'rank_order' => 2]);
        $firstProvider = TrainingProvider::factory()->create(['name' => 'الجهة الأساسية']);
        $secondProvider = TrainingProvider::factory()->create(['name' => 'الجهة الثانوية']);

        $response = $this->actingAs($this->actor())->post(route('trainees.store'), $this->validPayload([
            'full_name' => 'متدرب جديد',
            'education_level_id' => $educationLevel->id,
            'job_grade_id' => $jobGrade->id,
            'providers' => [
                [
                    'provider_id' => $firstProvider->id,
                    'job_title_at_provider' => 'محاسب أول',
                    'from_date' => '2020-01-01',
                    'is_primary' => 1,
                ],
                [
                    'provider_id' => $secondProvider->id,
                    'job_title_at_provider' => 'مدقق',
                    'from_date' => '2022-01-01',
                    'is_primary' => 0,
                ],
            ],
        ]));

        $trainee = Trainee::where('full_name', 'متدرب جديد')->firstOrFail();

        $response->assertRedirect(route('trainees.show', $trainee));
        $this->assertDatabaseHas('trainees', [
            'id' => $trainee->id,
            'education_level_id' => $educationLevel->id,
            'job_grade_id' => $jobGrade->id,
            'is_active' => 1,
        ]);
        $this->assertDatabaseHas('trainee_providers', [
            'trainee_id' => $trainee->id,
            'provider_id' => $firstProvider->id,
            'job_title_at_provider' => 'محاسب أول',
            'is_primary' => 1,
        ]);
        $this->assertDatabaseHas('trainee_providers', [
            'trainee_id' => $trainee->id,
            'provider_id' => $secondProvider->id,
            'is_primary' => 0,
        ]);
    }

    public function test_trainee_creation_validates_required_unique_and_related_fields(): void
    {
        Trainee::factory()->create(['national_id' => '30001011234567']);
        $provider = TrainingProvider::factory()->create();

        $response = $this->actingAs($this->actor())->post(route('trainees.store'), $this->validPayload([
            'national_id' => '30001011234567',
            'full_name' => '',
            'gender' => 'invalid',
            'providers' => [
                ['provider_id' => $provider->id, 'is_primary' => 1],
                ['provider_id' => $provider->id, 'is_primary' => 1],
            ],
        ]));

        $response->assertSessionHasErrors([
            'national_id',
            'full_name',
            'gender',
            'providers.0.provider_id',
            'providers.1.provider_id',
            'providers',
        ]);
        $this->assertDatabaseCount('trainees', 1);
    }

    public function test_only_one_primary_provider_is_allowed_and_dates_must_be_ordered(): void
    {
        $firstProvider = TrainingProvider::factory()->create();
        $secondProvider = TrainingProvider::factory()->create();

        $response = $this->actingAs($this->actor())->post(route('trainees.store'), $this->validPayload([
            'providers' => [
                [
                    'provider_id' => $firstProvider->id,
                    'from_date' => '2023-01-01',
                    'to_date' => '2022-01-01',
                    'is_primary' => 1,
                ],
                [
                    'provider_id' => $secondProvider->id,
                    'is_primary' => 1,
                ],
            ],
        ]));

        $response->assertSessionHasErrors(['providers', 'providers.0.to_date']);
        $this->assertDatabaseCount('trainees', 0);
    }

    public function test_trainee_can_be_updated_and_affiliations_are_replaced(): void
    {
        $trainee = Trainee::factory()->create(['full_name' => 'اسم قديم']);
        $oldProvider = TrainingProvider::factory()->create();
        $newProvider = TrainingProvider::factory()->create();

        $trainee->traineeProviders()->create([
            'provider_id' => $oldProvider->id,
            'is_primary' => true,
        ]);

        $response = $this->actingAs($this->actor())->put(route('trainees.update', $trainee), $this->validPayload([
            'full_name' => 'اسم محدث',
            'providers' => [
                [
                    'provider_id' => $newProvider->id,
                    'job_title_at_provider' => 'مدير',
                    'from_date' => '2024-01-01',
                    'is_primary' => 1,
                ],
            ],
        ]));

        $response->assertRedirect(route('trainees.show', $trainee));
        $this->assertDatabaseHas('trainees', [
            'id' => $trainee->id,
            'full_name' => 'اسم محدث',
        ]);
        $this->assertDatabaseMissing('trainee_providers', [
            'trainee_id' => $trainee->id,
            'provider_id' => $oldProvider->id,
        ]);
        $this->assertDatabaseHas('trainee_providers', [
            'trainee_id' => $trainee->id,
            'provider_id' => $newProvider->id,
            'job_title_at_provider' => 'مدير',
            'is_primary' => 1,
        ]);
    }

    public function test_trainee_can_be_deleted_with_its_affiliations_and_warnings(): void
    {
        $trainee = Trainee::factory()->create();
        $provider = TrainingProvider::factory()->create();

        $trainee->traineeProviders()->create([
            'provider_id' => $provider->id,
            'is_primary' => true,
        ]);
        $trainee->traineeWarnings()->create([
            'warning_type' => 'warning',
            'description' => 'إنذار تجريبي',
            'issued_at' => now()->toDateString(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->actor())->delete(route('trainees.destroy', $trainee));

        $response->assertRedirect(route('trainees.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('trainees', ['id' => $trainee->id]);
        $this->assertDatabaseCount('trainee_providers', 0);
        $this->assertDatabaseCount('trainee_warnings', 0);
    }

    public function test_trainee_with_business_records_cannot_be_deleted(): void
    {
        $linkedUser = Trainee::factory()->create();
        User::factory()->create(['trainee_id' => $linkedUser->id]);

        $nominated = Trainee::factory()->create();
        $nominationSession = CourseSession::factory()->create();
        Nomination::create([
            'session_id' => $nominationSession->id,
            'trainee_id' => $nominated->id,
            'source' => 'self',
            'status' => 'submitted',
        ]);

        $enrolled = Trainee::factory()->create();
        $enrollmentSession = CourseSession::factory()->create();
        $nomination = Nomination::create([
            'session_id' => $enrollmentSession->id,
            'trainee_id' => $enrolled->id,
            'source' => 'self',
            'status' => 'approved',
        ]);
        Enrollment::create([
            'nomination_id' => $nomination->id,
            'session_id' => $enrollmentSession->id,
            'trainee_id' => $enrolled->id,
            'status' => 'enrolled',
        ]);

        $invoiced = Trainee::factory()->create();
        $invoiceSession = CourseSession::factory()->create();
        Invoice::create([
            'invoice_no' => 'INV-TRAINEE-1',
            'session_id' => $invoiceSession->id,
            'bill_to' => 'trainee',
            'trainee_id' => $invoiced->id,
            'amount' => 100,
            'total_amount' => 100,
            'status' => 'issued',
        ]);

        foreach ([$linkedUser, $nominated, $enrolled, $invoiced] as $trainee) {
            $this->actingAs($this->actor())
                ->delete(route('trainees.destroy', $trainee))
                ->assertRedirect(route('trainees.show', $trainee))
                ->assertSessionHas('error');

            $this->assertDatabaseHas('trainees', ['id' => $trainee->id]);
        }
    }

    public function test_trainee_show_and_edit_pages_render_related_data(): void
    {
        $trainee = Trainee::factory()->create([
            'full_name' => 'متدرب العرض',
            'job_title' => 'محاسب',
        ]);
        $provider = TrainingProvider::factory()->create(['name' => 'جهة العرض']);
        $trainee->traineeProviders()->create([
            'provider_id' => $provider->id,
            'job_title_at_provider' => 'محاسب بالجهة',
            'is_primary' => true,
        ]);
        $trainee->traineeWarnings()->create([
            'warning_type' => 'issue',
            'description' => 'ملاحظة العرض',
            'issued_at' => now()->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($this->actor())
            ->get(route('trainees.show', $trainee))
            ->assertOk()
            ->assertSee('متدرب العرض')
            ->assertSee('محاسب')
            ->assertSee('جهة العرض')
            ->assertSee('محاسب بالجهة')
            ->assertSee('ملاحظة العرض')
            ->assertSee('مشكلة');

        $this->actingAs($this->actor())
            ->get(route('trainees.edit', $trainee))
            ->assertOk()
            ->assertSee('متدرب العرض')
            ->assertSee('جهة العرض')
            ->assertSee('محاسب بالجهة');
    }

    public function test_warning_can_be_added_and_deleted(): void
    {
        $trainee = Trainee::factory()->create();
        $session = CourseSession::factory()->create();
        $actor = $this->actor(['WARNINGS_MANAGE']);

        $response = $this->actingAs($actor)->post(route('trainees.warnings.store', $trainee), [
            'warning_type' => 'warning',
            'session_id' => $session->id,
            'description' => 'إنذار جديد',
            'issued_at' => now()->toDateString(),
            'is_active' => 1,
        ]);

        $response->assertRedirect();
        $warning = $trainee->traineeWarnings()->firstOrFail();
        $this->assertSame($actor->id, $warning->issued_by);
        $this->assertDatabaseHas('trainee_warnings', [
            'trainee_id' => $trainee->id,
            'session_id' => $session->id,
            'warning_type' => 'warning',
            'description' => 'إنذار جديد',
        ]);

        $this->actingAs($actor)
            ->delete(route('trainees.warnings.destroy', [$trainee, $warning]))
            ->assertRedirect();

        $this->assertDatabaseCount('trainee_warnings', 0);
    }

    public function test_warning_requires_warning_permission_and_correct_trainee(): void
    {
        $trainee = Trainee::factory()->create();
        $otherTrainee = Trainee::factory()->create();

        $this->actingAs($this->actor(['TRAINEES_MANAGE']))
            ->post(route('trainees.warnings.store', $trainee), [
                'warning_type' => 'warning',
            ])
            ->assertForbidden();

        $warning = $trainee->traineeWarnings()->create([
            'warning_type' => 'warning',
            'issued_at' => now()->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($this->actor(['WARNINGS_MANAGE']))
            ->delete(route('trainees.warnings.destroy', [$otherTrainee, $warning]))
            ->assertNotFound();

        $this->assertDatabaseHas('trainee_warnings', ['id' => $warning->id]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function actor(array $permissions = ['TRAINEES_MANAGE']): User
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
            'national_id' => '30101011234567',
            'full_name' => 'متدرب تجريبي',
            'gender' => 'male',
            'birth_date' => '1995-05-01',
            'phone' => '01000000000',
            'email' => 'trainee@example.com',
            'address' => 'القاهرة',
            'education_level_id' => null,
            'qualification' => 'بكالوريوس',
            'years_experience' => 2,
            'job_title' => 'محاسب',
            'job_grade_id' => null,
            'is_active' => 1,
            'providers' => [],
        ], $overrides);
    }
}
