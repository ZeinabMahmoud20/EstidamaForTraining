<?php

namespace Database\Factories;

use App\Models\Equipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Equipment> */
class EquipmentFactory extends Factory
{
    protected $model = Equipment::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'بروجيكتور', 'شاشة عرض', 'سبورة ذكية', 'ميكروفون', 'نظام صوت',
                'كاميرا', 'حاسب آلي', 'طابعة', 'واي فاي', 'مكيف',
            ]).' '.fake()->unique()->numerify('##'),
        ];
    }
}
