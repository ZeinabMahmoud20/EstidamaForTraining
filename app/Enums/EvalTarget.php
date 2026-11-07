<?php

namespace App\Enums;

enum EvalTarget: string
{
    case Trainer = 'trainer';
    case Program = 'program';
    case Trainee = 'trainee';
    case Venue = 'venue';
    case Operations = 'operations';
}
