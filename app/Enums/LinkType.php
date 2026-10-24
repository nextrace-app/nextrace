<?php

namespace App\Enums;

enum LinkType: string
{
    case Website = 'website';
    case Facebook = 'facebook';
    case Instagram = 'instagram';
    case Regulations = 'regulations';
    case Route = 'route';
    case Results = 'results';
    case Photos = 'photos';
    case Timing = 'timing';
    case Other = 'other';
}
