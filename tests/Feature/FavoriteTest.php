<?php

namespace Tests\Feature;

use App\Constants\Events;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_login_page_of_their_locale(): void
    {
        $this->post(route('events.favorite.store', ['event' => 1]))->assertRedirect(route('login'));
        $this->post(route('en.events.favorite.store', ['event' => 1]))->assertRedirect(route('en.login'));

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_the_favorites_page_is_only_for_signed_in_users(): void
    {
        $this->get(route('favorites.index'))->assertRedirect(route('login'));
        $this->get(route('en.favorites.index'))->assertRedirect(route('en.login'));
    }

    public function test_the_favorites_page_lists_the_events_of_the_current_user_newest_first(): void
    {
        $user = User::factory()->create();
        $someoneElse = User::factory()->create();

        Favorite::factory()->for($user)->forEvent(1)->create(['created_at' => now()->subDay()]);
        Favorite::factory()->for($user)->forEvent(3)->create(['created_at' => now()]);
        Favorite::factory()->for($someoneElse)->forEvent(2)->create();

        $this->actingAs($user)->get(route('favorites.index'))->assertInertia(
            fn ($page) => $page
                ->component('Favorites/Index')
                ->where('events.0.id', 3)
                ->where('events.1.id', 1)
                ->count('events', 2)
        );
    }

    public function test_the_favorites_page_is_empty_without_favorites(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('favorites.index'))->assertInertia(
            fn ($page) => $page->component('Favorites/Index')->count('events', 0)
        );
    }

    public function test_the_favorites_page_skips_events_that_no_longer_exist(): void
    {
        $user = User::factory()->create();
        Favorite::factory()->for($user)->forEvent(1)->create();
        Favorite::factory()->for($user)->forEvent(999999)->create();

        $this->actingAs($user)->get(route('favorites.index'))->assertInertia(
            fn ($page) => $page->count('events', 1)->where('events.0.id', 1)
        );
    }

    public function test_a_user_can_add_an_event_to_their_favorites(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('events.index'))
            ->post(route('events.favorite.store', ['event' => 1]))
            ->assertRedirect(route('events.index'));

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'event_id' => 1,
        ]);
    }

    public function test_adding_the_same_event_twice_keeps_a_single_favorite(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('events.favorite.store', ['event' => 1]));
        $this->actingAs($user)->post(route('events.favorite.store', ['event' => 1]));

        $this->assertDatabaseCount('favorites', 1);
    }

    public function test_a_user_can_remove_an_event_from_their_favorites(): void
    {
        $user = User::factory()->create();
        Favorite::factory()->for($user)->forEvent(1)->create();

        $this->actingAs($user)->delete(route('events.favorite.destroy', ['event' => 1]));

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_favorites_of_other_users_are_left_alone(): void
    {
        $user = User::factory()->create();
        $someoneElse = User::factory()->create();
        Favorite::factory()->for($someoneElse)->forEvent(1)->create();

        $this->actingAs($user)->delete(route('events.favorite.destroy', ['event' => 1]));

        $this->assertDatabaseHas('favorites', [
            'user_id' => $someoneElse->id,
            'event_id' => 1,
        ]);
    }

    public function test_an_unknown_event_cannot_be_favorited(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('events.favorite.store', ['event' => 999999]))
            ->assertNotFound();

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_the_events_page_reports_the_favorites_of_the_current_user_only(): void
    {
        $user = User::factory()->create();
        $someoneElse = User::factory()->create();

        Favorite::factory()->for($user)->forEvent(2)->create();
        Favorite::factory()->for($someoneElse)->forEvent(3)->create();

        $this->actingAs($user)->get(route('events.index'))->assertInertia(
            fn ($page) => $page->component('Events/Index')->where('favorites', [2])
        );
    }

    public function test_the_events_page_reports_no_favorites_for_guests(): void
    {
        $this->get(route('events.index'))->assertInertia(
            fn ($page) => $page->where('favorites', [])
        );
    }

    public function test_the_event_page_reports_whether_it_is_a_favorite(): void
    {
        $user = User::factory()->create();
        $eventId = Events::LIST[0]['id'];

        $this->actingAs($user)->get(route('events.show', ['event' => $eventId]))->assertInertia(
            fn ($page) => $page->where('isFavorite', false)
        );

        Favorite::factory()->for($user)->forEvent($eventId)->create();

        $this->actingAs($user)->get(route('events.show', ['event' => $eventId]))->assertInertia(
            fn ($page) => $page->where('isFavorite', true)
        );
    }
}
