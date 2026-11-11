<?php

namespace App\Enums;

enum SessionStatus: string
{
    case Draft = 'draft';
    case OpenForRegistration = 'open_for_registration';
    case ClosedRegistration = 'closed_registration';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'مسودة',
            self::OpenForRegistration => 'مفتوحة للتسجيل',
            self::ClosedRegistration => 'مغلق التسجيل',
            self::InProgress => 'جارية',
            self::Completed => 'مكتملة',
            self::Cancelled => 'ملغاة',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::OpenForRegistration => 'ok',
            self::ClosedRegistration => 'warn',
            self::InProgress => 'brand',
            self::Completed => 'ok',
            self::Cancelled => 'danger',
        };
    }

    /**
     * Allowed manual status transitions. Publishing/registration changes are
     * guarded by SESSIONS_PUBLISH on the route.
     *
     * @return list<self>
     */
    public function nextStatuses(): array
    {
        return match ($this) {
            self::Draft => [self::OpenForRegistration, self::Cancelled],
            self::OpenForRegistration => [self::ClosedRegistration, self::Cancelled],
            self::ClosedRegistration => [self::InProgress, self::OpenForRegistration, self::Cancelled],
            self::InProgress => [self::Completed, self::Cancelled],
            self::Completed => [],
            self::Cancelled => [self::Draft],
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
