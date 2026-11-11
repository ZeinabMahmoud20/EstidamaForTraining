<?php

namespace App\Enums;

enum ExamResultStatus: string
{
    case PendingGrading = 'pending_grading';
    case Graded = 'graded';

    public function label(): string
    {
        return match ($this) {
            self::PendingGrading => 'بانتظار التصحيح',
            self::Graded => 'مصحح',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::PendingGrading => 'warn',
            self::Graded => 'ok',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
