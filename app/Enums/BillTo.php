<?php

namespace App\Enums;

enum BillTo: string
{
    case Trainee = 'trainee';
    case Provider = 'provider';

    public function label(): string
    {
        return match ($this) {
            self::Trainee => 'المتدرب',
            self::Provider => 'جهة التدريب',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
