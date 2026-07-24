<?php

namespace Tests\Feature;

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
}
