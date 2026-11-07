<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'password' => 'password',
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
