<?php

namespace Database\Factories;

use App\Enums\DeliveryMode;
use App\Enums\SessionStatus;
use App\Models\Course;
use App\Models\CourseSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CourseSession> */
class CourseSessionFactory extends Factory
{
    protected $model = CourseSession::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 month', '+3 months');
        $end = (clone $start)->modify('+2 days');

        return [
            'course_id' => Course::factory(),
            'session_code' => strtoupper(fake()->unique()->bothify('SES-####')),
            'provider_id' => null,
            'branch_id' => null,
            'accreditation_id' => null,
            'delivery_mode' => DeliveryMode::Onsite,
            'online_url' => null,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'registration_open' => null,
            'registration_close' => null,
            'max_trainees' => 20,
            'price' => 1000,
            'status' => SessionStatus::Draft,
            'created_by' => null,
        ];
    }

    public function online(): static
    {
        return $this->state([
            'delivery_mode' => DeliveryMode::Online,
            'online_url' => 'https://meet.example.com/session',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => SessionStatus::Cancelled]);
    }
}
