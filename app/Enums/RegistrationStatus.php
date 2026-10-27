<?php

namespace App\Enums;

enum RegistrationStatus: string
{
    case Unknown = 'unknown';
    case NotOpen = 'not_open';
    case Open = 'open';
    case Closed = 'closed';
    case SoldOut = 'sold_out';
}
