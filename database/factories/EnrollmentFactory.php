<?php

namespace Database\Factories;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Nomination;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Enrollment> */
class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        return [
            'nomination_id' => function () {
                $nomination = Nomination::factory()->approved()->create();

                return $nomination->id;
            },
            'session_id' => fn (array $attributes) => Nomination::find($attributes['nomination_id'])?->session_id,
            'trainee_id' => fn (array $attributes) => Nomination::find($attributes['nomination_id'])?->trainee_id,
            'status' => EnrollmentStatus::Enrolled,
            'attendance_pct' => null,
            'final_score_pct' => null,
            'is_passed' => null,
            'is_certificate_eligible' => null,
            'completed_at' => null,
        ];
    }
}
