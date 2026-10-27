<?php

namespace App\Enums;

enum EventStatus: string
{
    case Scheduled = 'scheduled';
    case Postponed = 'postponed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
}
