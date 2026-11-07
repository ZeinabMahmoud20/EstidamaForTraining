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
}
