<?php

namespace App\Constants;

class Events
{
    /**
     * @var array<int, array{id: int, name: string, description: string, date: string, location: string, category: string, distance: string, participants: int, image: string}>
     */
    public const LIST = [
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

    /**
     * The event with the given id, or null when there is no such event.
     *
     * @return array{id: int, name: string, description: string, date: string, location: string, category: string, distance: string, participants: int, image: string}|null
     */
    public static function find(int $id): ?array
    {
        foreach (self::LIST as $event) {
            if ($event['id'] === $id) {
                return $event;
            }
        }

        return null;
    }
}
