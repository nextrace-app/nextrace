<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SiteIndexingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_site_that_is_not_indexable_says_so_in_the_header_the_meta_tag_and_the_sitemap(): void
    {
        config(['site.indexable' => false]);

        $this->get(route('home'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $this->get(route('en.events.index'))
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $this->get(route('sitemap'))->assertNotFound();
    }

    public function test_an_indexable_site_sends_no_noindex_header_and_serves_the_sitemap(): void
    {
        config(['site.indexable' => true]);

        $this->get(route('home'))
            ->assertOk()
            ->assertHeaderMissing('X-Robots-Tag')
            ->assertSee('<meta name="robots" content="index, follow">', false);
        $this->get(route('sitemap'))->assertOk();
    }

    public function test_the_robots_command_disallows_everything_while_the_site_is_not_indexable(): void
    {
        config(['site.indexable' => false, 'app.url' => 'https://example.test']);
        $path = tempnam(sys_get_temp_dir(), 'robots');

        $this->artisan('seo:robots', ['--path' => $path])->assertSuccessful();

        $body = file_get_contents($path);
        unlink($path);

        $this->assertSame("User-agent: *\nDisallow: /\n", $body);
    }

    public function test_the_robots_command_allows_crawling_once_the_site_is_indexable(): void
    {
        config(['site.indexable' => true, 'app.url' => 'https://example.test']);
        $path = tempnam(sys_get_temp_dir(), 'robots');

        $this->artisan('seo:robots', ['--path' => $path])->assertSuccessful();

        $body = file_get_contents($path);
        unlink($path);

        $this->assertStringContainsString("User-agent: *\nAllow: /", $body);
        $this->assertStringContainsString("Sitemap: https://example.test/sitemap.xml\n", $body);
    }

    public function test_the_back_soon_page_is_a_503_in_both_languages_and_stays_out_of_search_results(): void
    {
        config(['site.indexable' => false]);
        Route::get('/maintenance-preview', fn () => abort(503, '', ['Retry-After' => '3600']));
        Route::get('/en/maintenance-preview', fn () => abort(503));

        $this->get('/maintenance-preview')
            ->assertStatus(503)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertHeader('Retry-After', '3600')
            ->assertInertia(fn ($page) => $page->component('Error')->where('status', 503));
        $this->get('/en/maintenance-preview')
            ->assertStatus(503)
            ->assertInertia(fn ($page) => $page->component('Error')->where('status', 503));
    }
}
