<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Branch> */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('BR???')),
            'name' => 'فرع '.fake()->city(),
            'city' => fake()->city(),
            'is_active' => true,
        ];
    }
}
