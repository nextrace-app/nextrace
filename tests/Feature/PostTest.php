<?php

namespace Tests\Feature;

use App\Enums\PostCategory;
use App\Enums\PostStatus;
use App\Models\Event;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_published_scope_returns_only_published_posts_whose_date_has_come(): void
    {
        $visible = Post::factory()->create();
        Post::factory()->draft()->create();
        Post::factory()->scheduled()->create();
        Post::factory()->archived()->create();

        $this->assertSame([$visible->id], Post::query()->published()->pluck('id')->all());
    }

    public function test_a_post_defaults_to_draft(): void
    {
        $post = Post::create(Arr::except(Post::factory()->raw(), ['status']));

        $this->assertSame(PostStatus::Draft, $post->fresh()->status);
    }

    public function test_status_and_category_are_cast_to_enums(): void
    {
        $post = Post::factory()->create(['category' => PostCategory::Guide]);

        $this->assertSame(PostCategory::Guide, $post->fresh()->category);
        $this->assertSame(PostStatus::Published, $post->fresh()->status);
    }

    public function test_translated_text_follows_the_locale_and_falls_back(): void
    {
        $post = Post::factory()->create([
            'title' => ['el' => 'Τίτλος', 'en' => 'Title'],
            'excerpt' => ['en' => 'Only English'],
        ]);

        app()->setLocale('el');

        $this->assertSame('Τίτλος', $post->translated('title'));
        $this->assertSame('Only English', $post->translated('excerpt'));
        $this->assertNull($post->translatedOrNull('meta_title'));
    }

    public function test_the_slug_is_unique(): void
    {
        Post::factory()->create(['slug' => 'same']);

        $this->expectException(QueryException::class);

        Post::factory()->create(['slug' => 'same']);
    }

    public function test_a_post_is_found_by_its_slug(): void
    {
        $post = Post::factory()->create(['slug' => 'spring-news']);

        $this->assertTrue($post->is((new Post)->resolveRouteBinding('spring-news')));
    }

    public function test_a_post_is_linked_to_events_in_both_directions(): void
    {
        $post = Post::factory()->create();
        $event = Event::factory()->create();

        $post->events()->attach($event);

        $this->assertTrue($event->posts->contains($post));
        $this->assertTrue($post->events->contains($event));
    }

    public function test_the_same_event_cannot_be_attached_to_a_post_twice(): void
    {
        $post = Post::factory()->create();
        $event = Event::factory()->create();
        $post->events()->attach($event);

        $this->expectException(QueryException::class);

        $post->events()->attach($event);
    }

    public function test_deleting_an_event_detaches_its_posts(): void
    {
        $post = Post::factory()->create();
        $event = Event::factory()->create();
        $post->events()->attach($event);

        $event->forceDelete();

        $this->assertCount(0, $post->fresh()->events);
        $this->assertDatabaseCount('event_post', 0);
    }

    public function test_deleting_the_author_keeps_the_post(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->create(['author_id' => $author->id]);

        $this->assertTrue($post->author->is($author));

        $author->delete();

        $this->assertNull($post->fresh()->author_id);
    }

    public function test_posts_are_soft_deleted(): void
    {
        $post = Post::factory()->create();

        $post->delete();

        $this->assertSoftDeleted($post);
    }
}
