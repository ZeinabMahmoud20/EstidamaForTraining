<?php

namespace Database\Factories;

use App\Models\Accreditation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Accreditation> */
class AccreditationFactory extends Factory
{
    protected $model = Accreditation::class;

    public function definition(): array
    {
        return [
            'name' => 'اعتماد '.fake()->unique()->word(),
            'issuing_body' => fake()->company(),
            'description' => fake()->sentence(),
        ];
    }
}
