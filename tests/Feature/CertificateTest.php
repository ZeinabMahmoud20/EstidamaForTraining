<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\CourseSession;
use App\Models\Enrollment;
use App\Models\Trainee;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_guests_are_redirected_from_certificates_but_can_verify(): void
    {
        $this->get(route('certificates.index'))->assertRedirect(route('login'));
        $this->get(route('certificates.own'))->assertRedirect(route('login'));
        $this->get(route('certificates.verify', 'UNKNOWN'))->assertOk()->assertSee('لا توجد شهادة');
    }

    public function test_certificate_permissions_are_enforced(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('certificates.index'))->assertForbidden();

        $this->actingAs($this->actor(['ISSUE_CERTIFICATE']))
            ->get(route('certificates.create'))
            ->assertOk();

        $this->actingAs($this->actor(['REVOKE_CERTIFICATE']))
            ->get(route('certificates.create'))
            ->assertForbidden();

        $this->actingAs($this->actor(['CERTIFICATES_VIEW_OWN']))
            ->get(route('certificates.index'))
            ->assertForbidden();

        $this->actingAs($this->actor(['CERTIFICATES_VIEW_OWN']))
            ->get(route('certificates.own'))
            ->assertOk();
    }

    public function test_certificate_template_can_be_created_and_updated(): void
    {
        $actor = $this->actor(['ISSUE_CERTIFICATE']);

        $this->actingAs($actor)
            ->post(route('certificates.templates.store'), [
                'name' => 'قالب رسمي',
                'template_html' => '<h1>{{trainee_name}}</h1>',
                'is_active' => 1,
            ])
            ->assertRedirect();

        $template = CertificateTemplate::firstOrFail();
        $this->assertSame('قالب رسمي', $template->name);

        $this->actingAs($actor)
            ->put(route('certificates.templates.update', $template), [
                'name' => 'قالب محدّث',
                'template_html' => '<h2>{{course_title}}</h2>',
                'is_active' => 1,
            ])
            ->assertRedirect(route('certificates.templates.edit', $template));

        $this->assertSame('قالب محدّث', $template->refresh()->name);
    }

    public function test_certificate_can_be_issued_and_printed_with_template_placeholders(): void
    {
        $enrollment = $this->enrollment('سارة محمد العلي', 'الذكاء الاصطناعي', 'SES-CERT-1');
        $template = CertificateTemplate::create([
            'name' => 'قالب اختبار',
            'template_html' => '<h1>{{trainee_name}}</h1><p>{{course_title}}</p><small>{{certificate_no}}</small>',
            'is_active' => true,
        ]);
        $issuer = $this->actor(['ISSUE_CERTIFICATE']);

        $this->actingAs($issuer)
            ->post(route('certificates.store'), [
                'enrollment_id' => $enrollment->id,
                'template_id' => $template->id,
                'certificate_type' => 'completion',
                'issue_date' => '2026-03-01',
            ])
            ->assertRedirect();

        $certificate = Certificate::firstOrFail();
        $this->assertSame('completion', $certificate->certificate_type->value);
        $this->assertSame('issued', $certificate->status->value);
        $this->assertSame($issuer->id, $certificate->issued_by);
        $this->assertNotEmpty($certificate->certificate_no);
        $this->assertNotEmpty($certificate->verification_code);

        $this->actingAs($issuer)
            ->get(route('certificates.print', $certificate))
            ->assertOk()
            ->assertSee('سارة محمد العلي')
            ->assertSee('الذكاء الاصطناعي')
            ->assertSee($certificate->certificate_no);
    }

    public function test_duplicate_certificate_for_the_same_enrollment_is_rejected(): void
    {
        $enrollment = $this->enrollment('متدرب', 'دورة', 'SES-CERT-2');
        $actor = $this->actor(['ISSUE_CERTIFICATE']);

        $this->actingAs($actor)->post(route('certificates.store'), [
            'enrollment_id' => $enrollment->id,
            'certificate_type' => 'attendance',
            'issue_date' => '2026-03-01',
        ])->assertRedirect();

        $this->actingAs($actor)->post(route('certificates.store'), [
            'enrollment_id' => $enrollment->id,
            'certificate_type' => 'completion',
            'issue_date' => '2026-03-02',
        ])->assertSessionHasErrors('enrollment_id');

        $this->assertDatabaseCount('certificates', 1);
    }

    public function test_certificate_for_withdrawn_enrollment_is_rejected(): void
    {
        $enrollment = $this->enrollment('منسحب', 'دورة', 'SES-CERT-3');
        $enrollment->update(['status' => 'withdrawn']);

        $this->actingAs($this->actor(['ISSUE_CERTIFICATE']))
            ->post(route('certificates.store'), [
                'enrollment_id' => $enrollment->id,
                'certificate_type' => 'attendance',
                'issue_date' => '2026-03-01',
            ])
            ->assertSessionHasErrors('enrollment_id');

        $this->assertDatabaseCount('certificates', 0);
    }

    public function test_certificate_can_be_revoked_with_a_reason(): void
    {
        $certificate = $this->certificate();
        $revoker = $this->actor(['REVOKE_CERTIFICATE']);

        $this->actingAs($revoker)
            ->patch(route('certificates.revoke', $certificate), [])
            ->assertSessionHasErrors('revoke_reason');

        $this->actingAs($revoker)
            ->patch(route('certificates.revoke', $certificate), ['revoke_reason' => 'خطأ في البيانات'])
            ->assertRedirect(route('certificates.show', $certificate));

        $certificate->refresh();
        $this->assertSame('revoked', $certificate->status->value);
        $this->assertSame($revoker->id, $certificate->revoked_by);
        $this->assertNotNull($certificate->revoked_at);
        $this->assertSame('خطأ في البيانات', $certificate->revoke_reason);
    }

    public function test_public_verification_page_shows_certificate_state(): void
    {
        $certificate = $this->certificate();

        $this->get(route('certificates.verify', $certificate->verification_code))
            ->assertOk()
            ->assertSee($certificate->certificate_no)
            ->assertSee('الشهادة صادرة وصحيحة');

        $certificate->update([
            'status' => 'revoked',
            'revoke_reason' => 'إلغاء اختباري',
            'revoked_at' => now(),
        ]);

        $this->get(route('certificates.verify', $certificate->verification_code))
            ->assertOk()
            ->assertSee('ملغاة وغير سارية')
            ->assertSee('إلغاء اختباري');
    }

    private function enrollment(string $traineeName, string $courseTitle, string $sessionCode): Enrollment
    {
        $session = CourseSession::factory()->create(['session_code' => $sessionCode]);
        $session->course()->update(['title' => $courseTitle]);

        return Enrollment::factory()->create([
            'session_id' => $session->id,
            'trainee_id' => Trainee::factory()->create(['full_name' => $traineeName])->id,
        ]);
    }

    private function certificate(): Certificate
    {
        $enrollment = $this->enrollment('متدرب شهادة', 'دورة شهادة', 'SES-CERT-4');

        return Certificate::create([
            'enrollment_id' => $enrollment->id,
            'certificate_no' => 'CERT-TEST-1',
            'certificate_type' => 'completion',
            'issue_date' => '2026-03-01',
            'status' => 'issued',
            'verification_code' => 'VERIFY-TEST-1',
            'issued_by' => $this->actor(['ISSUE_CERTIFICATE'])->id,
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
