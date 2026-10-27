<?php

namespace App\Enums;

enum DistanceClass: string
{
    case FiveK = '5k';
    case TenK = '10k';
    case HalfMarathon = 'half_marathon';
    case Marathon = 'marathon';
    case Ultra = 'ultra';
    case Other = 'other';
    case Kids = 'kids';
}
