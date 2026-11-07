<?php

namespace Database\Factories;

use App\Enums\CourseLevel;
use App\Enums\DeliveryMode;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Course> */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        $titles = [
            'إدارة المشاريع الاحترافية',
            'مهارات القيادة الفعّالة',
            'المحاسبة المالية',
            'أمن المعلومات',
            'إدارة الموارد البشرية',
            'السلامة والصحة المهنية',
            'التخطيط الاستراتيجي',
            'خدمة العملاء المتميزة',
        ];

        return [
            'code' => strtoupper(fake()->unique()->bothify('CRS-####')),
            'title' => fake()->randomElement($titles).' '.fake()->unique()->numerify('##'),
            'description' => fake()->sentence(14),
            'objectives' => fake()->sentence(10),
            'content_outline' => fake()->paragraph(3),
            'target_audience' => fake()->sentence(6),
            'category_id' => null,
            'course_type_id' => null,
            'level' => fake()->randomElement(array_map(fn (CourseLevel $level) => $level->value, CourseLevel::cases())),
            'total_hours' => fake()->randomElement([6, 9, 12, 18, 24, 30]),
            'days_count' => fake()->numberBetween(1, 6),
            'default_delivery_mode' => fake()->randomElement(array_map(fn (DeliveryMode $mode) => $mode->value, DeliveryMode::cases())),
            'price' => fake()->randomElement([0, 500, 750, 1000, 1500, 2000]),
            'min_trainees' => 5,
            'max_trainees' => 20,
            'min_attendance_pct' => 75,
            'pass_score_pct' => 60,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
