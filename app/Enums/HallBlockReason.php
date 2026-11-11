<?php

namespace App\Enums;

enum HallBlockReason: string
{
    case Maintenance = 'maintenance';
    case Reserved = 'reserved';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Maintenance => 'صيانة',
            self::Reserved => 'محجوزة',
            self::Other => 'أخرى',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
