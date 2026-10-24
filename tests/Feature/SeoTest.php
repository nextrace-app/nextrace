<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Support\PageMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    private function event(array $attributes = []): Event
    {
        return Event::factory()->create([
            'name' => ['en' => 'Heraklion Half Marathon', 'el' => 'Ημιμαραθώνιος Ηρακλείου'],
            'description' => ['en' => 'A fast course by the sea.', 'el' => 'Γρήγορη διαδρομή δίπλα στη θάλασσα.'],
            'distance_meters' => 21100,
            'starts_at' => '2027-04-18 09:30:00',
            'city' => 'Heraklion',
            'country' => 'GR',
            'latitude' => 35.3387,
            'longitude' => 25.1442,
            'image_url' => 'https://example.com/hm.jpg',
            'registration_url' => 'https://example.com/register',
            ...$attributes,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonLd(string $html): array
    {
        $this->assertSame(1, preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches));

        return json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
    }

    public function test_the_sitemap_lists_published_pages_in_both_locales_with_alternates(): void
    {
        $published = $this->event();
        $draft = $this->event(['name' => ['en' => 'Secret Draft', 'el' => 'Μυστικό']])->fill(['published_at' => null]);
        $draft->save();

        $response = $this->get('/sitemap.xml')->assertOk();

        $this->assertStringStartsWith('application/xml', $response->headers->get('Content-Type'));
        $xml = $response->getContent();

        foreach ([route('home'), route('el.home'), route('events.index'), route('el.events.index'), route('events.show', $published), route('el.events.show', $published)] as $url) {
            $this->assertStringContainsString("<loc>{$url}</loc>", $xml);
        }

        $this->assertStringContainsString('hreflang="x-default" href="'.route('events.show', $published).'"', $xml);
        $this->assertStringContainsString('hreflang="el" href="'.route('el.events.show', $published).'"', $xml);
        $this->assertStringContainsString('<lastmod>', $xml);
        $this->assertStringNotContainsString(route('events.show', $draft), $xml);
        $this->assertStringNotContainsString(route('el.events.show', $draft), $xml);
    }

    public function test_robots_allows_crawling_hides_account_pages_and_points_to_the_sitemap(): void
    {
        $response = $this->get('/robots.txt')->assertOk();

        $this->assertStringStartsWith('text/plain', $response->headers->get('Content-Type'));
        $body = $response->getContent();

        $this->assertStringContainsString("User-agent: *\nAllow: /", $body);
        foreach (['/login', '/el/login', '/register', '/el/register', '/forgot-password', '/reset-password', '/profile', '/el/profile', '/settings', '/dashboard', '/el/dashboard', '/favorites', '/el/favorites'] as $path) {
            $this->assertStringContainsString("Disallow: {$path}\n", $body);
        }
        $this->assertStringContainsString('Sitemap: '.route('sitemap'), $body);
        $this->assertStringNotContainsString('Disallow: /events', $body);
    }

    public function test_the_robots_file_is_not_redirected_for_greek_browsers(): void
    {
        $this->withHeader('Accept-Language', 'el-GR,el;q=0.9')->get('/robots.txt')->assertOk();
        $this->withHeader('Accept-Language', 'el-GR,el;q=0.9')->get('/sitemap.xml')->assertOk();
    }

    public function test_the_event_page_carries_localized_meta_tags_in_english(): void
    {
        $event = $this->event();

        $html = $this->get(route('events.show', $event))->assertOk()->getContent();
        $url = route('events.show', $event);

        $this->assertStringContainsString('<title>Heraklion Half Marathon: 21.1 km race in Heraklion - ', $html);
        $this->assertStringContainsString('<meta name="description" content="Heraklion Half Marathon, 21.1 km in Heraklion on April 18, 2027. A fast course by the sea.">', $html);
        $this->assertStringContainsString('<meta name="robots" content="index, follow">', $html);
        $this->assertStringContainsString("<link rel=\"canonical\" href=\"{$url}\">", $html);
        $this->assertStringContainsString('hreflang="el" href="'.route('el.events.show', $event).'"', $html);
        $this->assertStringContainsString('hreflang="x-default" href="'.$url.'"', $html);
        $this->assertStringContainsString('<meta property="og:type" content="article">', $html);
        $this->assertStringContainsString("<meta property=\"og:url\" content=\"{$url}\">", $html);
        $this->assertStringContainsString('<meta property="og:locale" content="en_US">', $html);
        $this->assertStringContainsString('<meta property="og:locale:alternate" content="el_GR">', $html);
        $this->assertStringContainsString('<meta property="og:image" content="https://example.com/hm.jpg">', $html);
        $this->assertStringContainsString('<meta property="og:site_name"', $html);
        $this->assertStringContainsString('<meta name="twitter:card" content="summary_large_image">', $html);

        $data = $this->jsonLd($html);
        $this->assertSame('SportsEvent', $data['@type']);
        $this->assertSame('Heraklion Half Marathon', $data['name']);
        $this->assertSame('2027-04-18T09:30:00+03:00', $data['startDate']);
        $this->assertArrayNotHasKey('endDate', $data);
        $this->assertSame('https://schema.org/EventScheduled', $data['eventStatus']);
        $this->assertSame('https://schema.org/OfflineEventAttendanceMode', $data['eventAttendanceMode']);
        $this->assertSame('Heraklion', $data['location']['address']['addressLocality']);
        $this->assertSame('GR', $data['location']['address']['addressCountry']);
        $this->assertSame(35.3387, $data['location']['geo']['latitude']);
        $this->assertSame('https://example.com/hm.jpg', $data['image']);
        $this->assertSame($url, $data['url']);
        $this->assertSame('en', $data['inLanguage']);
        $this->assertSame('https://example.com/register', $data['offers']['url']);
    }

    public function test_the_event_page_carries_localized_meta_tags_in_greek(): void
    {
        $event = $this->event();

        $html = $this->get(route('el.events.show', $event))->assertOk()->getContent();

        $this->assertStringContainsString('<title>Ημιμαραθώνιος Ηρακλείου: αγώνας 21.1 km, Heraklion - ', $html);
        $this->assertStringContainsString('<meta property="og:locale" content="el_GR">', $html);
        $this->assertStringContainsString('<meta property="og:locale:alternate" content="en_US">', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.route('el.events.show', $event).'">', $html);

        $data = $this->jsonLd($html);
        $this->assertSame('Ημιμαραθώνιος Ηρακλείου', $data['name']);
        $this->assertSame('el', $data['inLanguage']);
        $this->assertSame(route('el.events.show', $event), $data['url']);
    }

    public function test_a_midnight_start_is_a_plain_date_and_optional_fields_are_left_out(): void
    {
        $event = $this->event([
            'description' => ['en' => '', 'el' => ''],
            'starts_at' => '2027-04-18 00:00:00',
            'ends_at' => '2027-04-19 00:00:00',
            'latitude' => null,
            'longitude' => null,
            'image_url' => null,
            'registration_url' => null,
        ]);

        $html = $this->get(route('events.show', $event))->assertOk()->getContent();
        $data = $this->jsonLd($html);

        $this->assertSame('2027-04-18', $data['startDate']);
        $this->assertSame('2027-04-19', $data['endDate']);
        $this->assertArrayNotHasKey('geo', $data['location']);
        $this->assertArrayNotHasKey('image', $data);
        $this->assertArrayNotHasKey('offers', $data);
        $this->assertStringContainsString('<meta name="description" content="Heraklion Half Marathon is a 21.1 km running race in Heraklion, Greece, on April 18, 2027.">', $html);
        $this->assertStringContainsString('<meta name="twitter:card" content="summary">', $html);
        $this->assertStringNotContainsString('og:image', $html);
    }

    public function test_summer_start_times_use_the_daylight_saving_offset_and_json_is_escaped(): void
    {
        $this->assertSame('2027-07-10T07:00:00+03:00', PageMeta::schemaDate(Carbon::parse('2027-07-10 07:00:00')));
        $this->assertSame('2027-01-10T07:00:00+02:00', PageMeta::schemaDate(Carbon::parse('2027-01-10 07:00:00')));

        $event = $this->event(['name' => ['en' => 'Run </script><b>&', 'el' => 'x']]);
        $html = $this->get(route('events.show', $event))->assertOk()->getContent();

        $this->assertStringNotContainsString('Run </script>', $html);
        $this->assertSame('Run </script><b>&', $this->jsonLd($html)['name']);
    }

    public function test_the_home_and_events_pages_have_their_own_meta(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSee('<title>Running races in Greece and Crete - ', false)
            ->assertSee('<meta property="og:type" content="website">', false)
            ->assertDontSee('application/ld+json', false);

        $this->get(route('el.events.index'))->assertOk()
            ->assertSee('<title>Αγώνες δρόμου Κρήτη και Ελλάδα: ημερολόγιο αγώνων - ', false)
            ->assertSee('<link rel="canonical" href="'.route('el.events.index').'">', false)
            ->assertSee('hreflang="x-default" href="'.route('events.index').'"', false);
    }

    public function test_account_pages_are_not_indexable(): void
    {
        $this->get(route('login'))->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $this->get(route('el.register'))->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_an_unpublished_event_stays_hidden_and_leaks_no_structured_data(): void
    {
        $draft = $this->event(['name' => ['en' => 'Hidden Draft Race', 'el' => 'Κρυφός']]);
        $draft->forceFill(['published_at' => null])->save();

        foreach ([route('events.show', $draft), route('el.events.show', $draft)] as $url) {
            $response = $this->get($url)->assertNotFound();
            $this->assertStringNotContainsString('Hidden Draft Race', $response->getContent());
            $this->assertStringNotContainsString('application/ld+json', $response->getContent());
        }

        $this->assertStringNotContainsString('Hidden Draft Race', $this->get(route('events.index'))->getContent());
        $this->assertStringNotContainsString((string) route('events.show', $draft), $this->get('/sitemap.xml')->getContent());
    }
}
