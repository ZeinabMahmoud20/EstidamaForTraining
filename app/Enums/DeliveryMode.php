<?php

namespace App\Enums;

enum DeliveryMode: string
{
    case Onsite = 'onsite';
    case Online = 'online';
    case Hybrid = 'hybrid';

    public function label(): string
    {
        return match ($this) {
            self::Onsite => 'حضوري',
            self::Online => 'عن بعد',
            self::Hybrid => 'مدمج',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
