<?php

namespace App\Enums;

enum CriteriaOperator: string
{
    case Eq = 'eq';
    case Gte = 'gte';
    case Lte = 'lte';
    case In = 'in';
    case NotIn = 'not_in';

    public function label(): string
    {
        return match ($this) {
            self::Eq => 'يساوي',
            self::Gte => 'أكبر من أو يساوي',
            self::Lte => 'أصغر من أو يساوي',
            self::In => 'ضمن القائمة',
            self::NotIn => 'ليس ضمن القائمة',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
