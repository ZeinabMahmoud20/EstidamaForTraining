<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Enrolled = 'enrolled';
    case Completed = 'completed';
    case Failed = 'failed';
    case Withdrawn = 'withdrawn';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Enrolled => 'مسجل',
            self::Completed => 'مكتمل',
            self::Failed => 'راسب',
            self::Withdrawn => 'منسحب',
            self::Dismissed => 'مفصول',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Enrolled => 'brand',
            self::Completed => 'ok',
            self::Failed => 'danger',
            self::Withdrawn => 'warn',
            self::Dismissed => 'danger',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
