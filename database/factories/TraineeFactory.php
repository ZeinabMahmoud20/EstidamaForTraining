<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Models\Trainee;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Trainee> */
class TraineeFactory extends Factory
{
    protected $model = Trainee::class;

    public function definition(): array
    {
        $names = [
            'محمد أحمد السيد',
            'فاطمة علي حسن',
            'عمرو خالد إبراهيم',
            'مريم مصطفى كامل',
            'حسام الدين فؤاد',
            'نور الهدى صابر',
        ];

        return [
            'national_id' => fake()->unique()->numerify('3#############'),
            'full_name' => fake()->randomElement($names),
            'gender' => fake()->randomElement(Gender::cases()),
            'birth_date' => fake()->dateTimeBetween('-55 years', '-18 years')->format('Y-m-d'),
            'phone' => fake()->numerify('01#########'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->city().' - '.fake()->streetName(),
            'education_level_id' => null,
            'qualification' => fake()->randomElement(['بكالوريوس', 'ماجستير', 'دبلوم', 'ثانوية عامة']),
            'years_experience' => fake()->numberBetween(0, 30),
            'job_title' => fake()->randomElement(['محاسب', 'مهندس', 'إداري', 'فني', 'معلم']),
            'job_grade_id' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
