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

    public function test_visiting_an_unknown_event_returns_a_404(): void
    {
        $response = $this->get(route('events.show', ['event' => 999999]));
        $response->assertNotFound();
    }
}
