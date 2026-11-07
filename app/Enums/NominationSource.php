<?php

namespace App\Enums;

enum NominationSource: string
{
    case SelfNominated = 'self';
    case Provider = 'provider';
}
