<?php

namespace Tests\Feature;

use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\Race;
use App\Models\RaceCourse;
use App\Models\User;
use App\Support\GpxCourse;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RaceCourseTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(string $name): string
    {
        return (string) file_get_contents(base_path("tests/fixtures/gpx/{$name}"));
    }

    public function test_it_reads_length_climb_descent_and_highest_point_across_segments(): void
    {
        $course = GpxCourse::parse($this->fixture('hill.gpx'));

        // Five steps of 0.009° of latitude, about 1 km each.
        $this->assertEqualsWithDelta(5004, $course['distance_meters'], 5);
        // 100 → 102 is noise; +48 and +50 count, then −80.
        $this->assertSame(100, $course['elevation_gain_m']);
        $this->assertSame(80, $course['elevation_loss_m']);
        $this->assertSame(200, $course['highest_point_m']);
        $this->assertSame([37.97, 23.74], $course['track'][0]);
        $this->assertSame([38.015, 23.74], end($course['track']));
        $this->assertCount(GpxCourse::PROFILE_POINTS, $course['profile']);
        $this->assertSame([0.0, 100], $course['profile'][0]);
        $this->assertSame([5.0, 120], end($course['profile']));
    }

    public function test_a_route_without_elevation_has_a_length_and_a_line_but_no_profile(): void
    {
        $course = GpxCourse::parse($this->fixture('route-no-elevation.gpx'));

        $this->assertEqualsWithDelta(1000, $course['distance_meters'], 5);
        $this->assertNull($course['elevation_gain_m']);
        $this->assertNull($course['highest_point_m']);
        $this->assertNull($course['profile']);
        $this->assertCount(2, $course['track']);
    }

    public function test_a_long_track_is_thinned_out_for_the_map(): void
    {
        $points = '';

        for ($i = 0; $i < 5000; $i++) {
            $points .= sprintf('<trkpt lat="%.6f" lon="23.74"><ele>%d</ele></trkpt>', 37.9 + $i * 0.0001, 100 + ($i % 2));
        }

        $course = GpxCourse::parse("<gpx xmlns=\"http://www.topografix.com/GPX/1/1\"><trk><trkseg>{$points}</trkseg></trk></gpx>");

        $this->assertLessThanOrEqual(GpxCourse::TRACK_POINTS + 1, count($course['track']));
        $this->assertGreaterThan(GpxCourse::TRACK_POINTS * 0.9, count($course['track']));
        // A metre up and down at every point is noise, not 5 km of climbing.
        $this->assertSame(0, $course['elevation_gain_m']);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function unreadableFiles(): array
    {
        return [
            'not xml' => ['this is not a gpx file'],
            'other xml' => ['<?xml version="1.0"?><kml><Placemark/></kml>'],
            'no points' => ['<gpx><trk><trkseg></trkseg></trk></gpx>'],
            'one point' => ['<gpx><trk><trkseg><trkpt lat="37.9" lon="23.7"/></trkseg></trk></gpx>'],
            'bad coordinates' => ['<gpx><trk><trkseg><trkpt lat="200" lon="23.7"/><trkpt lat="x" lon="23.7"/></trkseg></trk></gpx>'],
        ];
    }

    #[DataProvider('unreadableFiles')]
    public function test_an_unreadable_file_is_rejected(string $xml): void
    {
        $this->expectException(InvalidArgumentException::class);

        GpxCourse::parse($xml);
    }

    public function test_external_entities_are_not_read(): void
    {
        $xml = '<?xml version="1.0"?><!DOCTYPE gpx [<!ENTITY secret SYSTEM "file:///etc/hosts">]>'
            .'<gpx><trk><name>&secret;</name><trkseg><trkpt lat="37.9" lon="23.7"/><trkpt lat="37.91" lon="23.7"/></trkseg></trk></gpx>';

        $course = GpxCourse::parse($xml);

        $this->assertStringNotContainsString('localhost', json_encode($course));
    }

    public function test_the_admin_uploads_a_gpx_and_the_event_page_draws_it(): void
    {
        Storage::fake('public');
        [$admin, $event] = $this->adminAndEvent();

        $this->actingAs($admin)->post(route('admin.events.update', $event), [
            ...$this->payload($event, ['gpx' => UploadedFile::fake()->createWithContent('athens-10k.gpx', $this->fixture('hill.gpx'))]),
            '_method' => 'PUT',
        ])->assertSessionHasNoErrors();

        $course = RaceCourse::query()->sole();

        $this->assertSame('athens-10k.gpx', $course->gpx_name);
        $this->assertSame(100, $course->elevation_gain_m);
        Storage::disk('public')->assertExists($course->gpx_path);

        $this->get(route('events.show', $event->slug))->assertInertia(fn (Assert $page) => $page
            ->where('event.races.0.gpx_url', Storage::disk('public')->url($course->gpx_path))
            ->where('event.races.0.course.elevation_gain_m', 100)
            ->where('event.races.0.course.gpx_name', 'athens-10k.gpx')
            ->has('event.races.0.course.track', 6)
            ->has('event.races.0.course.profile', GpxCourse::PROFILE_POINTS));

        $this->actingAs($admin)->get(route('admin.events.edit', $event))->assertInertia(fn (Assert $page) => $page
            ->where('record.races.0.gpx_name', 'athens-10k.gpx'));
    }

    public function test_the_form_sent_as_multipart_keeps_its_booleans(): void
    {
        Storage::fake('public');
        [$admin, $event] = $this->adminAndEvent();
        $payload = $this->payload($event, ['gpx' => UploadedFile::fake()->createWithContent('c.gpx', $this->fixture('hill.gpx'))]);

        // FormData turns booleans into "1" and "0".
        $payload['date_confirmed'] = '1';
        $payload['medical_certificate_required'] = '0';
        $payload['races'][0]['is_main'] = '1';
        $payload['races'][0]['remove_gpx'] = '0';

        $this->actingAs($admin)->post(route('admin.events.update', $event), [...$payload, '_method' => 'PUT'])
            ->assertSessionHasNoErrors();

        $this->assertTrue($event->races()->sole()->is_main);
        $this->assertSame(1, RaceCourse::query()->count());
    }

    public function test_a_new_file_replaces_the_old_one_and_remove_deletes_it(): void
    {
        Storage::fake('public');
        [$admin, $event] = $this->adminAndEvent();
        $upload = fn (string $fixture) => $this->actingAs($admin)->post(route('admin.events.update', $event), [
            ...$this->payload($event, ['gpx' => UploadedFile::fake()->createWithContent('c.gpx', $this->fixture($fixture))]),
            '_method' => 'PUT',
        ])->assertSessionHasNoErrors();

        $upload('hill.gpx');
        $first = RaceCourse::query()->sole()->gpx_path;
        $upload('route-no-elevation.gpx');
        $second = RaceCourse::query()->sole();

        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second->gpx_path);
        $this->assertNull($second->profile);

        // Saving without a file keeps it.
        $this->actingAs($admin)->put(route('admin.events.update', $event), $this->payload($event))->assertSessionHasNoErrors();
        $this->assertSame(1, RaceCourse::query()->count());

        $this->actingAs($admin)->put(route('admin.events.update', $event), $this->payload($event, ['remove_gpx' => true]))->assertSessionHasNoErrors();

        $this->assertSame(0, RaceCourse::query()->count());
        Storage::disk('public')->assertMissing($second->gpx_path);
    }

    public function test_removing_a_race_deletes_its_file(): void
    {
        Storage::fake('public');
        [$admin, $event] = $this->adminAndEvent();
        $this->actingAs($admin)->post(route('admin.events.update', $event), [
            ...$this->payload($event, ['gpx' => UploadedFile::fake()->createWithContent('c.gpx', $this->fixture('hill.gpx'))]),
            '_method' => 'PUT',
        ])->assertSessionHasNoErrors();
        $path = RaceCourse::query()->sole()->gpx_path;

        $payload = $this->payload($event);
        $payload['races'][0]['slug'] = 'half';

        $this->actingAs($admin)->put(route('admin.events.update', $event), $payload)->assertSessionHasNoErrors();

        $this->assertSame(0, RaceCourse::query()->count());
        Storage::disk('public')->assertMissing($path);
    }

    public function test_a_broken_or_wrong_file_is_a_form_error(): void
    {
        Storage::fake('public');
        [$admin, $event] = $this->adminAndEvent();

        $this->actingAs($admin)->post(route('admin.events.update', $event), [
            ...$this->payload($event, ['gpx' => UploadedFile::fake()->createWithContent('broken.gpx', '<gpx><trk></trk></gpx>')]),
            '_method' => 'PUT',
        ])->assertSessionHasErrors(['races.0.gpx' => trans('ui.admin.events.races.gpx_invalid')]);

        $this->actingAs($admin)->post(route('admin.events.update', $event), [
            ...$this->payload($event, ['gpx' => UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg')]),
            '_method' => 'PUT',
        ])->assertSessionHasErrors(['races.0.gpx']);

        $this->actingAs($admin)->post(route('admin.events.update', $event), [
            ...$this->payload($event, ['gpx' => UploadedFile::fake()->create('huge.gpx', 11 * 1024)]),
            '_method' => 'PUT',
        ])->assertSessionHasErrors(['races.0.gpx']);

        $this->assertSame(0, RaceCourse::query()->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_the_lists_know_the_file_but_not_the_course(): void
    {
        Storage::fake('public');
        $race = Race::factory()->create(['is_main' => true]);
        $course = RaceCourse::factory()->for($race)->create();

        $listed = (new EventResource(Event::query()->withDetails()->find($race->event_id)))->toArray(Request::create('/'));

        $this->assertSame(Storage::disk('public')->url($course->gpx_path), $listed['races'][0]['gpx_url']);
        $this->assertNull($listed['races'][0]['course']);
    }

    public function test_an_event_without_a_course_sends_none(): void
    {
        $race = Race::factory()->create(['is_main' => true]);
        $race->event->update(['published_at' => now()]);

        $this->get(route('events.show', $race->event->slug))->assertInertia(fn (Assert $page) => $page
            ->where('event.races.0.course', null)
            ->where('event.races.0.gpx_url', null));
    }

    /**
     * @return array{0: User, 1: Event}
     */
    private function adminAndEvent(): array
    {
        $this->seed(RoleSeeder::class);

        return [
            User::factory()->create()->assignRole('admin'),
            Event::factory()->create(['published_at' => now()]),
        ];
    }

    /**
     * The admin form of the event with one race.
     *
     * @param  array<string, mixed>  $race
     * @return array<string, mixed>
     */
    private function payload(Event $event, array $race = []): array
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
                ...$race,
            ]],
        ];
    }
}
