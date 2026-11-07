<?php

namespace App\Enums;

enum TrainerType: string
{
    case Internal = 'internal';
    case External = 'external';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'داخلي',
            self::External => 'خارجي',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
