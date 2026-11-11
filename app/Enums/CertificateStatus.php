<?php

namespace App\Enums;

enum CertificateStatus: string
{
    case Issued = 'issued';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Issued => 'صادرة',
            self::Revoked => 'ملغاة',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Issued => 'ok',
            self::Revoked => 'danger',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
