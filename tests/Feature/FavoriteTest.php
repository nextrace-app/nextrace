<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_login_page_of_their_locale(): void
    {
        $event = Event::factory()->create();

        $this->post(route('events.favorite.store', ['event' => $event]))->assertRedirect(route('login'));
        $this->post(route('el.events.favorite.store', ['event' => $event]))->assertRedirect(route('el.login'));

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_the_favorites_page_is_only_for_signed_in_users(): void
    {
        $this->get(route('favorites.index'))->assertRedirect(route('login'));
        $this->get(route('el.favorites.index'))->assertRedirect(route('el.login'));
    }

    public function test_the_favorites_page_lists_the_events_of_the_current_user_newest_first(): void
    {
        $user = User::factory()->create();
        $someoneElse = User::factory()->create();

        [$first, $second, $third] = Event::factory()->count(3)->create();

        Favorite::factory()->for($user)->forEvent($first->id)->create(['created_at' => now()->subDay()]);
        Favorite::factory()->for($user)->forEvent($third->id)->create(['created_at' => now()]);
        Favorite::factory()->for($someoneElse)->forEvent($second->id)->create();

        $this->actingAs($user)->get(route('favorites.index'))->assertInertia(
            fn ($page) => $page
                ->component('Favorites/Index')
                ->where('events.0.id', $third->id)
                ->where('events.1.id', $first->id)
                ->count('events', 2)
        );
    }

    public function test_a_past_event_stays_among_the_favorites_and_the_favorite_ids(): void
    {
        $this->travelTo('2026-10-24');

        $user = User::factory()->create();
        $past = Event::factory()->startingAt('2026-03-01 09:00:00')->create();
        Favorite::factory()->for($user)->forEvent($past->id)->create();

        $this->actingAs($user)->get(route('favorites.index'))->assertInertia(
            fn ($page) => $page->where('events.0.id', $past->id)->count('events', 1)
        );

        $this->actingAs($user)->get(route('events.index'))->assertInertia(
            fn ($page) => $page->count('events', 0)->where('favorites', [$past->id])
        );
    }

    public function test_the_favorites_page_is_empty_without_favorites(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('favorites.index'))->assertInertia(
            fn ($page) => $page->component('Favorites/Index')->count('events', 0)
        );
    }

    public function test_a_deleted_event_takes_the_favorites_of_it_along(): void
    {
        $user = User::factory()->create();
        $kept = Event::factory()->create();
        $deleted = Event::factory()->create();

        Favorite::factory()->for($user)->forEvent($kept->id)->create();
        Favorite::factory()->for($user)->forEvent($deleted->id)->create();

        $deleted->forceDelete();

        $this->assertDatabaseCount('favorites', 1);

        $this->actingAs($user)->get(route('favorites.index'))->assertInertia(
            fn ($page) => $page->count('events', 1)->where('events.0.id', $kept->id)
        );
    }

    public function test_a_user_can_add_an_event_to_their_favorites(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();

        $this->actingAs($user)
            ->from(route('events.index'))
            ->post(route('events.favorite.store', ['event' => $event]))
            ->assertRedirect(route('events.index'));

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'event_id' => $event->id,
        ]);
    }

    public function test_adding_the_same_event_twice_keeps_a_single_favorite(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();

        $this->actingAs($user)->post(route('events.favorite.store', ['event' => $event]));
        $this->actingAs($user)->post(route('events.favorite.store', ['event' => $event]));

        $this->assertDatabaseCount('favorites', 1);
    }

    public function test_a_user_can_remove_an_event_from_their_favorites(): void
    {
        $user = User::factory()->create();
        $event = Event::factory()->create();
        Favorite::factory()->for($user)->forEvent($event->id)->create();

        $this->actingAs($user)->delete(route('events.favorite.destroy', ['event' => $event]));

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_favorites_of_other_users_are_left_alone(): void
    {
        $user = User::factory()->create();
        $someoneElse = User::factory()->create();
        $event = Event::factory()->create();
        Favorite::factory()->for($someoneElse)->forEvent($event->id)->create();

        $this->actingAs($user)->delete(route('events.favorite.destroy', ['event' => $event]));

        $this->assertDatabaseHas('favorites', [
            'user_id' => $someoneElse->id,
            'event_id' => $event->id,
        ]);
    }

    public function test_an_unknown_event_cannot_be_favorited(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('events.favorite.store', ['event' => 'no-such-race']))
            ->assertNotFound();

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_the_events_page_reports_the_favorites_of_the_current_user_only(): void
    {
        $user = User::factory()->create();
        $someoneElse = User::factory()->create();

        [$mine, $theirs] = Event::factory()->count(2)->create();

        Favorite::factory()->for($user)->forEvent($mine->id)->create();
        Favorite::factory()->for($someoneElse)->forEvent($theirs->id)->create();

        $this->actingAs($user)->get(route('events.index'))->assertInertia(
            fn ($page) => $page->component('Events/Index')->where('favorites', [$mine->id])
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
        $event = Event::factory()->create();

        $this->actingAs($user)->get(route('events.show', ['event' => $event]))->assertInertia(
            fn ($page) => $page->where('isFavorite', false)
        );

        Favorite::factory()->for($user)->forEvent($event->id)->create();

        $this->actingAs($user)->get(route('events.show', ['event' => $event]))->assertInertia(
            fn ($page) => $page->where('isFavorite', true)
        );
    }
}
