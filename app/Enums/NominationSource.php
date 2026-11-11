<?php

namespace App\Enums;

enum NominationSource: string
{
    case SelfNominated = 'self';
    case Provider = 'provider';

    public function label(): string
    {
        return match ($this) {
            self::SelfNominated => 'تقديم ذاتي',
            self::Provider => 'ترشيح جهة',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
