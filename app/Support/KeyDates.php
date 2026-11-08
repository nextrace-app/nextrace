<?php

namespace App\Support;

use App\Enums\KeyDateType;
use App\Models\Event;
use App\Models\EventDate;

/**
 * The timeline of important dates of an event: the dates entered for it plus
 * the opening and closing of its registration, in date order, each marked as
 * over ('past'), the first one still to come ('next') or later ('upcoming').
 *
 * The registration dates are the wall-clock days the event fields hold; one is
 * left out when a date of the same type on the same day was entered.
 */
class KeyDates
{
    public const PAST = 'past';

    public const NEXT = 'next';

    public const UPCOMING = 'upcoming';

    /**
     * @return array<int, array{type: string, from: string, to: string|null, label: string|null, state: string}>
     */
    public static function for(Event $event, string $today): array
    {
        $dates = $event->dates->map(fn (EventDate $date) => [
            'type' => $date->type->value,
            'from' => $date->starts_on->toDateString(),
            'to' => $date->ends_on !== null && ! $date->ends_on->isSameDay($date->starts_on) ? $date->ends_on->toDateString() : null,
            'label' => $date->translatedOrNull('label'),
            'sort' => $date->sort_order,
        ]);

        $fromEvent = [
            KeyDateType::RegistrationOpens->value => $event->registration_opens_at?->toDateString(),
            KeyDateType::RegistrationCloses->value => $event->registration_closes_at?->toDateString(),
        ];

        foreach ($fromEvent as $type => $day) {
            if ($day !== null && ! $dates->contains(fn (array $date) => $date['type'] === $type && $date['from'] === $day)) {
                $dates->push(['type' => $type, 'from' => $day, 'to' => null, 'label' => null, 'sort' => -1]);
            }
        }

        $next = false;

        return $dates
            ->sortBy([['from', 'asc'], ['sort', 'asc']])
            ->values()
            ->map(function (array $date) use ($today, &$next): array {
                $state = self::UPCOMING;

                if (($date['to'] ?? $date['from']) < $today) {
                    $state = self::PAST;
                } elseif (! $next) {
                    $state = self::NEXT;
                    $next = true;
                }

                return [
                    'type' => $date['type'],
                    'from' => $date['from'],
                    'to' => $date['to'],
                    'label' => $date['label'],
                    'state' => $state,
                ];
            })
            ->all();
    }
}
