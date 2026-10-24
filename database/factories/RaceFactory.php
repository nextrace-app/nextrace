<?php

namespace Database\Factories;

use App\Enums\DistanceClass;
use App\Enums\RaceDiscipline;
use App\Enums\RaceTerrain;
use App\Models\Event;
use App\Models\Race;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<Race>
 */
class RaceFactory extends Factory
{
    /**
     * The distances races are run over in Greece, in metres.
     *
     * @var array<int, array{0: DistanceClass, 1: int, 2: string, 3: string}>
     */
    private const DISTANCES = [
        [DistanceClass::FiveK, 5000, '5 km', '5 χλμ.'],
        [DistanceClass::TenK, 10000, '10 km', '10 χλμ.'],
        [DistanceClass::HalfMarathon, 21097, 'Half marathon', 'Ημιμαραθώνιος'],
        [DistanceClass::Marathon, 42195, 'Marathon', 'Μαραθώνιος'],
        [DistanceClass::Ultra, 70000, 'Ultra 70 km', 'Υπερμαραθώνιος 70 χλμ.'],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$class, $metres, $en, $el] = fake()->randomElement(self::DISTANCES);

        return [
            'event_id' => Event::factory()->withoutRaces(),
            'slug' => Str::slug(str_replace('_', '-', $class->value)).'-'.fake()->unique()->numberBetween(1, 100000),
            'name' => ['en' => $en, 'el' => $el],
            'sort_order' => 0,
            'is_main' => false,
            'discipline' => RaceDiscipline::Run,
            'terrain' => RaceTerrain::Road,
            'distance_class' => $class,
            'distance_meters' => $metres,
            'starts_at' => fake()->dateTimeBetween('+1 week', '+1 year')->setTime(9, 0),
            'start_time_known' => true,
            'is_competitive' => true,
            'status' => 'scheduled',
        ];
    }

    /**
     * Indicate that this is the main race of its event.
     */
    public function main(): static
    {
        return $this->state(fn () => ['is_main' => true]);
    }

    /**
     * Indicate that the organiser has not announced the start time yet.
     */
    public function startTimeUnknown(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time_known' => false,
            'starts_at' => Carbon::parse($attributes['starts_at'])->startOfDay(),
        ]);
    }

    /**
     * A trail race over a given distance.
     */
    public function trail(int $metres = 24700, int $elevationGain = 1290): static
    {
        return $this->state(fn () => [
            'terrain' => RaceTerrain::Trail,
            'distance_class' => DistanceClass::Other,
            'distance_meters' => $metres,
            'elevation_gain_m' => $elevationGain,
        ]);
    }
}
