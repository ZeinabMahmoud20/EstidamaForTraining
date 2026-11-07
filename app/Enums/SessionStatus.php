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
}
