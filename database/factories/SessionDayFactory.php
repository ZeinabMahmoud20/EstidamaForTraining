<?php

namespace Database\Factories;

use App\Models\CourseSession;
use App\Models\SessionDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SessionDay> */
class SessionDayFactory extends Factory
{
    protected $model = SessionDay::class;

    public function definition(): array
    {
        return [
            'session_id' => CourseSession::factory(),
            'day_no' => 1,
            'day_date' => fake()->dateTimeBetween('-1 month', '+3 months')->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'hall_id' => null,
            'online_url' => null,
        ];
    }
}
