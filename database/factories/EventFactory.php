<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Creates events with one main race, which is what a valid event has.
 *
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Whether create() adds the main race.
     */
    protected bool $createsMainRace = true;

    /**
     * Attributes of the main race the factory creates, e.g. its distance.
     *
     * @var array<string, mixed>
     */
    protected array $mainRaceAttributes = [];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $city = fake()->randomElement(['Athens', 'Thessaloniki', 'Chania', 'Nafplio', 'Ioannina', 'Patras']);
        $suffix = fake()->randomElement(['Run', 'Night Run', 'Classic', 'Challenge']);

        $name = "{$city} {$suffix}";

        return [
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 100000),
            'name' => ['el' => $name, 'en' => $name],
            'description' => [
                'el' => fake()->sentence(),
                'en' => fake()->sentence(),
            ],
            'starts_on' => fake()->dateTimeBetween('+1 week', '+1 year')->format('Y-m-d'),
            'ends_on' => null,
            'city' => $city,
            'country' => 'GR',
            'latitude' => fake()->latitude(35, 41),
            'longitude' => fake()->longitude(20, 28),
            'participants' => fake()->numberBetween(400, 18000),
            'image_url' => fake()->imageUrl(800, 600),
            'registration_url' => fake()->url(),
            'published_at' => now(),
        ];
    }

    /**
     * Create the events, and the main race each of them has unless told otherwise.
     *
     * This wraps create() instead of using afterCreating(), whose callbacks
     * would be bound to the first factory of a chain and miss later states.
     *
     * @param  array<string, mixed>|(callable(array<string, mixed>): array<string, mixed>)  $attributes
     * @return Collection<int, Event>|Event
     */
    public function create($attributes = [], ?Model $parent = null)
    {
        $created = parent::create($attributes, $parent);

        foreach (Collection::wrap($created) as $event) {
            $this->addMainRace($event);
        }

        return $created;
    }

    private function addMainRace(Event $event): void
    {
        if (! $this->createsMainRace || $event->races()->exists()) {
            return;
        }

        $attributes = $this->mainRaceAttributes;

        // Races start at a time on the day of the event unless told otherwise.
        $attributes['starts_at'] ??= $event->starts_on->copy()->setTime(9, 0);

        RaceFactory::new()->main()->for($event)->create($attributes);
    }

    /**
     * Keep the main race settings when the factory is chained.
     *
     * @param  array<string, mixed>  $arguments
     */
    protected function newInstance(array $arguments = []): static
    {
        $instance = parent::newInstance($arguments);
        $instance->createsMainRace = $this->createsMainRace;
        $instance->mainRaceAttributes = $this->mainRaceAttributes;

        return $instance;
    }

    /**
     * Indicate that the event is not published yet.
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => null,
        ]);
    }

    /**
     * Create the event without any race.
     */
    public function withoutRaces(): static
    {
        $instance = $this->newInstance();
        $instance->createsMainRace = false;

        return $instance;
    }

    /**
     * Give the main race the factory creates these attributes.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function withMainRace(array $attributes): static
    {
        $instance = $this->newInstance();
        $instance->mainRaceAttributes = [...$this->mainRaceAttributes, ...$attributes];

        return $instance;
    }

    /**
     * Start the event, and its main race, at a local date and time.
     */
    public function startingAt(Carbon|string $startsAt): static
    {
        $startsAt = Carbon::parse($startsAt);

        return $this->state(fn () => ['starts_on' => $startsAt->toDateString()])
            ->withMainRace(['starts_at' => $startsAt, 'start_time_known' => $startsAt->format('H:i:s') !== '00:00:00']);
    }

    /**
     * Indicate that the date is not confirmed by the organiser yet.
     */
    public function dateUnconfirmed(): static
    {
        return $this->state(fn () => ['date_confirmed' => false]);
    }

    /**
     * Indicate that the event is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => EventStatus::Cancelled]);
    }

    /**
     * Indicate that registration is open.
     */
    public function registrationOpen(): static
    {
        return $this->state(fn () => ['registration_status' => RegistrationStatus::Open]);
    }
}
