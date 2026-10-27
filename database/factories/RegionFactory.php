<?php

namespace Database\Factories;

use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Region>
 */
class RegionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Heraklion', 'Chania', 'Rethymno', 'Lasithi', 'Attica', 'Epirus', 'Thessaly', 'Peloponnese']);

        return [
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 100000),
            'name' => ['en' => $name, 'el' => $name],
            'parent_id' => null,
        ];
    }

    /**
     * Nest the region inside another one.
     */
    public function within(Region $parent): static
    {
        return $this->state(fn () => ['parent_id' => $parent->id]);
    }
}
