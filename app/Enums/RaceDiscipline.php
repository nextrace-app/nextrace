<?php

namespace App\Enums;

enum RaceDiscipline: string
{
    case Run = 'run';
    case Walk = 'walk';
    case Relay = 'relay';
    case Cycle = 'cycle';
    case Hike = 'hike';
    case Other = 'other';
}
