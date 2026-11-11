<?php

namespace App\Enums;

enum ExamType: string
{
    case Pre = 'pre';
    case Post = 'post';
    case Final = 'final';
    case Practical = 'practical';

    public function label(): string
    {
        return match ($this) {
            self::Pre => 'قبلي',
            self::Post => 'بعدي',
            self::Final => 'نهائي',
            self::Practical => 'عملي',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
