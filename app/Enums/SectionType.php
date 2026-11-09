<?php

namespace App\Enums;

/**
 * What a section of the practical information of an event is about.
 */
enum SectionType: string
{
    case Transport = 'transport';
    case BagDrop = 'bag_drop';
    case Expo = 'expo';
    case RoadClosures = 'road_closures';
    case Medical = 'medical';
    case Other = 'other';
}
