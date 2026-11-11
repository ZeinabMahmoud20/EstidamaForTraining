<?php

namespace App\Enums;

enum WarningType: string
{
    case Warning = 'warning';
    case Issue = 'issue';

    public function label(): string
    {
        return match ($this) {
            self::Warning => 'إنذار',
            self::Issue => 'مشكلة',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Warning => 'warn',
            self::Issue => 'danger',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
