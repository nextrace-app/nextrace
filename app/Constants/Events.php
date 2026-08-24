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
