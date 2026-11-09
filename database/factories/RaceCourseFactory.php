<?php

namespace Database\Factories;

use App\Models\Race;
use App\Models\RaceCourse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RaceCourse>
 */
class RaceCourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'race_id' => Race::factory(),
            'gpx_path' => 'gpx/'.fake()->uuid().'.gpx',
            'gpx_name' => 'course.gpx',
            'distance_meters' => 10000,
            'elevation_gain_m' => 120,
            'elevation_loss_m' => 110,
            'highest_point_m' => 180,
            'track' => [[37.97, 23.74], [37.98, 23.75], [37.99, 23.76]],
            'profile' => [[0, 60], [5, 180], [10, 70]],
        ];
    }
}
