<?php

use App\Enums\DistanceClass;
use App\Enums\RaceDiscipline;
use App\Enums\RaceTerrain;
use Database\Seeders\RegionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * The region of the cities the catalogue held so far, by lower-cased city.
     *
     * @var array<string, string>
     */
    private const REGIONS_BY_CITY = [
        'athens' => 'attiki',
        'athens to sparta' => 'attiki',
        'thessaloniki' => 'central-macedonia',
        'mount olympus' => 'central-macedonia',
        'meteora' => 'thessaly',
        'santorini' => 'south-aegean',
        'rhodes' => 'south-aegean',
        'nafplio' => 'peloponnese',
        'ioannina' => 'epirus',
        'zagori' => 'epirus',
        'patras' => 'western-greece',
        'chania' => 'chania',
        'kissamos' => 'chania',
        'plokamiana' => 'chania',
        'heraklion' => 'heraklion',
        'fodele' => 'heraklion',
        'arkalochori' => 'heraklion',
        'voritsi' => 'heraklion',
        'rethymno' => 'rethymno',
        'ierapetra' => 'lasithi',
        'malles' => 'lasithi',
    ];

    /**
     * Give every existing event one main race built from the columns that are
     * about to be dropped, and fill the new date and region columns.
     */
    public function up(): void
    {
        (new RegionSeeder)->run();

        $regions = DB::table('regions')->pluck('id', 'slug');
        $now = now();

        foreach (DB::table('events')->orderBy('id')->get() as $event) {
            $startsAt = Carbon::parse($event->starts_at);

            DB::table('events')->where('id', $event->id)->update([
                'starts_on' => $startsAt->toDateString(),
                'ends_on' => $event->ends_at ? Carbon::parse($event->ends_at)->toDateString() : null,
                'region_id' => $regions[self::REGIONS_BY_CITY[Str::lower($event->city)] ?? ''] ?? null,
            ]);

            if (DB::table('races')->where('event_id', $event->id)->exists()) {
                continue;
            }

            $class = $this->distanceClass($event->category);
            $trail = Str::lower($event->category) === 'trail';

            DB::table('races')->insert([
                'event_id' => $event->id,
                'slug' => $class === DistanceClass::Other ? 'race' : Str::slug(str_replace('_', '-', $class->value)),
                'name' => json_encode(['en' => $event->category, 'el' => $event->category], JSON_UNESCAPED_UNICODE),
                'sort_order' => 0,
                'is_main' => true,
                'discipline' => RaceDiscipline::Run->value,
                'terrain' => ($trail ? RaceTerrain::Trail : RaceTerrain::Road)->value,
                'distance_class' => $class->value,
                'distance_meters' => $event->distance_meters,
                'starts_at' => $event->starts_at,
                'start_time_known' => $startsAt->format('H:i:s') !== '00:00:00',
                'registration_url' => $event->registration_url,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * The races are dropped with their table, and the columns that held their
     * data are restored from them by the migration that dropped those.
     */
    public function down(): void
    {
        //
    }

    private function distanceClass(string $category): DistanceClass
    {
        return match (Str::lower($category)) {
            '5k' => DistanceClass::FiveK,
            '10k' => DistanceClass::TenK,
            'half marathon' => DistanceClass::HalfMarathon,
            'marathon' => DistanceClass::Marathon,
            'ultra' => DistanceClass::Ultra,
            default => DistanceClass::Other,
        };
    }
};
