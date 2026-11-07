<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Late = 'late';
    case EarlyLeave = 'early_leave';
    case AbsentExcused = 'absent_excused';
    case AbsentUnexcused = 'absent_unexcused';
}
