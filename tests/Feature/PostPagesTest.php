<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PostPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_list_shows_published_posts_newest_first(): void
    {
        $older = Post::factory()->create(['published_at' => now()->subDays(3)]);
        $newer = Post::factory()->create(['published_at' => now()->subDay()]);

        $this->get(route('posts.index'))->assertOk()->assertInertia(
            fn (Assert $page) => $page
                ->component('Posts/Index')
                ->count('posts', 2)
                ->where('posts.0.id', $newer->id)
                ->where('posts.1.id', $older->id)
                ->where('posts.0.category', $newer->category->value)
                ->where('posts.0.date', $newer->published_at->toDateString())
        );
    }

    public function test_the_list_hides_drafts_scheduled_archived_and_deleted_posts(): void
    {
        $visible = Post::factory()->create();
        Post::factory()->draft()->create();
        Post::factory()->scheduled()->create();
        Post::factory()->archived()->create();
        Post::factory()->create()->delete();

        $this->get(route('posts.index'))->assertInertia(
            fn (Assert $page) => $page->count('posts', 1)->where('posts.0.id', $visible->id)
        );
    }

    public function test_the_list_is_split_into_pages_with_addresses_of_their_own(): void
    {
        config(['posts.per_page' => 2]);

        $posts = collect(range(1, 5))->map(
            fn (int $day) => Post::factory()->create(['published_at' => now()->subDays($day)])
        );

        $this->get(route('posts.index'))->assertInertia(
            fn (Assert $page) => $page
                ->count('posts', 2)
                ->where('posts.0.id', $posts[0]->id)
                ->where('pagination', ['current' => 1, 'last' => 3, 'total' => 5, 'perPage' => 2])
        );

        $this->get(route('en.posts.index', ['page' => 3]))->assertInertia(
            fn (Assert $page) => $page
                ->count('posts', 1)
                ->where('posts.0.id', $posts[4]->id)
                ->where('pagination.current', 3)
        );
    }

    public function test_the_first_page_has_no_page_parameter(): void
    {
        $this->get(route('posts.index', ['page' => 1]))->assertRedirect(route('posts.index'))->assertStatus(301);
    }

    public function test_a_page_that_does_not_exist_is_a_404(): void
    {
        Post::factory()->create();

        $this->get(route('posts.index', ['page' => 2]))->assertNotFound();
        $this->get(route('posts.index', ['page' => 0]))->assertNotFound();
        $this->get(route('posts.index', ['page' => 'abc']))->assertNotFound();
    }

    public function test_the_list_without_posts_is_an_empty_page_not_an_error(): void
    {
        $this->get(route('posts.index'))->assertOk()->assertInertia(
            fn (Assert $page) => $page->count('posts', 0)->where('pagination.last', 1)
        );
    }

    public function test_the_list_is_served_in_both_languages_with_a_fallback_for_missing_text(): void
    {
        $post = Post::factory()->create([
            'title' => ['en' => 'English only title'],
            'excerpt' => ['en' => 'English only excerpt'],
        ]);

        $this->get(route('posts.index'))->assertInertia(
            fn (Assert $page) => $page->where('posts.0.title', 'English only title')
        );

        $this->get(route('en.posts.index'))->assertInertia(
            fn (Assert $page) => $page
                ->where('posts.0.id', $post->id)
                ->where('posts.0.excerpt', 'English only excerpt')
        );
    }

    public function test_the_list_is_rendered_on_the_server_with_its_own_title_and_description(): void
    {
        $this->get(route('posts.index'))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('posts.index').'">', false)
            ->assertSee('hreflang="en" href="'.route('en.posts.index').'"', false)
            ->assertSee('BreadcrumbList', false);
    }

    public function test_a_published_post_has_a_page_of_its_own(): void
    {
        $post = Post::factory()->create([
            'title' => ['el' => 'Τίτλος', 'en' => 'The title'],
            'body' => ['el' => 'Κείμενο', 'en' => "Some **bold** text\n\n## A heading"],
            'cover_image_url' => 'https://example.org/cover.jpg',
            'source_url' => 'https://www.example.org/story',
        ]);

        $this->get(route('en.posts.show', $post))->assertOk()->assertInertia(
            fn (Assert $page) => $page
                ->component('Posts/Show')
                ->where('post.id', $post->id)
                ->where('post.title', 'The title')
                ->where('post.image', 'https://example.org/cover.jpg')
                ->where('post.source_url', 'https://www.example.org/story')
                ->where('post.body_html', fn ($html) => str_contains($html, '<strong>bold</strong>') && str_contains($html, '<h2>A heading</h2>'))
        );

        $this->get(route('posts.show', $post))->assertInertia(
            fn (Assert $page) => $page->where('post.title', 'Τίτλος')
        );
    }

    public function test_a_post_that_is_not_published_has_no_page(): void
    {
        foreach ([Post::factory()->draft(), Post::factory()->scheduled(), Post::factory()->archived()] as $factory) {
            $this->get(route('posts.show', $factory->create()))->assertNotFound();
        }

        $deleted = Post::factory()->create();
        $slug = $deleted->slug;
        $deleted->delete();

        $this->get(route('posts.show', $slug))->assertNotFound();
        $this->get(route('posts.show', 'no-such-post'))->assertNotFound();
    }

    public function test_the_markdown_of_a_body_is_made_safe(): void
    {
        $body = "<script>alert(1)</script>\n\n<img src=x onerror=alert(1)>\n\n[bad](javascript:alert(1))\n\n[good](https://example.org/page)";
        $post = Post::factory()->create(['body' => ['el' => $body, 'en' => $body]]);

        $html = $post->bodyHtml();

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('href="https://example.org/page"', $html);
        $this->assertStringContainsString('rel="nofollow noopener noreferrer"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
    }

    public function test_links_to_the_own_site_stay_in_the_same_tab(): void
    {
        config(['app.url' => 'https://nextrace.test']);

        $post = Post::factory()->create(['body' => ['el' => '[events](https://nextrace.test/events)', 'en' => '[events](https://nextrace.test/events)']]);

        $this->assertStringNotContainsString('target="_blank"', $post->bodyHtml());
    }

    public function test_a_source_that_is_not_a_web_address_is_not_offered(): void
    {
        $post = Post::factory()->create(['source_url' => 'javascript:alert(1)']);

        $this->get(route('posts.show', $post))->assertInertia(
            fn (Assert $page) => $page->where('post.source_url', null)
        );
    }

    public function test_the_page_of_a_post_describes_itself_to_search_engines(): void
    {
        $post = Post::factory()->create([
            'title' => ['en' => 'A very long title'],
            'excerpt' => ['en' => 'The excerpt'],
            'meta_title' => ['en' => 'Custom meta title'],
            'meta_description' => ['en' => 'Custom meta description'],
            'cover_image_url' => 'https://example.org/cover.jpg',
        ]);

        $this->get(route('en.posts.show', $post))
            ->assertOk()
            ->assertSee('Custom meta title - ', false)
            ->assertSee('name="description" content="Custom meta description"', false)
            ->assertSee('property="og:image" content="https://example.org/cover.jpg"', false)
            ->assertSee('property="og:type" content="article"', false)
            ->assertSee('BlogPosting', false)
            ->assertSee('datePublished', false)
            ->assertSee('BreadcrumbList', false);
    }

    public function test_the_meta_falls_back_to_the_title_and_the_excerpt(): void
    {
        $post = Post::factory()->create([
            'title' => ['en' => 'Plain title'],
            'excerpt' => ['en' => 'Plain excerpt'],
            'meta_title' => null,
            'meta_description' => null,
        ]);

        $this->get(route('en.posts.show', $post))
            ->assertSee('Plain title - ', false)
            ->assertSee('name="description" content="Plain excerpt"', false);
    }

    public function test_the_meta_falls_back_to_the_body_without_an_excerpt(): void
    {
        $post = Post::factory()->create([
            'title' => ['en' => 'Plain title'],
            'excerpt' => null,
            'meta_description' => null,
            'body' => ['en' => "First **paragraph** of the story.\n\nSecond."],
        ]);

        $this->get(route('en.posts.show', $post))
            ->assertSee('name="description" content="First paragraph of the story. Second."', false);
    }

    public function test_the_posts_are_in_the_sitemap_but_drafts_are_not(): void
    {
        $post = Post::factory()->create();
        $draft = Post::factory()->draft()->create();

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee('<loc>'.route('posts.index').'</loc>', false)
            ->assertSee('<loc>'.route('posts.show', $post).'</loc>', false)
            ->assertSee('<loc>'.route('en.posts.show', $post).'</loc>', false)
            ->assertDontSee(route('posts.show', $draft), false);
    }

    public function test_the_pages_stay_closed_to_search_engines_while_the_site_is_closed(): void
    {
        config(['site.indexable' => false]);
        $post = Post::factory()->create();

        $this->get(route('posts.index'))->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get(route('posts.show', $post))->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_the_pages_are_open_to_search_engines_when_the_site_is_open(): void
    {
        $post = Post::factory()->create();

        $this->get(route('posts.index'))->assertSee('content="index, follow"', false);
        $this->get(route('posts.show', $post))->assertSee('content="index, follow"', false);
    }
}
