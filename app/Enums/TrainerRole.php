<?php

namespace App\Enums;

enum TrainerRole: string
{
    case Primary = 'primary';
    case Backup = 'backup';
}
