<?php

namespace Database\Seeders;

use App\Constants\Events;
use App\Models\Event;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Puts the events that used to be served from App\Constants\Events into the table.
 *
 * The ids of the constants are kept, so the favorites runners already have keep
 * pointing at the same races.
 */
class EventSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the events table.
     */
    public function run(): void
    {
        foreach (Events::LIST as $event) {
            Event::query()->updateOrCreate(['id' => $event['id']], [
                'slug' => Str::slug($event['name']),

                // The constants only ever carried English, so both locales start out with it.
                'name' => ['el' => $event['name'], 'en' => $event['name']],
                'description' => ['el' => $event['description'], 'en' => $event['description']],

                'category' => $event['category'],
                'distance_meters' => $this->metres($event['distance']),
                'starts_at' => Carbon::parse($event['date'])->startOfDay(),
                'ends_at' => null,
                'city' => Str::before($event['location'], ','),
                'country' => 'GR',
                'participants' => $event['participants'],
                'image_url' => $event['image'],
                'registration_url' => null,
                'published_at' => now(),
            ]);
        }

        // Inserting explicit ids leaves the sequence behind, so the next event
        // created without one would collide with a seeded race.
        DB::statement("select setval(pg_get_serial_sequence('events', 'id'), (select max(id) from events))");
    }

    /**
     * The number of metres a distance such as '42.2 km' stands for.
     */
    private function metres(string $distance): int
    {
        return (int) round(((float) $distance) * 1000);
    }
}
