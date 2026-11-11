<?php

namespace Database\Factories;

use App\Enums\NominationSource;
use App\Enums\NominationStatus;
use App\Models\CourseSession;
use App\Models\Nomination;
use App\Models\Trainee;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Nomination> */
class NominationFactory extends Factory
{
    protected $model = Nomination::class;

    public function definition(): array
    {
        return [
            'session_id' => CourseSession::factory(),
            'trainee_id' => Trainee::factory(),
            'source' => NominationSource::SelfNominated,
            'nominated_by_provider_id' => null,
            'application_date' => now(),
            'application_data' => null,
            'status' => NominationStatus::Submitted,
            'rank_no' => null,
            'waitlist_order' => null,
            'decision_by' => null,
            'decision_at' => null,
            'decision_reason' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => NominationStatus::Approved,
            'decision_at' => now(),
        ]);
    }

    public function waitlisted(): static
    {
        return $this->state(fn () => [
            'status' => NominationStatus::Waitlisted,
            'waitlist_order' => 1,
            'decision_at' => now(),
        ]);
    }
}
