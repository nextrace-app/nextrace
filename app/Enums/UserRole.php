<?php

namespace App\Enums;

/**
 * The roles a user can hold. A regular user has none of them.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Organizer = 'organizer';
}
