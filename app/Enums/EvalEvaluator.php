<?php

namespace App\Enums;

enum EvalEvaluator: string
{
    case Trainee = 'trainee';
    case Trainer = 'trainer';
    case Supervisor = 'supervisor';
    case Provider = 'provider';

    public function label(): string
    {
        return match ($this) {
            self::Trainee => 'متدرب',
            self::Trainer => 'مدرب',
            self::Supervisor => 'مشرف',
            self::Provider => 'جهة تدريب',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
