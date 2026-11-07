<?php

namespace App\Enums;

enum HallBlockReason: string
{
    case Maintenance = 'maintenance';
    case Reserved = 'reserved';
    case Other = 'other';
}
