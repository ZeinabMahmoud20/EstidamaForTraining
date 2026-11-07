<?php

namespace App\Enums;

enum EvalEvaluator: string
{
    case Trainee = 'trainee';
    case Trainer = 'trainer';
    case Supervisor = 'supervisor';
    case Provider = 'provider';
}
