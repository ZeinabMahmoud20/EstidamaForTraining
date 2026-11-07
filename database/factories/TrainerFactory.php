<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\TrainerType;
use App\Models\Trainer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Trainer> */
class TrainerFactory extends Factory
{
    protected $model = Trainer::class;

    public function definition(): array
    {
        $names = [
            'أحمد محمود عبد الله',
            'منى إبراهيم حسن',
            'خالد سعيد المصري',
            'سارة محمد العلي',
            'يوسف عبد الرحمن',
            'نورهان طارق فؤاد',
        ];

        return [
            'trainer_type' => TrainerType::Internal,
            'provider_id' => null,
            'branch_id' => null,
            'national_id' => fake()->unique()->numerify('2#############'),
            'full_name' => fake()->randomElement($names),
            'gender' => fake()->randomElement(Gender::cases()),
            'birth_date' => fake()->dateTimeBetween('-55 years', '-25 years')->format('Y-m-d'),
            'phone' => fake()->numerify('01#########'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->city().' - '.fake()->streetName(),
            'highest_qualification' => fake()->randomElement(['بكالوريوس', 'ماجستير', 'دكتوراه']),
            'years_experience' => fake()->numberBetween(3, 25),
            'bio' => fake()->sentence(12),
            'hourly_rate' => fake()->numberBetween(200, 1500),
            'bank_account' => null,
            'is_active' => true,
        ];
    }

    public function external(): static
    {
        return $this->state(['trainer_type' => TrainerType::External]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
