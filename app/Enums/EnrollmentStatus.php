<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Enrolled = 'enrolled';
    case Completed = 'completed';
    case Failed = 'failed';
    case Withdrawn = 'withdrawn';
    case Dismissed = 'dismissed';
}
