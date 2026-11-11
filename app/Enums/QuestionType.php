<?php

namespace App\Enums;

enum QuestionType: string
{
    case Mcq = 'mcq';
    case TrueFalse = 'true_false';
    case Text = 'text';

    public function label(): string
    {
        return match ($this) {
            self::Mcq => 'اختيار من متعدد',
            self::TrueFalse => 'صح / خطأ',
            self::Text => 'إجابة نصية',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
