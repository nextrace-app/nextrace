<?php

namespace App\Support;

use App\Enums\RaceStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\Race;
use App\Models\RacePrice;
use Illuminate\Support\Carbon;

/**
 * Where the registration of a race stands today, worked out from the race,
 * its price tiers and the registration fields of its event.
 *
 * With price tiers, a race is open while one of them is valid today; without
 * them, it is open when the event says so. The dates are days in Athens.
 */
class RaceRegistration
{
    /** How many days before the current tier ends the price counts as rising soon. */
    public const RISING_SOON_DAYS = 7;

    public const OPEN = 'open';

    public const FREE = 'free';

    public const SOLD_OUT = 'sold_out';

    public const CLOSED = 'closed';

    public const NOT_OPEN = 'not_open';

    public const UNKNOWN = 'unknown';

    /**
     * The states from the most to the least useful to a runner; an event shows
     * the first one any of its races is in.
     *
     * @var list<string>
     */
    public const PRIORITY = [self::OPEN, self::FREE, self::NOT_OPEN, self::SOLD_OUT, self::CLOSED, self::UNKNOWN];

    public static function today(): string
    {
        return Carbon::now('Europe/Athens')->toDateString();
    }

    /**
     * Whether a price tier is over ('past'), the one valid today ('current'),
     * or still to come or undated (null).
     */
    public static function priceState(RacePrice $price, ?RacePrice $current, string $today): ?string
    {
        if ($price->valid_until !== null && $price->valid_until->toDateString() < $today) {
            return 'past';
        }

        return $current !== null && $price->is($current) ? 'current' : null;
    }

    /**
     * The first tier valid today, if any.
     */
    public static function currentPrice(Race $race, string $today): ?RacePrice
    {
        return $race->prices->first(fn (RacePrice $price) => ($price->valid_from === null || $price->valid_from->toDateString() <= $today)
            && ($price->valid_until === null || $price->valid_until->toDateString() >= $today));
    }

    public static function status(Race $race, Event $event, string $today): string
    {
        if ($race->sold_out || $event->registration_status === RegistrationStatus::SoldOut) {
            return self::SOLD_OUT;
        }

        $now = Carbon::now();

        if ($race->status === RaceStatus::Cancelled
            || $race->status === RaceStatus::Completed
            || $event->registration_status === RegistrationStatus::Closed
            || ($event->registration_closes_at !== null && $event->registration_closes_at->lt($now))) {
            return self::CLOSED;
        }

        if ($event->registration_status === RegistrationStatus::NotOpen
            || ($event->registration_opens_at !== null && $event->registration_opens_at->gt($now))) {
            return self::NOT_OPEN;
        }

        if ($race->prices->isNotEmpty()) {
            $current = self::currentPrice($race, $today);

            if ($current === null) {
                $allPast = $race->prices->every(fn (RacePrice $price) => $price->valid_until !== null && $price->valid_until->toDateString() < $today);

                return $allPast ? self::CLOSED : self::NOT_OPEN;
            }

            return $current->amount_cents === 0 ? self::FREE : self::OPEN;
        }

        return $event->registration_status === RegistrationStatus::Open ? self::OPEN : self::UNKNOWN;
    }

    /**
     * The last day of the current price when a dearer tier follows within a
     * week, so that the page can say the price rises soon.
     */
    public static function priceRisesAfter(Race $race, string $today): ?string
    {
        $current = self::currentPrice($race, $today);

        if ($current?->valid_until === null) {
            return null;
        }

        $lastDay = $current->valid_until->toDateString();

        if ($lastDay > Carbon::parse($today)->addDays(self::RISING_SOON_DAYS)->toDateString()) {
            return null;
        }

        $dearer = $race->prices->contains(fn (RacePrice $price) => $price->valid_from !== null
            && $price->valid_from->toDateString() > $lastDay
            && $price->amount_cents > $current->amount_cents);

        return $dearer ? $lastDay : null;
    }
}
