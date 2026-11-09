<?php

namespace Tests\Feature;

use App\Enums\SectionType;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\EventSection;
use App\Models\Race;
use App\Models\RaceCheckpoint;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PracticalInfoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The event as the event page renders it.
     *
     * @return array<string, mixed>
     */
    private function resource(Event $event): array
    {
        $this->app->setLocale('en');

        return (new EventResource($event->fresh()->load(['races.prices', 'races.checkpoints', 'sections'])))->toArray(Request::create('/'));
    }

    public function test_sections_keep_their_order_and_fall_back_to_the_type_for_the_title(): void
    {
        $event = Event::factory()->create();
        EventSection::factory()->for($event)->create(['type' => SectionType::Medical, 'sort_order' => 1, 'body' => ['en' => 'Ambulances on the course.']]);
        EventSection::factory()->for($event)->create(['type' => SectionType::Transport, 'sort_order' => 0, 'body' => ['en' => 'Take the **metro**.']]);
        EventSection::factory()->for($event)->create(['type' => SectionType::Other, 'sort_order' => 2, 'title' => ['en' => 'Pacers', 'el' => 'Pacers'], 'body' => ['en' => 'Every 15 minutes.']]);

        $sections = $this->resource($event)['sections'];

        $this->assertSame(['transport', 'medical', 'other'], array_column($sections, 'type'));
        $this->assertSame([null, null, 'Pacers'], array_column($sections, 'title'));
        $this->assertStringContainsString('<strong>metro</strong>', $sections[0]['body_html']);
    }

    public function test_a_section_without_text_is_hidden_and_text_falls_back_to_english(): void
    {
        $event = Event::factory()->create();
        EventSection::factory()->for($event)->create(['type' => SectionType::BagDrop, 'body' => ['en' => '   ', 'el' => '']]);
        EventSection::factory()->for($event)->create(['type' => SectionType::Expo, 'body' => ['en' => 'At the hall.']]);

        $this->app->setLocale('el');
        $sections = (new EventResource($event->fresh()->load(['races', 'sections'])))->toArray(Request::create('/'))['sections'];

        $this->assertCount(1, $sections);
        $this->assertSame('expo', $sections[0]['type']);
        $this->assertStringContainsString('At the hall.', $sections[0]['body_html']);
    }

    public function test_raw_html_and_unsafe_links_are_removed_from_the_text(): void
    {
        $event = Event::factory()->create();
        EventSection::factory()->for($event)->create(['body' => ['en' => "Hello <script>alert(1)</script> <img src=x onerror=alert(2)>\n\n[click](javascript:alert(3))"]]);

        $html = $this->resource($event)['sections'][0]['body_html'];

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('Hello', $html);
    }

    public function test_checkpoints_are_listed_by_distance_and_the_full_distance_is_the_finish(): void
    {
        $race = Race::factory()->create(['distance_meters' => 42195, 'starts_at' => '2026-11-08 08:30:00', 'start_time_known' => true, 'time_limit_minutes' => 480]);
        RaceCheckpoint::factory()->for($race)->create(['distance_meters' => 42195, 'closes_at' => '2026-11-08 18:15:00']);
        RaceCheckpoint::factory()->for($race)->create(['distance_meters' => 21097, 'closes_at' => '2026-11-08 14:14:00']);

        $cutoffs = $this->resource($race->event)['races'][0]['cutoffs'];

        $this->assertSame([
            ['distance_meters' => 21097, 'closes_at' => '2026-11-08 14:14', 'is_finish' => false],
            // The entered finish wins over the one the time limit gives.
            ['distance_meters' => 42195, 'closes_at' => '2026-11-08 18:15', 'is_finish' => true],
        ], $cutoffs);
    }

    public function test_the_finish_is_added_from_the_start_time_and_the_time_limit(): void
    {
        $race = Race::factory()->create(['distance_meters' => 246000, 'starts_at' => '2026-09-25 07:00:00', 'start_time_known' => true, 'time_limit_minutes' => 2160]);
        RaceCheckpoint::factory()->for($race)->create(['distance_meters' => 81000, 'closes_at' => '2026-09-25 16:30:00']);

        $cutoffs = $this->resource($race->event)['races'][0]['cutoffs'];

        $this->assertCount(2, $cutoffs);
        // A 36-hour race finishes the next day.
        $this->assertSame(['distance_meters' => 246000, 'closes_at' => '2026-09-26 19:00', 'is_finish' => true], $cutoffs[1]);
    }

    public function test_no_finish_is_made_up_without_a_start_time_or_without_checkpoints(): void
    {
        $unknownStart = Race::factory()->create(['distance_meters' => 21097, 'starts_at' => '2026-11-08 00:00:00', 'start_time_known' => false, 'time_limit_minutes' => 180]);
        RaceCheckpoint::factory()->for($unknownStart)->create(['distance_meters' => 10000, 'closes_at' => '2026-11-08 10:30:00']);
        $noCheckpoints = Race::factory()->create(['time_limit_minutes' => 180, 'start_time_known' => true]);

        $this->assertCount(1, $this->resource($unknownStart->event)['races'][0]['cutoffs']);
        $this->assertSame([], $this->resource($noCheckpoints->event)['races'][0]['cutoffs']);
    }

    public function test_the_event_page_sends_sections_and_cutoffs_and_the_lists_do_not(): void
    {
        $race = Race::factory()->create(['is_main' => true]);
        $event = $race->event;
        $event->update(['published_at' => now()]);
        EventSection::factory()->for($event)->create(['body' => ['en' => 'By bus.']]);
        RaceCheckpoint::factory()->for($race)->create(['distance_meters' => 5000]);

        $this->get(route('events.show', $event->slug))->assertInertia(fn (Assert $page) => $page
            ->has('event.sections', 1)
            ->where('event.sections.0.type', 'transport')
            ->has('event.races.0.cutoffs', 1));

        $this->assertSame([], (new EventResource(Event::query()->withDetails()->find($event->id)))->toArray(Request::create('/'))['sections']);
    }

    public function test_the_admin_saves_sections_and_checkpoints_and_reads_them_back(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create()->assignRole('admin');
        $event = Event::factory()->create();

        $this->actingAs($admin)->put(route('admin.events.update', $event), $this->payload($event, [
            ['type' => 'transport', 'title' => ['el' => '', 'en' => ''], 'body' => ['el' => 'Με λεωφορείο.', 'en' => 'By bus.']],
            ['type' => 'other', 'title' => ['el' => '', 'en' => 'Pacers'], 'body' => ['el' => '', 'en' => 'Every 15 minutes.']],
        ], [
            ['distance_meters' => 5000, 'closes_at' => '2026-11-08T09:40'],
        ]))->assertSessionHasNoErrors();

        $sections = $event->fresh()->sections;

        $this->assertSame(['transport', 'other'], $sections->map(fn (EventSection $section) => $section->type->value)->all());
        $this->assertNull($sections[0]->title);
        $this->assertSame(['en' => 'Every 15 minutes.'], $sections[1]->body);
        $this->assertSame('2026-11-08 09:40:00', $event->races()->first()->checkpoints()->first()->closes_at->format('Y-m-d H:i:s'));

        $this->actingAs($admin)->get(route('admin.events.edit', $event))->assertInertia(fn (Assert $page) => $page
            ->has('record.sections', 2)
            ->where('record.sections.1.title', ['en' => 'Pacers'])
            ->where('record.races.0.checkpoints.0.closes_at', '2026-11-08T09:40')
            ->has('raceOptions.sectionTypes', 6));

        $this->actingAs($admin)->put(route('admin.events.update', $event), $this->payload($event, [], []))->assertSessionHasNoErrors();

        $this->assertSame(0, $event->sections()->count());
        $this->assertSame(0, RaceCheckpoint::query()->count());
    }

    public function test_the_admin_rejects_an_untitled_other_section_missing_text_and_incomplete_checkpoints(): void
    {
        $this->seed(RoleSeeder::class);
        $event = Event::factory()->create();

        $this->actingAs(User::factory()->create()->assignRole('admin'))->put(route('admin.events.update', $event), $this->payload($event, [
            ['type' => 'other', 'title' => ['el' => '', 'en' => ''], 'body' => ['el' => '', 'en' => 'Text']],
            ['type' => 'expo', 'title' => ['el' => '', 'en' => ''], 'body' => ['el' => 'Κείμενο', 'en' => '']],
            ['type' => 'parking', 'body' => ['en' => 'Text']],
        ], [
            ['distance_meters' => '', 'closes_at' => ''],
        ]))->assertSessionHasErrors([
            'sections.0.title.en', 'sections.1.body.en', 'sections.2.type',
            'races.0.checkpoints.0.distance_meters', 'races.0.checkpoints.0.closes_at',
        ]);
    }

    /**
     * The admin form of the event with the given sections and checkpoints of its race.
     *
     * @param  array<int, array<string, mixed>>  $sections
     * @param  array<int, array<string, mixed>>  $checkpoints
     * @return array<string, mixed>
     */
    private function payload(Event $event, array $sections, array $checkpoints): array
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
                'checkpoints' => $checkpoints,
            ]],
            'links' => [],
            'dates' => [],
            'sections' => $sections,
        ];
    }
}
