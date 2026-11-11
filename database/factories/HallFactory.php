<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Hall;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Hall> */
class HallFactory extends Factory
{
    protected $model = Hall::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'code' => 'HALL-'.fake()->unique()->numerify('###'),
            'name' => 'قاعة '.fake()->randomElement(['التدريب', 'الاجتماعات', 'العروض', 'الورش', 'المؤتمرات']).' '.fake()->unique()->numerify('##'),
            'capacity' => fake()->randomElement([10, 15, 20, 25, 30, 40, 50]),
            'floor' => fake()->randomElement(['الأول', 'الثاني', 'الثالث']),
            'location_description' => fake()->sentence(6),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
