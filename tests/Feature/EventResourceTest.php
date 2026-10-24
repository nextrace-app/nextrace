<?php

namespace Tests\Feature;

use App\Enums\LinkType;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\EventLink;
use App\Models\EventSeries;
use App\Models\Organizer;
use App\Models\Race;
use App\Models\RacePrice;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class EventResourceTest extends TestCase
{
    use RefreshDatabase;

    private function resolve(Event $event): array
    {
        return (new EventResource($event->fresh()))->toArray(Request::create('/'));
    }

    public function test_it_keeps_every_key_the_current_pages_read(): void
    {
        $event = Event::factory()->withMainRace([
            'distance_class' => 'marathon',
            'distance_meters' => 42195,
            'starts_at' => '2027-04-11 07:00:00',
        ])->create(['city' => 'Chania', 'latitude' => '35.5', 'longitude' => '24.0', 'participants' => 900]);

        $data = $this->resolve($event);

        foreach (['id', 'slug', 'name', 'description', 'date', 'time', 'city', 'location', 'category', 'distance', 'distance_meters', 'participants', 'image', 'latitude', 'longitude', 'registration_url'] as $key) {
            $this->assertArrayHasKey($key, $data);
        }

        $this->assertSame('2027-04-11', $data['date']);
        $this->assertSame('07:00', $data['time']);
        $this->assertSame('Marathon', $data['category']);
        $this->assertSame('42.2 km', $data['distance']);
        $this->assertSame(42195, $data['distance_meters']);
        $this->assertSame('Chania, Greece', $data['location']);
        $this->assertSame(35.5, $data['latitude']);
    }

    public function test_the_time_is_null_when_the_start_time_is_not_known(): void
    {
        $event = Event::factory()->startingAt('2027-04-25 00:00:00')->create();

        $this->assertNull($this->resolve($event)['time']);
        $this->assertNull($this->resolve($event)['races'][0]['time']);
    }

    public function test_it_adds_races_prices_links_and_the_related_records(): void
    {
        $organizer = Organizer::factory()->create(['name' => ['en' => 'Club', 'el' => 'Σύλλογος'], 'email' => 'a@example.com']);
        $series = EventSeries::factory()->create(['slug' => 'crete-marathon', 'name' => ['en' => 'Crete Marathon', 'el' => 'Μαραθώνιος Κρήτης']]);
        $region = Region::factory()->create(['slug' => 'test-region', 'name' => ['en' => 'Chania', 'el' => 'Χανιά']]);

        $event = Event::factory()->create([
            'organizer_id' => $organizer->id,
            'series_id' => $series->id,
            'region_id' => $region->id,
            'edition_number' => 11,
            'venue' => ['en' => 'Stadium', 'el' => 'Στάδιο'],
            'registration_status' => 'open',
            'registration_closes_at' => '2027-03-12 00:00:00',
            'contact_email' => 'info@example.com',
            'date_confirmed' => false,
        ]);
        $race = Race::factory()->for($event)->create([
            'slug' => 'ten',
            'name' => ['en' => '10 km', 'el' => '10 χλμ.'],
            'distance_meters' => 10000,
            'terrain' => 'road',
            'certification' => 'aims',
            'time_limit_minutes' => 90,
            'start_location' => ['en' => 'Gate', 'el' => 'Πύλη'],
        ]);
        RacePrice::factory()->for($race)->create(['amount_cents' => 1250, 'label' => ['en' => 'Early', 'el' => 'Έγκαιρη'], 'valid_until' => '2027-01-01']);
        EventLink::factory()->for($event)->create(['type' => LinkType::Regulations, 'url' => 'https://example.com/rules', 'label' => ['en' => 'Rules', 'el' => 'Κανονισμός']]);

        $data = $this->resolve($event);

        $this->assertSame(['slug' => 'crete-marathon', 'name' => 'Crete Marathon'], $data['series']);
        $this->assertSame(['slug' => 'test-region', 'name' => 'Chania'], $data['region']);
        $this->assertSame('Club', $data['organizer']['name']);
        $this->assertSame('a@example.com', $data['organizer']['email']);
        $this->assertSame(11, $data['edition_number']);
        $this->assertSame('Stadium', $data['venue']);
        $this->assertSame('scheduled', $data['status']);
        $this->assertFalse($data['date_confirmed']);
        $this->assertSame('open', $data['registration']['status']);
        $this->assertSame('2027-03-12 00:00:00', $data['registration']['closes_at']);
        $this->assertSame(['email' => 'info@example.com', 'phone' => null], $data['contact']);
        $this->assertSame([['type' => 'regulations', 'url' => 'https://example.com/rules', 'label' => 'Rules']], $data['links']);

        $this->assertCount(2, $data['races']);
        $ten = $data['races'][1];
        $this->assertSame('10 km', $ten['name']);
        $this->assertSame('10 km', $ten['distance']);
        $this->assertSame('aims', $ten['certification']);
        $this->assertSame(90, $ten['time_limit_minutes']);
        $this->assertSame('Gate', $ten['start_location']);
        $this->assertFalse($ten['is_main']);
        $this->assertTrue($data['races'][0]['is_main']);
        $this->assertSame([[
            'label' => 'Early',
            'amount' => 12.5,
            'currency' => 'EUR',
            'valid_from' => null,
            'valid_until' => '2027-01-01',
        ]], $ten['prices']);
    }

    public function test_related_records_are_null_or_empty_when_absent(): void
    {
        $data = $this->resolve(Event::factory()->create());

        $this->assertNull($data['series']);
        $this->assertNull($data['organizer']);
        $this->assertNull($data['region']);
        $this->assertNull($data['venue']);
        $this->assertSame([], $data['links']);
        $this->assertSame('unknown', $data['registration']['status']);
    }

    public function test_text_is_in_the_locale_of_the_request(): void
    {
        $event = Event::factory()->create(['venue' => ['en' => 'Stadium', 'el' => 'Στάδιο']]);
        Race::factory()->for($event)->create(['name' => ['en' => '5 km', 'el' => '5 χλμ.']]);

        $this->app->setLocale('el');

        $data = $this->resolve($event);

        $this->assertSame('Στάδιο', $data['venue']);
        $this->assertSame('5 χλμ.', $data['races'][1]['name']);
    }

    public function test_an_event_without_races_still_renders(): void
    {
        $data = $this->resolve(Event::factory()->withoutRaces()->create(['starts_on' => '2027-05-01']));

        $this->assertSame('2027-05-01', $data['date']);
        $this->assertNull($data['distance_meters']);
        $this->assertSame([], $data['races']);
    }
}
