<?php

namespace App\Enums;

enum ExamResultStatus: string
{
    case PendingGrading = 'pending_grading';
    case Graded = 'graded';
}
