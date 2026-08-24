<?php

namespace Tests\Feature;

use App\Constants\Events;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_visit_the_events_page(): void
    {
        $response = $this->get(route('events.index'));
        $response->assertOk();
    }

    public function test_authenticated_users_can_visit_the_events_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('events.index'));
        $response->assertOk();
    }

    public function test_guests_can_visit_an_event_page(): void
    {
        $response = $this->get(route('events.show', ['event' => Events::LIST[0]['id']]));
        $response->assertOk();
    }

    public function test_every_event_in_the_catalogue_is_complete_and_uniquely_numbered(): void
    {
        $ids = array_column(Events::LIST, 'id');

        $this->assertSame($ids, array_unique($ids), 'Two events share an id.');

        foreach (Events::LIST as $event) {
            $this->assertSame(
                ['id', 'name', 'description', 'date', 'location', 'category', 'distance', 'participants', 'image'],
                array_keys($event),
                "Event [{$event['id']}] does not carry the expected fields."
            );

            $this->assertMatchesRegularExpression(
                '/^\d{4}-\d{2}-\d{2}$/',
                $event['date'],
                "Event [{$event['id']}] has a date the frontend cannot format."
            );
        }
    }

    public function test_every_event_in_the_catalogue_has_its_own_page(): void
    {
        foreach (Events::LIST as $event) {
            $this->get(route('events.show', ['event' => $event['id']]))->assertOk();
        }
    }

    public function test_visiting_an_unknown_event_returns_a_404(): void
    {
        $response = $this->get(route('events.show', ['event' => 999999]));
        $response->assertNotFound();
    }
}
