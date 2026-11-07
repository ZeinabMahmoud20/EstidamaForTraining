<?php

namespace Database\Factories;

use App\Models\TrainingProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TrainingProvider> */
class TrainingProviderFactory extends Factory
{
    protected $model = TrainingProvider::class;

    public function definition(): array
    {
        return [
            'name' => 'جهة تدريب '.fake()->unique()->company(),
            'contact_person' => fake()->name(),
            'phone' => fake()->numerify('02#########'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->city().' - '.fake()->streetName(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
