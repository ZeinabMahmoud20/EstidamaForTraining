<?php

namespace App\Enums;

enum KitStatus: string
{
    case Pending = 'pending';
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
