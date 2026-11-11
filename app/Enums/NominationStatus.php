<?php

namespace App\Enums;

enum NominationStatus: string
{
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Waitlisted = 'waitlisted';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'مُقدَّم',
            self::UnderReview => 'قيد المراجعة',
            self::Approved => 'مقبول',
            self::Rejected => 'مرفوض',
            self::Waitlisted => 'قائمة الانتظار',
            self::Withdrawn => 'منسحب',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Submitted => 'neutral',
            self::UnderReview => 'warn',
            self::Approved => 'ok',
            self::Rejected => 'danger',
            self::Waitlisted => 'warn',
            self::Withdrawn => 'neutral',
        };
    }

    /** @return list<self> */
    public function nextStatuses(): array
    {
        return match ($this) {
            self::Submitted => [self::UnderReview, self::Approved, self::Rejected, self::Waitlisted, self::Withdrawn],
            self::UnderReview => [self::Approved, self::Rejected, self::Waitlisted, self::Withdrawn],
            self::Waitlisted => [self::Approved, self::Rejected, self::UnderReview, self::Withdrawn],
            self::Approved => [self::Withdrawn],
            self::Rejected => [self::UnderReview],
            self::Withdrawn => [self::UnderReview],
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
