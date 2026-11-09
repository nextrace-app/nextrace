<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Post;
use Database\Seeders\EventSeeder;
use Database\Seeders\PostSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RaceNewsTest extends TestCase
{
    use RefreshDatabase;

    private function event(array $attributes = []): Event
    {
        return Event::factory()->create(['published_at' => now()->subWeek(), ...$attributes]);
    }

    public function test_the_event_page_shows_the_three_latest_published_posts_newest_first(): void
    {
        $event = $this->event();
        $old = Post::factory()->create(['published_at' => now()->subDays(10)]);
        $newest = Post::factory()->create(['published_at' => now()->subDay()]);
        $middle = Post::factory()->create(['published_at' => now()->subDays(3)]);
        $older = Post::factory()->create(['published_at' => now()->subDays(5)]);
        $event->posts()->attach([$old->id, $newest->id, $middle->id, $older->id]);

        $this->get(route('events.show', $event->slug))->assertInertia(fn (Assert $page) => $page
            ->has('news.posts', 3)
            ->where('news.posts.0.slug', $newest->slug)
            ->where('news.posts.1.slug', $middle->slug)
            ->where('news.posts.2.slug', $older->slug)
            ->where('news.total', 4));
    }

    public function test_drafts_scheduled_and_unrelated_posts_are_left_out(): void
    {
        $event = $this->event();
        $published = Post::factory()->create();
        $event->posts()->attach([
            $published->id,
            Post::factory()->draft()->create()->id,
            Post::factory()->scheduled()->create()->id,
            Post::factory()->archived()->create()->id,
        ]);
        Post::factory()->create();

        $this->get(route('events.show', $event->slug))->assertInertia(fn (Assert $page) => $page
            ->has('news.posts', 1)
            ->where('news.posts.0.slug', $published->slug)
            ->where('news.total', 1));
    }

    public function test_an_event_without_posts_has_no_news(): void
    {
        $event = $this->event();

        $this->get(route('events.show', $event->slug))->assertInertia(fn (Assert $page) => $page
            ->where('news.posts', [])
            ->where('news.total', 0));
    }

    public function test_the_post_page_links_the_published_events_it_is_about(): void
    {
        $post = Post::factory()->create();
        $later = $this->event(['slug' => 'later', 'starts_on' => '2026-12-06', 'city' => 'Patras']);
        $sooner = $this->event(['slug' => 'sooner', 'starts_on' => '2026-11-08', 'city' => 'Athens']);
        $draft = Event::factory()->create(['published_at' => null]);
        $post->events()->attach([$later->id, $sooner->id, $draft->id]);

        $this->get(route('posts.show', $post))->assertInertia(fn (Assert $page) => $page
            ->has('events', 2)
            ->where('events.0.slug', 'sooner')
            ->where('events.0.city', 'Athens')
            ->where('events.1.slug', 'later'));
    }

    public function test_a_post_without_events_has_no_related_races(): void
    {
        $this->get(route('posts.show', Post::factory()->create()))->assertInertia(fn (Assert $page) => $page
            ->where('events', []));
    }

    public function test_the_posts_list_narrows_to_the_news_of_an_event(): void
    {
        $event = $this->event(['slug' => 'athens-marathon']);
        $news = Post::factory()->create();
        $event->posts()->attach([$news->id, Post::factory()->draft()->create()->id]);
        Post::factory()->count(2)->create();

        $this->get(route('posts.index', ['event' => 'athens-marathon']))
            ->assertOk()
            // It repeats posts the full list has, so it stays out of search results.
            ->assertSee('<meta name="robots" content="noindex, follow">', false)
            ->assertSee('<meta property="og:title" content="Νέα: ', false)
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts', 1)
                ->where('posts.0.slug', $news->slug)
                ->where('event.slug', 'athens-marathon')
                ->where('pagination.total', 1));

        // Scoped page meta lives for one request; the test makes two.
        $this->app->forgetScopedInstances();

        $this->get(route('posts.index'))
            ->assertSee('<meta name="robots" content="index, follow">', false)
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts', 3)
                ->where('event', null));
    }

    public function test_an_unknown_or_unpublished_event_in_the_filter_is_a_404(): void
    {
        Event::factory()->create(['slug' => 'draft-race', 'published_at' => null]);

        $this->get(route('posts.index', ['event' => 'no-such-race']))->assertNotFound();
        $this->get(route('posts.index', ['event' => 'draft-race']))->assertNotFound();
        $this->get(route('posts.index').'?event[]=x')->assertNotFound();
    }

    public function test_the_seeders_give_the_athens_marathon_two_news_posts_and_a_draft(): void
    {
        $this->seed([EventSeeder::class, PostSeeder::class]);
        $this->seed(PostSeeder::class);

        $athens = Event::query()->where('slug', 'athens-marathon')->firstOrFail();

        $this->assertSame(3, $athens->posts()->count());
        $this->assertSame(2, $athens->posts()->published()->count());

        $this->get(route('events.show', 'athens-marathon'))->assertInertia(fn (Assert $page) => $page
            ->has('news.posts', 2)
            ->where('news.posts.0.slug', 'athens-marathon-expo-and-bib-pickup'));
    }
}
