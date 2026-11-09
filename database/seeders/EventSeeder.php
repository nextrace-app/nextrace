<?php

namespace Database\Seeders;

use App\Enums\DistanceClass;
use App\Enums\LinkType;
use App\Enums\RaceTerrain;
use App\Models\Event;
use App\Models\EventSeries;
use App\Models\Region;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Puts the catalogue of races into the events table, each with one main race.
 *
 * The catalogue used to be served straight from a constant, so the ids it
 * carried are kept: the favorites runners already have keep pointing at the
 * same races.
 */
class EventSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * The region of the cities of the catalogue.
     *
     * @var array<string, string>
     */
    private const REGIONS_BY_CITY = [
        'Athens' => 'attiki',
        'Athens to Sparta' => 'attiki',
        'Thessaloniki' => 'central-macedonia',
        'Mount Olympus' => 'central-macedonia',
        'Meteora' => 'thessaly',
        'Santorini' => 'south-aegean',
        'Rhodes' => 'south-aegean',
        'Nafplio' => 'peloponnese',
        'Ioannina' => 'epirus',
        'Zagori' => 'epirus',
        'Patras' => 'western-greece',
        'Chania' => 'chania',
    ];

    /**
     * Seed the events table.
     */
    public function run(): void
    {
        $this->call(RegionSeeder::class);

        $regions = Region::query()->pluck('id', 'slug');

        foreach (self::catalogue() as $item) {
            $city = Str::before($item['location'], ',');
            $date = Carbon::parse($item['date'])->startOfDay();

            $event = Event::query()->updateOrCreate(['id' => $item['id']], [
                'slug' => Str::slug($item['name']),

                // The catalogue only ever carried English, so both locales start out with it.
                'name' => ['el' => $item['name'], 'en' => $item['name']],
                'description' => ['el' => $item['description'], 'en' => $item['description']],

                'starts_on' => $date->toDateString(),
                'ends_on' => null,
                'region_id' => $regions[self::REGIONS_BY_CITY[$city] ?? ''] ?? null,
                'city' => $city,
                'country' => 'GR',
                'participants' => $item['participants'],
                'image_url' => $item['image'],
                'registration_url' => null,
                'published_at' => now(),
            ]);

            $class = $this->distanceClass($item['category']);

            // The catalogue never had a time of day, so the start time stays unknown.
            $event->races()->updateOrCreate(['slug' => $class === DistanceClass::Other ? 'race' : Str::slug(str_replace('_', '-', $class->value))], [
                'name' => ['el' => $item['category'], 'en' => $item['category']],
                'is_main' => true,
                'terrain' => $item['category'] === 'Trail' ? RaceTerrain::Trail : RaceTerrain::Road,
                'distance_class' => $class,
                'distance_meters' => $this->metres($item['distance']),
                'starts_at' => $date,
                'start_time_known' => false,
            ]);
        }

        $this->seedAthensWeekend();
        $this->seedAthensEditions();
    }

    /**
     * Athens Marathon as a series: the 2026 race is its 43rd edition and the
     * 42nd, on the second Sunday of November 2025, is kept as a past edition.
     * The links are the results and photo pages of the official site.
     */
    private function seedAthensEditions(): void
    {
        $event = Event::query()->find(1);

        if ($event === null) {
            return;
        }

        $series = EventSeries::query()->updateOrCreate(['slug' => 'athens-marathon'], [
            'name' => ['el' => 'Μαραθώνιος Αθήνας', 'en' => 'Athens Marathon'],
            'website' => 'https://www.athensauthenticmarathon.gr',
        ]);

        $event->update(['series_id' => $series->id, 'edition_number' => 43]);
        $event->links()->whereIn('type', [LinkType::Results, LinkType::Photos])->delete();
        $event->links()->create(['type' => LinkType::Results, 'url' => 'https://www.athensauthenticmarathon.gr/en/results/live_results/agones-2-26', 'sort_order' => 10]);
        $event->links()->create(['type' => LinkType::Photos, 'url' => 'https://photos.athensauthenticmarathon.gr', 'sort_order' => 11]);

        $previous = Event::query()->updateOrCreate(['slug' => 'athens-marathon-2025'], [
            'series_id' => $series->id,
            'edition_number' => 42,
            'name' => $event->name,
            'description' => $event->description,
            'starts_on' => '2025-11-09',
            'ends_on' => null,
            'region_id' => $event->region_id,
            'city' => $event->city,
            'country' => 'GR',
            'registration_status' => 'closed',
            'published_at' => now(),
        ]);

        $previous->races()->updateOrCreate(['slug' => 'marathon'], [
            'name' => ['el' => 'Μαραθώνιος', 'en' => 'Marathon'],
            'is_main' => true,
            'terrain' => RaceTerrain::Road,
            'distance_class' => DistanceClass::Marathon,
            'distance_meters' => 42195,
            'starts_at' => '2025-11-09 00:00',
            'start_time_known' => false,
            'start_location' => ['el' => 'Μαραθώνας', 'en' => 'Marathon'],
            'finish_location' => ['el' => 'Παναθηναϊκό Στάδιο', 'en' => 'Panathenaic Stadium'],
        ]);

        $previous->links()->delete();
        $previous->links()->create(['type' => LinkType::Results, 'url' => 'https://www.athensauthenticmarathon.gr/en/results', 'sort_order' => 0]);
        $previous->links()->create(['type' => LinkType::Photos, 'url' => 'https://www.athensauthenticmarathon.gr/en/pages/previous-photos', 'sort_order' => 1]);
    }

    /**
     * Athens Marathon is a weekend of races, as on athensauthenticmarathon.gr:
     * the 5 km, the universities night run, the kids race and the Special
     * Olympics race on Saturday, the marathon and the 10 km on Sunday.
     */
    private function seedAthensWeekend(): void
    {
        $event = Event::query()->find(1);

        if ($event === null) {
            return;
        }

        $event->update([
            'starts_on' => '2026-11-07',
            'ends_on' => '2026-11-08',
            'registration_status' => 'closed',
            'registration_closes_at' => '2026-09-25 23:59:00',
        ]);

        $stadium = ['el' => 'Παναθηναϊκό Στάδιο', 'en' => 'Panathenaic Stadium'];
        $amalias = ['el' => 'Λεωφ. Βασ. Αμαλίας', 'en' => 'Vasilissis Amalias Avenue'];
        $sofias = ['el' => 'Λεωφ. Βασ. Σοφίας', 'en' => 'Vasilissis Sofias Avenue'];

        $races = [
            'marathon' => [['el' => 'Μαραθώνιος', 'en' => 'Marathon'], DistanceClass::Marathon, 42195, '2026-11-08 08:30', ['el' => 'Μαραθώνας', 'en' => 'Marathon'], true, 20],
            '10k' => [['el' => '10 χλμ.', 'en' => '10 km'], DistanceClass::TenK, 10000, '2026-11-08 08:00', $amalias, true, null],
            '5k' => [['el' => '5 χλμ.', 'en' => '5 km'], DistanceClass::FiveK, 5000, '2026-11-07 17:00', $amalias, true, null],
            'night-run-5k' => [['el' => 'Νυχτερινός αγώνας πανεπιστημίων 5 χλμ.', 'en' => 'Universities night run 5 km'], DistanceClass::FiveK, 5000, '2026-11-07 20:00', $amalias, true, null],
            'kids-race' => [['el' => 'Παιδικός αγώνας 1,2 χλμ.', 'en' => 'Kids race 1.2 km'], DistanceClass::Kids, 1200, '2026-11-07 16:00', $sofias, false, null],
            'special-olympics' => [['el' => 'Special Olympics 1,2 χλμ.', 'en' => 'Special Olympics 1.2 km'], DistanceClass::Other, 1200, '2026-11-07 16:45', $sofias, false, null],
        ];

        // Time limit (minutes) and aid stations per race, from the race pages.
        $details = [
            'marathon' => [480, 15],
            '10k' => [90, 2],
            '5k' => [60, 1],
            'night-run-5k' => [60, 1],
        ];

        // Early and standard entry in euros; the kids and Special Olympics races are free.
        $prices = [
            'marathon' => [75, 165],
            '10k' => [45, 90],
            '5k' => [40, 85],
            'night-run-5k' => [25, 45],
        ];

        $order = 0;

        foreach ($races as $slug => [$name, $class, $metres, $startsAt, $start, $competitive, $minAge]) {
            $race = $event->races()->updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'sort_order' => $order++,
                'is_main' => $slug === 'marathon',
                'terrain' => RaceTerrain::Road,
                'distance_class' => $class,
                'distance_meters' => $metres,
                'starts_at' => $startsAt,
                'start_time_known' => true,
                'start_location' => $start,
                'finish_location' => $stadium,
                'is_competitive' => $competitive,
                'min_age' => $minAge,
                'time_limit_minutes' => $details[$slug][0] ?? null,
                'aid_stations' => $details[$slug][1] ?? null,
                'medical_certificate_required' => false,
                'sold_out' => isset($prices[$slug]),
            ]);

            $race->prices()->delete();

            if (isset($prices[$slug])) {
                $race->prices()->create(['label' => ['el' => 'Πρώιμη συμμετοχή', 'en' => 'Early entry'], 'amount_cents' => $prices[$slug][0] * 100, 'valid_until' => '2026-05-31']);
                $race->prices()->create(['label' => ['el' => 'Κανονική συμμετοχή', 'en' => 'Standard entry'], 'amount_cents' => $prices[$slug][1] * 100, 'valid_from' => '2026-06-01', 'valid_until' => '2026-09-25']);
            } else {
                $race->prices()->create(['amount_cents' => 0]);
            }
        }

        // The "important dates" of the race site; the closing of the registration comes from the event.
        $event->dates()->delete();

        foreach ([
            ['registration_opens', '2026-09-14', null, ['el' => 'Ανοίγουν οι εγγραφές του παιδικού αγώνα', 'en' => 'Kids race registration opens']],
            ['changes_deadline', '2026-09-25', null, ['el' => 'Τελευταία ημέρα για αλλαγές και ακυρώσεις', 'en' => 'Last day for changes and cancellations']],
            ['bib_pickup', '2026-11-04', '2026-11-07', ['el' => 'Έκθεση και παραλαβή αριθμών, Κλειστό Φαλήρου', 'en' => 'Expo and bib pickup, Faliro Indoor Hall']],
            ['other', '2026-11-07', null, ['el' => 'Αγώνες Σαββάτου', 'en' => 'Saturday races']],
            ['other', '2026-11-08', null, ['el' => 'Μαραθώνιος και 10 χλμ.', 'en' => 'Marathon and 10 km']],
        ] as $position => [$type, $startsOn, $endsOn, $label]) {
            $event->dates()->create(['type' => $type, 'starts_on' => $startsOn, 'ends_on' => $endsOn, 'label' => $label, 'sort_order' => $position]);
        }

        // The marathon cut-offs from the English race page; the waves end at 18:15.
        $marathon = $event->races()->where('slug', 'marathon')->first();
        $marathon?->checkpoints()->delete();

        foreach ([
            5000 => '11:08', 7500 => '11:40', 10000 => '12:10', 12500 => '12:37', 15000 => '13:05', 17500 => '13:33',
            20000 => '14:02', 21097 => '14:14', 22500 => '14:31', 25000 => '14:58', 27500 => '15:27', 30000 => '15:54',
            32500 => '16:28', 35000 => '16:52', 37500 => '17:22', 40000 => '17:49', 42195 => '18:15',
        ] as $metres => $time) {
            $marathon?->checkpoints()->create(['distance_meters' => $metres, 'closes_at' => "2026-11-08 {$time}:00"]);
        }

        // The practical information of the race page.
        $event->sections()->delete();

        foreach ([
            ['transport', [
                'el' => "Τα λεωφορεία της διοργάνωσης αναχωρούν για την αφετηρία στον Μαραθώνα χωρίς στάσεις:\n\n- **Μπλοκ 1–10:** 05:00–06:15 από Συγγρού-Φιξ, Σύνταγμα, Πανεπιστήμιο, Ευαγγελισμό, Κατεχάκη και Ομόνοια.\n- **Μπλοκ 11–15:** 06:30–07:30 από Εθνική Άμυνα, Κατεχάκη και Νομισματοκοπείο.\n\nΗ λεωφόρος Μαραθώνος κλείνει από τις 07:00· αν έρθετε με αυτοκίνητο, να είστε στην αφετηρία έως τις 07:30.",
                'en' => "Organiser buses run to the start in Marathonas with no stops:\n\n- **Blocks 1–10:** 05:00–06:15 from Syngrou-Fix, Syntagma, Panepistimio, Evangelismos, Katechaki and Omonia.\n- **Blocks 11–15:** 06:30–07:30 from Ethniki Amyna, Katechaki and Nomismatokopio.\n\nMarathonos Avenue closes from 07:00; if you drive, be at the start by 07:30.",
            ]],
            ['bag_drop', [
                'el' => 'Δεν υπάρχει φύλαξη ρούχων στην αφετηρία την ημέρα του αγώνα. Παραδώστε την τσάντα σας στην έκθεση στο Κλειστό Φαλήρου, 4–7 Νοεμβρίου, και παραλάβετε τη μετά τον τερματισμό στη λεωφ. Βασ. Όλγας, κάτω από το Ζάππειο, δείχνοντας τον αριθμό σας.',
                'en' => 'There is no bag drop at the start on race day. Hand your bag in at the expo at the Faliro Indoor Hall, 4–7 November, and collect it after the finish on Vasilissis Olgas Avenue, below the Zappeion, by showing your bib.',
            ]],
            ['expo', [
                'el' => 'Η έκθεση και η παραλαβή αριθμών γίνονται στο Κλειστό Φαλήρου: Τετάρτη 4 Νοεμβρίου 12:00–21:00, Πέμπτη και Παρασκευή 10:00–21:00, Σάββατο 7 Νοεμβρίου 10:00–20:00. Φέρτε το email με τον αριθμό σας και ταυτότητα ή διαβατήριο. Την Κυριακή δεν δίνονται αριθμοί.',
                'en' => 'The expo and bib pickup are at the Faliro Indoor Hall: Wednesday 4 November 12:00–21:00, Thursday and Friday 10:00–21:00, Saturday 7 November 10:00–20:00. Bring the email with your bib number and an ID card or passport. No bibs are handed out on Sunday.',
            ]],
            ['road_closures', [
                'el' => 'Όλες οι διαδρομές είναι κλειστές για τα οχήματα. Η Τροχαία ανοίγει τους δρόμους σταδιακά, αφού περάσουν οι τελευταίοι δρομείς εντός του χρονικού ορίου.',
                'en' => 'All race routes are closed to traffic. The traffic police reopen the roads gradually once the last runners have passed within the time limit.',
            ]],
            ['medical', [
                'el' => 'Το ΕΚΑΒ και ομάδες διάσωσης καλύπτουν τη διαδρομή: ιατρική βοήθεια σε κάθε σταθμό, ασθενοφόρα σε βασικά σημεία και ένα πίσω από τους τελευταίους δρομείς. Φυσικοθεραπευτές στα 25 και 30 χλμ.',
                'en' => 'EKAB and rescue teams cover the course: medical help at every station, ambulances at key points and one behind the last runners. Physiotherapists at 25 and 30 km.',
            ]],
        ] as $position => [$type, $body]) {
            $event->sections()->create(['type' => $type, 'body' => $body, 'sort_order' => $position]);
        }
    }

    private function distanceClass(string $category): DistanceClass
    {
        return match ($category) {
            '5K' => DistanceClass::FiveK,
            '10K' => DistanceClass::TenK,
            'Half Marathon' => DistanceClass::HalfMarathon,
            'Marathon' => DistanceClass::Marathon,
            'Ultra' => DistanceClass::Ultra,
            default => DistanceClass::Other,
        };
    }

    /**
     * The races this seeder puts into the table.
     *
     * @return array<int, array{id: int, name: string, description: string, date: string, location: string, category: string, distance: string, participants: int, image: string}>
     */
    public static function catalogue(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Athens Marathon',
                'description' => 'The authentic marathon route from Marathon to Athens, following in the footsteps of the legendary run of 490 BC.',
                'date' => '2026-11-08',
                'location' => 'Athens, Greece',
                'category' => 'Marathon',
                'distance' => '42.2 km',
                'participants' => 18000,
                'image' => 'https://images.unsplash.com/photo-1552674605-db6ffd4facb5?w=800',
            ],
            [
                'id' => 2,
                'name' => 'Thessaloniki Coastal 10K',
                'description' => 'A scenic run along the Thessaloniki waterfront with views of the Thermaic Gulf.',
                'date' => '2026-05-16',
                'location' => 'Thessaloniki, Greece',
                'category' => '10K',
                'distance' => '10 km',
                'participants' => 4200,
                'image' => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=800',
            ],
            [
                'id' => 3,
                'name' => 'Meteora Trail Run',
                'description' => 'A challenging trail race through the dramatic rock formations and monasteries of Meteora.',
                'date' => '2026-09-20',
                'location' => 'Meteora, Greece',
                'category' => 'Trail',
                'distance' => '21.1 km',
                'participants' => 1500,
                'image' => 'https://images.unsplash.com/photo-1533107862482-0e6974b06ec4?w=800',
            ],
            [
                'id' => 4,
                'name' => 'Santorini Sunset Run',
                'description' => 'A picturesque evening run along the caldera, finishing with a view of the famous Santorini sunset.',
                'date' => '2026-06-05',
                'location' => 'Santorini, Greece',
                'category' => '5K',
                'distance' => '5 km',
                'participants' => 2800,
                'image' => 'https://images.unsplash.com/photo-1570077188670-e3a8d69ac5ff?w=800',
            ],
            [
                'id' => 5,
                'name' => 'Spartathlon',
                'description' => 'The historic ultramarathon from Athens to Sparta, retracing the route Pheidippides covered in a day and a half.',
                'date' => '2026-09-25',
                'location' => 'Athens to Sparta, Greece',
                'category' => 'Ultra',
                'distance' => '246 km',
                'participants' => 400,
                'image' => 'https://images.unsplash.com/photo-1571008887538-b36bb32f4571?w=800',
            ],
            [
                'id' => 6,
                'name' => 'Rhodes Marathon',
                'description' => 'A coastal marathon passing the medieval old town and the ancient stadium of Rhodes.',
                'date' => '2026-10-11',
                'location' => 'Rhodes, Greece',
                'category' => 'Marathon',
                'distance' => '42.2 km',
                'participants' => 3200,
                'image' => 'https://images.unsplash.com/photo-1552674605-db6ffd4facb5?w=800',
            ],
            [
                'id' => 7,
                'name' => 'Chania Half Marathon',
                'description' => 'A flat and fast half marathon through the Venetian harbour of Chania and the seafront of western Crete.',
                'date' => '2026-10-25',
                'location' => 'Chania, Greece',
                'category' => 'Half Marathon',
                'distance' => '21.1 km',
                'participants' => 2600,
                'image' => 'https://images.unsplash.com/photo-1502904550040-7534597429ae?w=800',
            ],
            [
                'id' => 8,
                'name' => 'Nafplio Night Run',
                'description' => 'An evening race through the old town, finishing under the floodlit walls of the Palamidi fortress.',
                'date' => '2026-11-21',
                'location' => 'Nafplio, Greece',
                'category' => '10K',
                'distance' => '10 km',
                'participants' => 3500,
                'image' => 'https://images.unsplash.com/photo-1476480862126-209bfaa8edc8?w=800',
            ],
            [
                'id' => 9,
                'name' => 'Ioannina Lake Run',
                'description' => 'A spring half marathon on the shore of Lake Pamvotida, with the island and the old city walls in sight.',
                'date' => '2027-03-14',
                'location' => 'Ioannina, Greece',
                'category' => 'Half Marathon',
                'distance' => '21.1 km',
                'participants' => 5000,
                'image' => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=800',
            ],
            [
                'id' => 10,
                'name' => 'Patras Seaside 5K',
                'description' => 'A short, family friendly race along the promenade of Patras, open to runners of every level.',
                'date' => '2027-04-18',
                'location' => 'Patras, Greece',
                'category' => '5K',
                'distance' => '5 km',
                'participants' => 1800,
                'image' => 'https://images.unsplash.com/photo-1570077188670-e3a8d69ac5ff?w=800',
            ],
            [
                'id' => 11,
                'name' => 'Olympus Marathon',
                'description' => 'A mountain race from Dion to the slopes of Mount Olympus, climbing more than two thousand metres on the way.',
                'date' => '2027-06-26',
                'location' => 'Mount Olympus, Greece',
                'category' => 'Trail',
                'distance' => '44 km',
                'participants' => 1200,
                'image' => 'https://images.unsplash.com/photo-1533107862482-0e6974b06ec4?w=800',
            ],
            [
                'id' => 12,
                'name' => 'Zagori Mountain Running',
                'description' => 'A summer ultra across the stone bridges and gorges of the Zagori villages in Epirus.',
                'date' => '2027-07-17',
                'location' => 'Zagori, Greece',
                'category' => 'Ultra',
                'distance' => '80 km',
                'participants' => 900,
                'image' => 'https://images.unsplash.com/photo-1486218119243-13883505764c?w=800',
            ],
        ];
    }

    /**
     * The number of metres a distance such as '42.2 km' stands for.
     */
    private function metres(string $distance): int
    {
        return (int) round(((float) $distance) * 1000);
    }
}
