<?php

namespace App\Enums;

enum RaceTerrain: string
{
    case Road = 'road';
    case Trail = 'trail';
    case CrossCountry = 'cross_country';
    case Track = 'track';
    case Mixed = 'mixed';
}
