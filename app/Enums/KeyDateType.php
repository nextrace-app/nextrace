<?php

namespace App\Enums;

/**
 * What an important date of an event is about.
 */
enum KeyDateType: string
{
    case RegistrationOpens = 'registration_opens';
    case RegistrationCloses = 'registration_closes';
    case ChangesDeadline = 'changes_deadline';
    case Expo = 'expo';
    case BibPickup = 'bib_pickup';
    case Other = 'other';
}
