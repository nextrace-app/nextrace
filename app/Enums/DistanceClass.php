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

    /**
     * The class a distance falls in, for races that do not name one.
     */
    public static function forMeters(int $metres): self
    {
        return match (true) {
            $metres === 5000 => self::FiveK,
            $metres === 10000 => self::TenK,
            in_array($metres, [21097, 21098, 21100], true) => self::HalfMarathon,
            in_array($metres, [42195, 42200], true) => self::Marathon,
            $metres > 42200 => self::Ultra,
            default => self::Other,
        };
    }
}
