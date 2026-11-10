<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Event;
use App\Models\FeatureFlag;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PostsFlagTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->switchFeature(Post::FEATURE, false);
    }

    public function test_the_application_ships_the_flag_switched_off(): void
    {
        $this->assertDatabaseHas('feature_flags', ['key' => Post::FEATURE]);

        $this->assertFalse(FeatureFlag::query()->where('key', Post::FEATURE)->value('is_active'));
    }

    public function test_the_posts_pages_do_not_exist(): void
    {
        $post = Post::factory()->create();

        $this->get(route('posts.index'))->assertNotFound();
        $this->get(route('posts.show', $post))->assertNotFound();
        $this->get(route('en.posts.index'))->assertNotFound();
        $this->get(route('en.posts.show', $post))->assertNotFound();
    }

    public function test_a_missing_flag_also_hides_the_posts(): void
    {
        FeatureFlag::query()->where('key', Post::FEATURE)->delete();

        $this->get(route('posts.index'))->assertNotFound();
    }

    public function test_the_event_page_carries_no_race_news(): void
    {
        $event = Event::factory()->create();
        $event->posts()->attach(Post::factory()->create());

        $this->get(route('events.show', $event))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('news.posts', [])
            ->where('news.total', 0)
            ->where('features', fn ($features) => ! collect($features)->contains(Post::FEATURE)));
    }

    public function test_the_sitemap_leaves_the_posts_out(): void
    {
        $post = Post::factory()->create();

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('<loc>'.route('events.index').'</loc>', $xml);
        $this->assertStringNotContainsString(route('posts.index'), $xml);
        $this->assertStringNotContainsString(route('posts.show', $post), $xml);
    }

    public function test_the_api_still_takes_posts_in(): void
    {
        [, $plain] = ApiToken::issue('scout', [ApiToken::POSTS_WRITE], User::factory()->create());

        $this->withToken($plain)
            ->postJson('/api/v1/posts', ['locale' => 'en', 'title' => 'Spring marathon opens', 'body' => '# Hello', 'category' => 'news'])
            ->assertCreated();

        $this->assertDatabaseCount('posts', 1);
    }

    public function test_switching_the_flag_on_brings_the_posts_back(): void
    {
        $event = Event::factory()->create();
        $post = Post::factory()->create();
        $event->posts()->attach($post);

        $this->switchFeature(Post::FEATURE, true);

        $this->get(route('posts.show', $post))->assertOk();
        $this->get(route('events.show', $event))->assertInertia(fn (Assert $page) => $page
            ->where('news.total', 1)
            ->where('features', fn ($features) => collect($features)->contains(Post::FEATURE)));
        $this->assertStringContainsString(route('posts.show', $post), $this->get('/sitemap.xml')->getContent());
    }
}
