<?php

namespace App\Enums;

enum EvalTarget: string
{
    case Trainer = 'trainer';
    case Program = 'program';
    case Trainee = 'trainee';
    case Venue = 'venue';
    case Operations = 'operations';

    public function label(): string
    {
        return match ($this) {
            self::Trainer => 'المدرب',
            self::Program => 'البرنامج التدريبي',
            self::Trainee => 'المتدرب',
            self::Venue => 'القاعة والتجهيزات',
            self::Operations => 'الخدمات الإدارية',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
