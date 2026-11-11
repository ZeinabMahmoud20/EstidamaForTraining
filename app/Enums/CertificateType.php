<?php

namespace App\Enums;

enum CertificateType: string
{
    case Attendance = 'attendance';
    case Completion = 'completion';
    case Appreciation = 'appreciation';

    public function label(): string
    {
        return match ($this) {
            self::Attendance => 'شهادة حضور',
            self::Completion => 'شهادة إتمام',
            self::Appreciation => 'شهادة تقدير',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
