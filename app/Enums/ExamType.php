<?php

namespace App\Enums;

enum ExamType: string
{
    case Pre = 'pre';
    case Post = 'post';
    case Final = 'final';
    case Practical = 'practical';
}
