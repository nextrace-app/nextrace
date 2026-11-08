<?php

namespace Tests\Feature;

use App\Enums\KeyDateType;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\EventDate;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class KeyDatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-01 12:00:00');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function keyDates(Event $event): array
    {
        $this->app->setLocale('en');

        return (new EventResource($event->fresh()->load('dates')))->toArray(Request::create('/'))['key_dates'];
    }

    public function test_an_event_without_dates_has_an_empty_timeline(): void
    {
        $event = Event::factory()->create(['registration_opens_at' => null, 'registration_closes_at' => null]);

        $this->assertSame([], $this->keyDates($event));
    }

    public function test_dates_are_sorted_by_day_whatever_order_they_were_entered_in(): void
    {
        $event = Event::factory()->create(['registration_opens_at' => null, 'registration_closes_at' => null]);
        EventDate::factory()->for($event)->create(['type' => KeyDateType::Expo, 'starts_on' => '2026-11-04', 'sort_order' => 0]);
        EventDate::factory()->for($event)->create(['type' => KeyDateType::ChangesDeadline, 'starts_on' => '2026-09-25', 'sort_order' => 1]);
        EventDate::factory()->for($event)->create(['type' => KeyDateType::Other, 'starts_on' => '2026-11-04', 'label' => ['en' => 'Press day', 'el' => 'Ημέρα Τύπου'], 'sort_order' => 2]);

        $dates = $this->keyDates($event);

        $this->assertSame(['changes_deadline', 'expo', 'other'], array_column($dates, 'type'));
        $this->assertSame([null, null, 'Press day'], array_column($dates, 'label'));
    }

    public function test_a_span_keeps_its_end_and_a_one_day_span_is_a_single_day(): void
    {
        $event = Event::factory()->create(['registration_opens_at' => null, 'registration_closes_at' => null]);
        EventDate::factory()->for($event)->create(['type' => KeyDateType::BibPickup, 'starts_on' => '2026-11-04', 'ends_on' => '2026-11-07']);
        EventDate::factory()->for($event)->create(['type' => KeyDateType::Expo, 'starts_on' => '2026-11-10', 'ends_on' => '2026-11-10']);

        $dates = $this->keyDates($event);

        $this->assertSame(['from' => '2026-11-04', 'to' => '2026-11-07'], ['from' => $dates[0]['from'], 'to' => $dates[0]['to']]);
        $this->assertNull($dates[1]['to']);
    }

    public function test_past_dates_are_marked_and_only_the_first_one_to_come_is_next(): void
    {
        $event = Event::factory()->create(['registration_opens_at' => null, 'registration_closes_at' => null]);
        EventDate::factory()->for($event)->create(['type' => KeyDateType::ChangesDeadline, 'starts_on' => '2026-09-25']);
        // A span that started before today and is still running is not over.
        EventDate::factory()->for($event)->create(['type' => KeyDateType::Expo, 'starts_on' => '2026-09-28', 'ends_on' => '2026-10-02']);
        EventDate::factory()->for($event)->create(['type' => KeyDateType::BibPickup, 'starts_on' => '2026-11-04']);
        EventDate::factory()->for($event)->create(['type' => KeyDateType::Other, 'starts_on' => '2026-11-08', 'label' => ['en' => 'Race day']]);

        $this->assertSame(['past', 'next', 'upcoming', 'upcoming'], array_column($this->keyDates($event), 'state'));
    }

    public function test_a_date_on_today_is_next(): void
    {
        $event = Event::factory()->create(['registration_opens_at' => null, 'registration_closes_at' => null]);
        EventDate::factory()->for($event)->create(['type' => KeyDateType::Expo, 'starts_on' => '2026-10-01']);

        $this->assertSame('next', $this->keyDates($event)[0]['state']);
    }

    public function test_when_every_date_is_over_none_is_next(): void
    {
        $event = Event::factory()->create(['registration_opens_at' => null, 'registration_closes_at' => null]);
        EventDate::factory()->for($event)->create(['type' => KeyDateType::Expo, 'starts_on' => '2026-09-01']);
        EventDate::factory()->for($event)->create(['type' => KeyDateType::BibPickup, 'starts_on' => '2026-09-02']);

        $this->assertSame(['past', 'past'], array_column($this->keyDates($event), 'state'));
    }

    public function test_the_registration_dates_of_the_event_join_the_timeline(): void
    {
        $event = Event::factory()->create([
            'registration_opens_at' => '2026-06-01 10:00:00',
            'registration_closes_at' => '2026-10-20 23:59:00',
        ]);
        EventDate::factory()->for($event)->create(['type' => KeyDateType::Expo, 'starts_on' => '2026-10-20']);

        $dates = $this->keyDates($event);

        $this->assertSame(['registration_opens', 'registration_closes', 'expo'], array_column($dates, 'type'));
        $this->assertSame(['2026-06-01', '2026-10-20', '2026-10-20'], array_column($dates, 'from'));
        $this->assertSame(['past', 'next', 'upcoming'], array_column($dates, 'state'));
    }

    public function test_an_entered_date_replaces_the_registration_date_of_the_same_day(): void
    {
        $event = Event::factory()->create(['registration_opens_at' => null, 'registration_closes_at' => '2026-10-20 23:59:00']);
        EventDate::factory()->for($event)->create(['type' => KeyDateType::RegistrationCloses, 'starts_on' => '2026-10-20', 'label' => ['en' => 'Online entries close']]);

        $dates = $this->keyDates($event);

        $this->assertCount(1, $dates);
        $this->assertSame('Online entries close', $dates[0]['label']);
    }

    public function test_the_event_page_sends_the_timeline(): void
    {
        $event = Event::factory()->create(['registration_opens_at' => null, 'registration_closes_at' => null]);
        EventDate::factory()->for($event)->create(['type' => KeyDateType::Expo, 'starts_on' => '2026-11-04', 'ends_on' => '2026-11-07']);

        $this->get(route('events.show', $event->slug))->assertInertia(fn (Assert $page) => $page
            ->has('event.key_dates', 1)
            ->where('event.key_dates.0.type', 'expo')
            ->where('event.key_dates.0.to', '2026-11-07')
            ->where('event.key_dates.0.state', 'next'));
    }

    public function test_the_admin_saves_the_dates_of_an_event_and_reads_them_back(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create()->assignRole('admin');
        $event = Event::factory()->create();

        $this->actingAs($admin)->put(route('admin.events.update', $event), $this->payload($event, [
            ['type' => 'bib_pickup', 'starts_on' => '2026-11-04', 'ends_on' => '2026-11-07', 'label' => ['el' => '', 'en' => 'Bib pickup at the hall']],
            ['type' => 'changes_deadline', 'starts_on' => '2026-09-25', 'ends_on' => '', 'label' => ['el' => '', 'en' => '']],
        ]))->assertSessionHasNoErrors();

        $dates = $event->fresh()->dates;

        $this->assertSame(['changes_deadline', 'bib_pickup'], $dates->map(fn (EventDate $date) => $date->type->value)->all());
        $this->assertNull($dates[0]->label);
        $this->assertNull($dates[0]->ends_on);
        $this->assertSame(['en' => 'Bib pickup at the hall'], $dates[1]->label);

        $this->actingAs($admin)->get(route('admin.events.edit', $event))->assertInertia(fn (Assert $page) => $page
            ->has('record.dates', 2)
            // The form gets them back in date order.
            ->where('record.dates.0.type', 'changes_deadline')
            ->where('record.dates.1.ends_on', '2026-11-07')
            ->has('raceOptions.dateTypes', 6));

        $this->actingAs($admin)->put(route('admin.events.update', $event), $this->payload($event, []))->assertSessionHasNoErrors();

        $this->assertSame(0, $event->dates()->count());
    }

    public function test_the_admin_rejects_an_other_date_without_a_label_and_an_end_before_the_start(): void
    {
        $this->seed(RoleSeeder::class);
        $event = Event::factory()->create();

        $this->actingAs(User::factory()->create()->assignRole('admin'))->put(route('admin.events.update', $event), $this->payload($event, [
            ['type' => 'other', 'starts_on' => '2026-11-04', 'label' => ['el' => '', 'en' => '']],
            ['type' => 'expo', 'starts_on' => '2026-11-04', 'ends_on' => '2026-11-01'],
            ['type' => 'party', 'starts_on' => ''],
        ]))->assertSessionHasErrors(['dates.0.label.en', 'dates.1.ends_on', 'dates.2.type', 'dates.2.starts_on']);
    }

    /**
     * The admin form of the event with the given key dates.
     *
     * @param  array<int, array<string, mixed>>  $dates
     * @return array<string, mixed>
     */
    private function payload(Event $event, array $dates): array
    {
        return [
            'slug' => $event->slug,
            'name' => ['el' => 'Αγώνας', 'en' => 'Race'],
            'description' => ['el' => 'Περιγραφή', 'en' => 'About'],
            'starts_on' => '2026-11-08',
            'city' => 'Athens',
            'status' => 'scheduled',
            'registration_status' => 'open',
            'date_confirmed' => true,
            'medical_certificate_required' => false,
            'races' => [[
                'slug' => 'ten-k',
                'name' => ['el' => '10 χλμ', 'en' => '10K'],
                'is_main' => true,
                'discipline' => 'run',
                'terrain' => 'road',
                'distance_meters' => 10000,
                'starts_at' => '2026-11-08T09:00',
                'status' => 'scheduled',
                'prices' => [],
            ]],
            'links' => [],
            'dates' => $dates,
        ];
    }
}
