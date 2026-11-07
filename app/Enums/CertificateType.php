<?php

namespace App\Enums;

enum CertificateType: string
{
    case Attendance = 'attendance';
    case Completion = 'completion';
    case Appreciation = 'appreciation';
}
