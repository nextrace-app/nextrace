<?php

namespace App\Support;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Race;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * An event as one calendar entry: the start of its main race, with the other
 * races listed in the description. Written by hand as iCalendar (RFC 5545) for
 * Apple Calendar and Outlook, and as a link that opens Google Calendar.
 *
 * Race times are stored as wall-clock times in Athens; the entry carries them
 * in UTC, which every calendar reads the same way. A race without a known start
 * time becomes an all-day entry.
 */
class EventCalendar
{
    public const TIMEZONE = 'Europe/Athens';

    /** How long an entry lasts when the race has no time limit, in minutes. */
    public const DEFAULT_MINUTES = 180;

    /**
     * What the countdown card needs, or null when it should not be shown: the
     * event is not on, its date is not confirmed, or the main race has started
     * on a past day.
     *
     * @return array{starts_on: string, time: string|null, today: string, ics_url: string, google_url: string}|null
     */
    public static function countdown(Event $event, string $today): ?array
    {
        $race = $event->mainRace;

        if ($race === null || $event->status !== EventStatus::Scheduled || ! $event->date_confirmed) {
            return null;
        }

        $day = $race->starts_at->toDateString();

        if ($day < $today) {
            return null;
        }

        return [
            'starts_on' => $day,
            'time' => $race->start_time_known ? $race->starts_at->format('H:i') : null,
            'today' => $today,
            'ics_url' => route(Locale::routeName('events.calendar'), $event),
            'google_url' => self::googleUrl($event),
        ];
    }

    /**
     * The iCalendar file of the event.
     */
    public static function ics(Event $event): string
    {
        $race = $event->mainRace;
        abort_if($race === null, 404);

        [$start, $end] = self::span($race);
        $allDay = ! $race->start_time_known;

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//NextRace//Events//'.Str::upper(app()->getLocale()),
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:event-'.$event->id.'@'.(parse_url(config()->string('app.url'), PHP_URL_HOST) ?: 'nextrace'),
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            $allDay ? 'DTSTART;VALUE=DATE:'.$start->format('Ymd') : 'DTSTART:'.$start->format('Ymd\THis\Z'),
            $allDay ? 'DTEND;VALUE=DATE:'.$end->format('Ymd') : 'DTEND:'.$end->format('Ymd\THis\Z'),
            'SUMMARY:'.self::text(self::summary($event, $race)),
            'LOCATION:'.self::text(self::location($event, $race)),
            'DESCRIPTION:'.self::text(self::description($event)),
            'URL:'.self::eventUrl($event),
            'STATUS:CONFIRMED',
            'TRANSP:OPAQUE',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", array_map(self::fold(...), $lines))."\r\n";
    }

    /**
     * A link that opens Google Calendar with the entry filled in.
     */
    public static function googleUrl(Event $event): string
    {
        $race = $event->mainRace;
        abort_if($race === null, 404);

        [$start, $end] = self::span($race);
        $format = $race->start_time_known ? 'Ymd\THis\Z' : 'Ymd';

        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => self::summary($event, $race),
            'dates' => $start->format($format).'/'.$end->format($format),
            'details' => self::description($event),
            'location' => self::location($event, $race),
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Start and end of the entry: UTC moments for a timed race, or the day and
     * the day after for an all-day one (the end of an all-day entry is exclusive).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private static function span(Race $race): array
    {
        if (! $race->start_time_known) {
            $day = Carbon::parse($race->starts_at->toDateString());

            return [$day, $day->copy()->addDay()];
        }

        $start = Carbon::parse($race->starts_at->format('Y-m-d H:i:s'), self::TIMEZONE)->utc();

        return [$start, $start->copy()->addMinutes($race->time_limit_minutes ?? self::DEFAULT_MINUTES)];
    }

    private static function summary(Event $event, Race $race): string
    {
        $name = $event->translated('name');
        $raceName = $race->translated('name');

        // An event with several races names the one the entry is for.
        return $event->races->count() > 1 && $raceName !== '' && ! Str::contains($name, $raceName) ? "{$name}: {$raceName}" : $name;
    }

    private static function location(Event $event, Race $race): string
    {
        return collect([$race->translatedOrNull('start_location'), $event->city])->filter()->unique()->implode(', ');
    }

    /**
     * Every race of the event with its day and time, then the event page.
     */
    private static function description(Event $event): string
    {
        $races = $event->races->map(function (Race $race): string {
            $when = $race->starts_at->copy()->settings(['locale' => app()->getLocale()])->isoFormat($race->start_time_known ? 'D MMM, HH:mm' : 'D MMM');

            return "{$race->translated('name')}: {$when}";
        });

        return implode("\n", [...$races->all(), self::eventUrl($event)]);
    }

    private static function eventUrl(Event $event): string
    {
        return route(Locale::routeName('events.show'), $event);
    }

    /**
     * Escape text as iCalendar wants it: backslash, semicolon, comma and new line.
     */
    private static function text(string $value): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\;', '\,', '\n', '\n'], $value);
    }

    /**
     * Fold a line longer than 75 octets into continuation lines, without
     * splitting a multi-byte character.
     */
    private static function fold(string $line): string
    {
        $parts = [];

        while (strlen($line) > 75) {
            $cut = 75;

            while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) {
                $cut--;
            }

            $parts[] = substr($line, 0, $cut);
            $line = ' '.substr($line, $cut);
        }

        $parts[] = $line;

        return implode("\r\n", $parts);
    }
}
