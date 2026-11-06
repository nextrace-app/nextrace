<?php

namespace Tests\Feature;

use App\Enums\PostCategory;
use App\Enums\PostStatus;
use App\Models\ApiToken;
use App\Models\Event;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiPostsTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/posts';

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        [, $plain] = ApiToken::issue('scout', [ApiToken::POSTS_WRITE], $this->owner);

        $this->withToken($plain);
    }

    public function test_it_needs_a_token_with_the_posts_ability(): void
    {
        $this->withToken('nr_unknown')->postJson(self::URL, $this->payload())->assertUnauthorized();

        [, $readOnly] = ApiToken::issue('reader', [ApiToken::EVENTS_READ]);
        $this->withToken($readOnly)->postJson(self::URL, $this->payload())->assertForbidden();

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_it_stores_a_draft_owned_by_the_token_user(): void
    {
        $this->postJson(self::URL, $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.slug', 'spring-marathon-opens')
            ->assertJsonStructure(['data' => ['id', 'slug', 'status']]);

        $post = Post::query()->sole();

        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertNull($post->published_at);
        $this->assertSame($this->owner->id, $post->author_id);
        $this->assertSame(PostCategory::News, $post->category);
        $this->assertSame(['en' => 'Spring marathon opens'], $post->title);
        $this->assertSame(['en' => '# Hello'], $post->body);
    }

    public function test_a_stored_post_is_never_visible_on_the_site(): void
    {
        $this->postJson(self::URL, $this->payload())->assertCreated();

        $this->assertSame(0, Post::query()->published()->count());
    }

    public function test_fields_that_would_publish_are_refused(): void
    {
        foreach ([['status' => 'published'], ['status' => 'draft'], ['published_at' => '2026-01-01 10:00:00'], ['author_id' => 1]] as $extra) {
            $this->postJson(self::URL, $this->payload($extra))->assertUnprocessable()->assertJsonValidationErrors(array_key_first($extra));
        }

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_text_is_saved_under_the_locale_of_the_request(): void
    {
        $this->postJson(self::URL, $this->payload([
            'locale' => 'el',
            'title' => 'Ο μαραθώνιος ανοίγει',
            'excerpt' => 'Σύντομα',
            'cover_image_alt' => 'Δρομείς',
            'meta_title' => 'Μαραθώνιος',
            'meta_description' => 'Περιγραφή',
        ]))->assertCreated();

        $post = Post::query()->sole();

        $this->assertSame(['el' => 'Ο μαραθώνιος ανοίγει'], $post->title);
        $this->assertSame(['el' => 'Σύντομα'], $post->excerpt);
        $this->assertSame(['el' => 'Δρομείς'], $post->cover_image_alt);
        $this->assertSame(['el' => 'Μαραθώνιος'], $post->meta_title);
        $this->assertSame(['el' => 'Περιγραφή'], $post->meta_description);
    }

    public function test_optional_fields_are_saved(): void
    {
        $this->postJson(self::URL, $this->payload([
            'source_url' => 'https://example.com/news/1',
            'cover_image_url' => 'https://example.com/cover.jpg',
        ]))->assertCreated();

        $post = Post::query()->sole();

        $this->assertSame('https://example.com/news/1', $post->source_url);
        $this->assertSame('https://example.com/cover.jpg', $post->cover_image_url);
        $this->assertNull($post->excerpt);
    }

    public function test_the_slug_is_built_from_the_title_and_made_unique(): void
    {
        $this->postJson(self::URL, $this->payload())->assertCreated()->assertJsonPath('data.slug', 'spring-marathon-opens');
        $this->postJson(self::URL, $this->payload())->assertCreated()->assertJsonPath('data.slug', 'spring-marathon-opens-2');

        Post::query()->where('slug', 'spring-marathon-opens-2')->first()->delete();

        $this->postJson(self::URL, $this->payload())->assertCreated()->assertJsonPath('data.slug', 'spring-marathon-opens-3');
    }

    public function test_a_greek_title_gets_a_latin_slug(): void
    {
        $this->postJson(self::URL, $this->payload(['locale' => 'el', 'title' => 'Ο Μαραθώνιος ανοίγει']))->assertCreated();

        $this->assertMatchesRegularExpression('/^[a-z0-9]+(-[a-z0-9]+)*$/', Post::query()->sole()->slug);
    }

    public function test_a_title_without_letters_still_gets_a_slug(): void
    {
        $this->postJson(self::URL, $this->payload(['title' => '!!!']))->assertCreated()->assertJsonPath('data.slug', 'post');
    }

    public function test_a_given_slug_is_used_and_must_be_free_and_well_formed(): void
    {
        $this->postJson(self::URL, $this->payload(['slug' => 'my-slug']))->assertCreated()->assertJsonPath('data.slug', 'my-slug');
        $this->postJson(self::URL, $this->payload(['slug' => 'my-slug']))->assertUnprocessable()->assertJsonValidationErrors('slug');
        $this->postJson(self::URL, $this->payload(['slug' => 'Not A Slug']))->assertUnprocessable()->assertJsonValidationErrors('slug');
    }

    public function test_a_source_url_can_be_sent_only_once(): void
    {
        $this->postJson(self::URL, $this->payload(['source_url' => 'https://example.com/a']))->assertCreated();

        $this->postJson(self::URL, $this->payload(['source_url' => 'https://example.com/a']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('source_url');

        $this->assertDatabaseCount('posts', 1);
    }

    public function test_it_attaches_the_events(): void
    {
        $events = Event::factory()->count(2)->create();

        $this->postJson(self::URL, $this->payload(['event_ids' => $events->pluck('id')->all()]))->assertCreated();

        $this->assertEqualsCanonicalizing($events->pluck('id')->all(), Post::query()->sole()->events->pluck('id')->all());
    }

    public function test_unknown_or_repeated_events_are_refused(): void
    {
        $event = Event::factory()->create();

        $this->postJson(self::URL, $this->payload(['event_ids' => [$event->id, 99999]]))->assertUnprocessable()->assertJsonValidationErrors('event_ids.1');
        $this->postJson(self::URL, $this->payload(['event_ids' => [$event->id, $event->id]]))->assertUnprocessable();
        $this->postJson(self::URL, $this->payload(['event_ids' => 'all']))->assertUnprocessable()->assertJsonValidationErrors('event_ids');

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_the_required_fields_are_checked(): void
    {
        $this->postJson(self::URL, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['locale', 'title', 'body', 'category']);
    }

    public function test_bad_values_are_refused(): void
    {
        foreach ([
            ['locale' => 'fr'],
            ['category' => 'gossip'],
            ['title' => str_repeat('a', 256)],
            ['title' => ['array']],
            ['source_url' => 'not a url'],
            ['source_url' => 'javascript:alert(1)'],
            ['cover_image_url' => 'ftp://example.com/a.jpg'],
        ] as $extra) {
            $this->postJson(self::URL, $this->payload($extra))->assertUnprocessable();
        }

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_a_token_without_a_user_stores_a_post_without_an_author(): void
    {
        [, $plain] = ApiToken::issue('anonymous', [ApiToken::POSTS_WRITE]);

        $this->withToken($plain)->postJson(self::URL, $this->payload())->assertCreated();

        $this->assertNull(Post::query()->sole()->author_id);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'locale' => 'en',
            'title' => 'Spring marathon opens',
            'body' => '# Hello',
            'category' => 'news',
            ...$overrides,
        ];
    }
}
