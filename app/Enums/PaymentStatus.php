<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'بانتظار الاعتماد',
            self::Approved => 'معتمد',
            self::Paid => 'مدفوع',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'warn',
            self::Approved => 'brand',
            self::Paid => 'ok',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
