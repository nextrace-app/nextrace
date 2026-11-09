<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Race;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 1 November 2026, 12:00 in Athens.
        $this->travelTo('2026-11-01 10:00:00');
    }

    /**
     * A published event whose main race starts at the given wall-clock time in Athens.
     *
     * @param  array<string, mixed>  $race
     * @param  array<string, mixed>  $event
     */
    private function event(array $race = [], array $event = []): Event
    {
        $model = Event::factory()->create([
            'slug' => 'athens-marathon',
            'name' => ['en' => 'Athens Marathon', 'el' => 'Μαραθώνιος Αθήνας'],
            'city' => 'Athens',
            'status' => EventStatus::Scheduled,
            'date_confirmed' => true,
            'published_at' => now()->subWeek(),
            ...$event,
        ]);

        Race::factory()->for($model)->create([
            'slug' => 'marathon',
            'name' => ['en' => 'Marathon', 'el' => 'Μαραθώνιος'],
            'is_main' => true,
            'starts_at' => '2026-11-08 08:30:00',
            'start_time_known' => true,
            'time_limit_minutes' => 480,
            'start_location' => ['en' => 'Marathon; Attica', 'el' => 'Μαραθώνας'],
            ...$race,
        ]);

        return $model;
    }

    private function ics(Event $event): string
    {
        return $this->get(route('en.events.calendar', $event))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
            ->assertHeader('Content-Disposition', 'attachment; filename="athens-marathon.ics"')
            ->getContent();
    }

    public function test_the_calendar_file_holds_the_main_race_in_utc_and_ends_after_its_time_limit(): void
    {
        $ics = $this->ics($this->event());
        // Folded lines continue after a CRLF and a space.
        $unfolded = str_replace("\r\n ", '', $ics);

        $this->assertStringStartsWith("BEGIN:VCALENDAR\r\nVERSION:2.0\r\n", $ics);
        $this->assertStringEndsWith("END:VCALENDAR\r\n", $ics);
        // 08:30 in Athens in November (UTC+2) is 06:30 UTC; eight hours later is 14:30.
        $this->assertStringContainsString("DTSTART:20261108T063000Z\r\n", $ics);
        $this->assertStringContainsString("DTEND:20261108T143000Z\r\n", $ics);
        $this->assertStringContainsString("SUMMARY:Athens Marathon\r\n", $ics);
        // Semicolons and commas are escaped.
        $this->assertStringContainsString('LOCATION:Marathon\; Attica\, Athens', $unfolded);
        $this->assertStringContainsString('URL:'.route('en.events.show', 'athens-marathon'), $unfolded);
        $this->assertStringContainsString('UID:event-', $ics);

        foreach (explode("\r\n", $ics) as $line) {
            $this->assertLessThanOrEqual(75, strlen($line));
        }
    }

    public function test_summer_time_is_taken_into_account(): void
    {
        $ics = $this->ics($this->event(['starts_at' => '2027-06-06 19:00:00', 'time_limit_minutes' => null]));

        // Athens is UTC+3 in June; without a limit the entry lasts three hours.
        $this->assertStringContainsString("DTSTART:20270606T160000Z\r\n", $ics);
        $this->assertStringContainsString("DTEND:20270606T190000Z\r\n", $ics);
    }

    public function test_a_race_without_a_start_time_is_an_all_day_entry(): void
    {
        $ics = $this->ics($this->event(['starts_at' => '2026-11-08 00:00:00', 'start_time_known' => false]));

        $this->assertStringContainsString("DTSTART;VALUE=DATE:20261108\r\n", $ics);
        $this->assertStringContainsString("DTEND;VALUE=DATE:20261109\r\n", $ics);
    }

    public function test_the_other_races_are_listed_and_named_in_the_summary(): void
    {
        $event = $this->event(event: ['name' => ['en' => 'Athens Classic', 'el' => 'Athens Classic']]);
        Race::factory()->for($event)->create(['name' => ['en' => '10 km', 'el' => '10 χλμ.'], 'is_main' => false, 'starts_at' => '2026-11-08 08:00:00', 'start_time_known' => true]);

        $unfolded = str_replace("\r\n ", '', $this->ics($event));

        $this->assertStringContainsString('SUMMARY:Athens Classic: Marathon', $unfolded);
        $this->assertStringContainsString('10 km: 8 Nov\, 08:00', $unfolded);
    }

    public function test_a_draft_has_no_calendar_file(): void
    {
        $event = $this->event(event: ['published_at' => null]);

        $this->get(route('en.events.calendar', $event))->assertNotFound();
    }

    public function test_the_event_page_sends_the_countdown_and_the_calendar_links(): void
    {
        $event = $this->event();

        $this->get(route('en.events.show', $event))->assertInertia(fn (Assert $page) => $page
            ->where('countdown.starts_on', '2026-11-08')
            ->where('countdown.time', '08:30')
            ->where('countdown.today', '2026-11-01')
            ->where('countdown.ics_url', route('en.events.calendar', $event))
            ->where('countdown.google_url', fn (string $url) => str_starts_with($url, 'https://calendar.google.com/calendar/render?action=TEMPLATE')
                && str_contains($url, 'dates=20261108T063000Z%2F20261108T143000Z')));
    }

    public function test_today_is_the_date_in_athens(): void
    {
        // 23:30 UTC on 7 November is already 8 November in Athens: race day.
        $this->travelTo('2026-11-07 23:30:00');

        $this->get(route('en.events.show', $this->event()))->assertInertia(fn (Assert $page) => $page
            ->where('countdown.today', '2026-11-08')
            ->where('countdown.starts_on', '2026-11-08'));
    }

    public function test_the_countdown_is_gone_after_race_day(): void
    {
        $this->travelTo('2026-11-09 08:00:00');

        $this->get(route('en.events.show', $this->event()))->assertInertia(fn (Assert $page) => $page
            ->where('countdown', null));
    }

    public function test_no_countdown_for_a_cancelled_postponed_or_unconfirmed_event(): void
    {
        foreach ([['status' => EventStatus::Cancelled], ['status' => EventStatus::Postponed], ['date_confirmed' => false]] as $i => $attributes) {
            $event = $this->event(['slug' => "race-{$i}"], ['slug' => "event-{$i}", ...$attributes]);

            $this->get(route('en.events.show', $event))->assertInertia(fn (Assert $page) => $page
                ->where('countdown', null));
        }
    }
}
