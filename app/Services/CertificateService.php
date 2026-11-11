<?php

namespace App\Services;

use App\Enums\CertificateStatus;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Issues and revokes training certificates.
 */
class CertificateService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function issue(array $data, User $actor): Certificate
    {
        return DB::transaction(function () use ($data, $actor) {
            $enrollment = Enrollment::query()->findOrFail((int) $data['enrollment_id']);

            return Certificate::create([
                'enrollment_id' => $enrollment->id,
                'template_id' => $data['template_id'] ?? null,
                'certificate_no' => $this->certificateNumber(),
                'certificate_type' => $data['certificate_type'],
                'issue_date' => $data['issue_date'] ?? now()->toDateString(),
                'expiry_date' => $data['expiry_date'] ?? null,
                'status' => CertificateStatus::Issued,
                'verification_code' => $this->verificationCode(),
                'issued_by' => $actor->id,
            ]);
        });
    }

    public function revoke(Certificate $certificate, string $reason, User $actor): Certificate
    {
        $certificate->update([
            'status' => CertificateStatus::Revoked,
            'revoked_by' => $actor->id,
            'revoked_at' => now(),
            'revoke_reason' => $reason,
        ]);

        return $certificate->refresh();
    }

    private function certificateNumber(): string
    {
        do {
            $number = 'CERT-'.now()->format('Y').'-'.strtoupper(Str::random(6));
        } while (Certificate::where('certificate_no', $number)->exists());

        return $number;
    }

    private function verificationCode(): string
    {
        do {
            $code = strtoupper(Str::random(40));
        } while (Certificate::where('verification_code', $code)->exists());

        return $code;
    }
}
