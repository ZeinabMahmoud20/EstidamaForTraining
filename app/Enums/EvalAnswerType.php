<?php

namespace App\Enums;

enum EvalAnswerType: string
{
    case Scale15 = 'scale_1_5';
    case Text = 'text';

    public function label(): string
    {
        return match ($this) {
            self::Scale15 => 'مقياس من 1 إلى 5',
            self::Text => 'إجابة نصية',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
