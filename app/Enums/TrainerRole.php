<?php

namespace App\Enums;

enum TrainerRole: string
{
    case Primary = 'primary';
    case Backup = 'backup';

    public function label(): string
    {
        return match ($this) {
            self::Primary => 'أساسي',
            self::Backup => 'احتياطي',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
