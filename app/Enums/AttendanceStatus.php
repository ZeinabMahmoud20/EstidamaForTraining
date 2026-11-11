<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Late = 'late';
    case EarlyLeave = 'early_leave';
    case AbsentExcused = 'absent_excused';
    case AbsentUnexcused = 'absent_unexcused';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'حاضر',
            self::Late => 'متأخر',
            self::EarlyLeave => 'انصراف مبكر',
            self::AbsentExcused => 'غائب بعذر',
            self::AbsentUnexcused => 'غائب بدون عذر',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Present => 'ok',
            self::Late => 'warn',
            self::EarlyLeave => 'warn',
            self::AbsentExcused => 'brand',
            self::AbsentUnexcused => 'danger',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
