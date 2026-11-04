<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Race;
use App\Models\RacePrice;
use App\Support\PageMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    private function event(array $attributes = [], array $race = []): Event
    {
        return Event::factory()->withMainRace(['distance_meters' => 21100, 'starts_at' => '2027-04-18 09:30:00', ...$race])->create([
            'name' => ['en' => 'Heraklion Half Marathon', 'el' => 'Ημιμαραθώνιος Ηρακλείου'],
            'description' => ['en' => 'A fast course by the sea.', 'el' => 'Γρήγορη διαδρομή δίπλα στη θάλασσα.'],
            'starts_on' => '2027-04-18',
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
    private function jsonLd(string $html, string $type = 'SportsEvent'): array
    {
        $blocks = array_filter(array_map(
            fn (string $block): array => json_decode($block, true, flags: JSON_THROW_ON_ERROR),
            $this->jsonLdBlocks($html),
        ), fn (array $block): bool => $block['@type'] === $type);

        $this->assertCount(1, $blocks, "Expected exactly one {$type} block.");

        return array_values($blocks)[0];
    }

    /**
     * @return list<string>
     */
    private function jsonLdBlocks(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

        return $matches[1];
    }

    public function test_the_sitemap_lists_published_pages_in_both_locales_with_alternates(): void
    {
        $published = $this->event();
        $draft = $this->event(['name' => ['en' => 'Secret Draft', 'el' => 'Μυστικό']])->fill(['published_at' => null]);
        $draft->save();

        $response = $this->get('/sitemap.xml')->assertOk();

        $this->assertStringStartsWith('application/xml', $response->headers->get('Content-Type'));
        $xml = $response->getContent();

        foreach ([route('home'), route('en.home'), route('events.index'), route('en.events.index'), route('events.show', $published), route('en.events.show', $published)] as $url) {
            $this->assertStringContainsString("<loc>{$url}</loc>", $xml);
        }

        $this->assertStringContainsString('hreflang="x-default" href="'.route('events.show', $published).'"', $xml);
        $this->assertStringContainsString('hreflang="el" href="'.route('events.show', $published).'"', $xml);
        $this->assertStringContainsString('hreflang="en" href="'.route('en.events.show', $published).'"', $xml);
        $this->assertStringContainsString('<lastmod>', $xml);
        $this->assertStringNotContainsString(route('events.show', $draft), $xml);
        $this->assertStringNotContainsString(route('en.events.show', $draft), $xml);
    }

    public function test_the_robots_command_hides_account_pages_and_points_to_the_sitemap(): void
    {
        config(['app.url' => 'https://example.test/']);
        $path = tempnam(sys_get_temp_dir(), 'robots');

        $this->artisan('seo:robots', ['--path' => $path])->assertSuccessful();

        $body = file_get_contents($path);
        unlink($path);

        $this->assertStringContainsString("User-agent: *\nAllow: /", $body);
        foreach (['/login', '/en/login', '/register', '/en/register', '/forgot-password', '/reset-password', '/profile', '/en/profile', '/settings', '/dashboard', '/en/dashboard', '/favorites', '/en/favorites'] as $path) {
            $this->assertStringContainsString("Disallow: {$path}\n", $body);
        }
        $this->assertStringContainsString("Sitemap: https://example.test/sitemap.xml\n", $body);
        $this->assertStringNotContainsString('Disallow: /events', $body);
    }

    public function test_the_static_robots_file_is_committed_and_keeps_crawlers_out(): void
    {
        $this->assertFileExists(public_path('robots.txt'));

        $this->assertSame("User-agent: *\nDisallow: /\n", file_get_contents(public_path('robots.txt')));
    }

    public function test_the_sitemap_is_not_redirected_for_greek_browsers(): void
    {
        $this->withHeader('Accept-Language', 'el-GR,el;q=0.9')->get('/sitemap.xml')->assertOk();
    }

    public function test_search_results_of_the_events_list_stay_out_of_the_index_but_pages_and_distance_filters_do_not(): void
    {
        $this->get(route('events.index', ['q' => 'marathon']))->assertSee('<meta name="robots" content="noindex, follow">', false);
        $this->get(route('en.events.index', ['q' => 'marathon']))->assertSee('<meta name="robots" content="noindex, follow">', false);

        $this->get(route('events.index'))->assertSee('<meta name="robots" content="index, follow">', false);
        $this->get(route('events.index', ['distance' => 'marathon']))->assertSee('<meta name="robots" content="index, follow">', false);
        $this->get(route('events.index', ['q' => '']))->assertSee('<meta name="robots" content="index, follow">', false);
    }

    public function test_the_event_page_carries_localized_meta_tags_in_english(): void
    {
        $event = $this->event();

        $html = $this->get(route('en.events.show', $event))->assertOk()->getContent();
        $url = route('en.events.show', $event);

        $this->assertStringContainsString('<title>Heraklion Half Marathon: 21.1 km race in Heraklion - ', $html);
        $this->assertStringContainsString('<meta name="description" content="Heraklion Half Marathon, 21.1 km in Heraklion on April 18, 2027. A fast course by the sea.">', $html);
        $this->assertStringContainsString('<meta name="robots" content="index, follow">', $html);
        $this->assertStringContainsString("<link rel=\"canonical\" href=\"{$url}\">", $html);
        $this->assertStringContainsString('hreflang="el" href="'.route('events.show', $event).'"', $html);
        $this->assertStringContainsString('hreflang="en" href="'.$url.'"', $html);
        $this->assertStringContainsString('hreflang="x-default" href="'.route('events.show', $event).'"', $html);
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

        $html = $this->get(route('events.show', $event))->assertOk()->getContent();

        $this->assertStringContainsString('<title>Ημιμαραθώνιος Ηρακλείου: αγώνας 21.1 km, Heraklion - ', $html);
        $this->assertStringContainsString('<meta property="og:locale" content="el_GR">', $html);
        $this->assertStringContainsString('<meta property="og:locale:alternate" content="en_US">', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.route('events.show', $event).'">', $html);

        $data = $this->jsonLd($html);
        $this->assertSame('Ημιμαραθώνιος Ηρακλείου', $data['name']);
        $this->assertSame('el', $data['inLanguage']);
        $this->assertSame(route('events.show', $event), $data['url']);
    }

    public function test_a_midnight_start_is_a_plain_date_and_optional_fields_are_left_out(): void
    {
        $event = $this->event([
            'description' => ['en' => '', 'el' => ''],
            'starts_on' => '2027-04-18',
            'ends_on' => '2027-04-19',
            'latitude' => null,
            'longitude' => null,
            'image_url' => null,
            'registration_url' => null,
        ], ['starts_at' => '2027-04-18 00:00:00', 'start_time_known' => false]);

        $html = $this->get(route('en.events.show', $event))->assertOk()->getContent();
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
        $html = $this->get(route('en.events.show', $event))->assertOk()->getContent();

        $this->assertStringNotContainsString('Run </script>', $html);
        $this->assertSame('Run </script><b>&', $this->jsonLd($html)['name']);
    }

    public function test_the_home_and_events_pages_have_their_own_meta(): void
    {
        $this->get(route('en.home'))->assertOk()
            ->assertSee('<title>Running races in Greece - ', false)
            ->assertSee('<meta property="og:type" content="website">', false);

        $this->get(route('events.index'))->assertOk()
            ->assertSee('<title>Αγώνες δρόμου Ελλάδα: ημερολόγιο αγώνων - ', false)
            ->assertSee('<link rel="canonical" href="'.route('events.index').'">', false)
            ->assertSee('hreflang="x-default" href="'.route('events.index').'"', false);
    }

    public function test_account_pages_are_not_indexable(): void
    {
        $this->get(route('login'))->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $this->get(route('en.register'))->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_an_unpublished_event_stays_hidden_and_leaks_no_structured_data(): void
    {
        $draft = $this->event(['name' => ['en' => 'Hidden Draft Race', 'el' => 'Κρυφός']]);
        $draft->forceFill(['published_at' => null])->save();

        foreach ([route('events.show', $draft), route('en.events.show', $draft)] as $url) {
            $response = $this->get($url)->assertNotFound();
            $this->assertStringNotContainsString('Hidden Draft Race', $response->getContent());
            $this->assertStringNotContainsString('application/ld+json', $response->getContent());
        }

        $this->assertStringNotContainsString('Hidden Draft Race', $this->get(route('en.events.index'))->getContent());
        $this->assertStringNotContainsString((string) route('events.show', $draft), $this->get('/sitemap.xml')->getContent());
    }

    public function test_every_race_is_a_sub_event_with_offers_and_the_event_has_an_organizer(): void
    {
        $organizer = Organizer::factory()->create(['name' => ['en' => 'Crete Runners', 'el' => 'Δρομείς Κρήτης'], 'website' => 'https://runners.example.com']);
        $event = $this->event(['organizer_id' => $organizer->id], ['name' => ['en' => 'Half marathon', 'el' => 'Ημιμαραθώνιος']]);

        $tenK = Race::factory()->for($event)->create([
            'name' => ['en' => '10 km', 'el' => '10 χλμ'],
            'distance_meters' => 10000,
            'starts_at' => '2027-04-18 10:30:00',
            'registration_url' => 'https://example.com/register-10k',
        ]);
        RacePrice::factory()->for($tenK)->create(['amount_cents' => 1550, 'currency' => 'EUR', 'valid_from' => '2027-01-01', 'valid_until' => '2027-03-31']);
        RacePrice::factory()->for($tenK)->create(['amount_cents' => 0, 'currency' => 'EUR', 'valid_from' => null, 'valid_until' => null]);
        Race::factory()->for($event)->startTimeUnknown()->create(['name' => ['en' => '5 km', 'el' => '5 χλμ'], 'distance_meters' => 5000, 'starts_at' => '2027-04-18 00:00:00']);

        $html = $this->get(route('en.events.show', $event))->assertOk()->getContent();
        $data = $this->jsonLd($html);

        $this->assertCount(3, $data['subEvent']);
        $this->assertSame('Crete Runners', $data['organizer']['name']);
        $this->assertSame('Organization', $data['organizer']['@type']);
        $this->assertSame('https://runners.example.com', $data['organizer']['url']);
        $this->assertStringContainsString('3 distances to choose from.', $html);

        $sub = collect($data['subEvent'])->firstWhere('name', '10 km');
        $this->assertSame('SportsEvent', $sub['@type']);
        $this->assertSame('2027-04-18T10:30:00+03:00', $sub['startDate']);
        $this->assertStringContainsString('10 km', $sub['description']);
        $this->assertSame('https://schema.org/EventScheduled', $sub['eventStatus']);
        $this->assertCount(2, $sub['offers']);
        $paid = collect($sub['offers'])->firstWhere('price', 15.5);
        $this->assertSame('EUR', $paid['priceCurrency']);
        $this->assertSame('2027-01-01', $paid['validFrom']);
        $this->assertSame('2027-03-31', $paid['validThrough']);
        $this->assertSame('https://example.com/register-10k', $paid['url']);

        $plain = collect($data['subEvent'])->first(fn (array $race) => $race['startDate'] === '2027-04-18' && str_contains($race['description'], '5 km'));
        $this->assertNotNull($plain);
    }

    public function test_a_single_race_event_keeps_its_description_and_one_sub_event(): void
    {
        $data = $this->jsonLd($this->get(route('en.events.show', $this->event()))->assertOk()->getContent());

        $this->assertCount(1, $data['subEvent']);
        $this->assertArrayNotHasKey('organizer', $data);
        $this->assertStringNotContainsString('distances', $data['description']);
    }

    public function test_the_event_status_is_mapped_to_schema_org(): void
    {
        $cases = [
            'scheduled' => 'https://schema.org/EventScheduled',
            'postponed' => 'https://schema.org/EventPostponed',
            'cancelled' => 'https://schema.org/EventCancelled',
        ];

        foreach ($cases as $status => $expected) {
            $event = $this->event(['status' => $status, 'name' => ['en' => "Race {$status}", 'el' => "Αγώνας {$status}"]]);
            $data = $this->jsonLd($this->get(route('en.events.show', $event))->assertOk()->getContent());

            $this->assertSame($expected, $data['eventStatus']);
            $this->assertSame($expected, $data['subEvent'][0]['eventStatus']);
        }
    }

    public function test_sub_event_text_is_escaped_too(): void
    {
        $event = $this->event();
        Race::factory()->for($event)->create(['name' => ['en' => '</script><i>&', 'el' => 'x'], 'distance_meters' => 3000]);

        $html = $this->get(route('en.events.show', $event))->assertOk()->getContent();

        $this->assertStringNotContainsString('</script><i>', $html);
        $this->assertContains('</script><i>&', array_column($this->jsonLd($html)['subEvent'], 'name'));
    }

    public function test_the_sitemap_lists_a_multi_race_event_once_per_locale(): void
    {
        $event = $this->event();
        Race::factory()->for($event)->create(['distance_meters' => 5000]);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertSame(1, substr_count($xml, '<loc>'.route('events.show', $event).'</loc>'));
        $this->assertSame(1, substr_count($xml, '<loc>'.route('en.events.show', $event).'</loc>'));
    }

    public function test_the_event_offers_sum_up_the_prices_of_its_races_and_say_whether_registration_is_open(): void
    {
        $event = $this->event(['registration_status' => 'open', 'registration_opens_at' => '2027-01-01 00:00:00', 'registration_closes_at' => '2027-04-10 23:59:00']);
        $tenK = Race::factory()->for($event)->create(['distance_meters' => 10000, 'starts_at' => '2027-04-18 10:30:00']);
        RacePrice::factory()->for($tenK)->create(['amount_cents' => 1550, 'currency' => 'EUR']);
        RacePrice::factory()->for($tenK)->create(['amount_cents' => 2500, 'currency' => 'EUR']);
        RacePrice::factory()->for($event->mainRace)->create(['amount_cents' => 3000, 'currency' => 'EUR']);

        $offers = $this->jsonLd($this->get(route('en.events.show', $event))->assertOk()->getContent())['offers'];

        $this->assertSame('AggregateOffer', $offers['@type']);
        $this->assertSame(15.5, $offers['lowPrice']);
        $this->assertSame(30, $offers['highPrice']);
        $this->assertSame('EUR', $offers['priceCurrency']);
        $this->assertSame(3, $offers['offerCount']);
        $this->assertSame('https://example.com/register', $offers['url']);
        $this->assertSame('https://schema.org/InStock', $offers['availability']);
        $this->assertStringStartsWith('2027-01-01T00:00:00', $offers['validFrom']);
        $this->assertStringStartsWith('2027-04-10T23:59:00', $offers['validThrough']);
    }

    public function test_the_availability_follows_the_state_of_registration(): void
    {
        $cases = [
            'open' => 'https://schema.org/InStock',
            'not_open' => 'https://schema.org/PreOrder',
            'sold_out' => 'https://schema.org/SoldOut',
        ];

        foreach ($cases as $status => $expected) {
            $event = $this->event(['registration_status' => $status, 'slug' => "race-{$status}"]);

            $offers = $this->jsonLd($this->get(route('en.events.show', $event))->assertOk()->getContent())['offers'];

            $this->assertSame('Offer', $offers['@type']);
            $this->assertSame($expected, $offers['availability']);
            $this->assertArrayNotHasKey('lowPrice', $offers);
        }

        foreach (['closed', 'unknown'] as $status) {
            $event = $this->event(['registration_status' => $status, 'slug' => "race-{$status}"]);

            $offers = $this->jsonLd($this->get(route('en.events.show', $event))->assertOk()->getContent())['offers'];

            $this->assertArrayNotHasKey('availability', $offers);
        }
    }

    public function test_an_event_with_nothing_to_offer_has_no_offers(): void
    {
        $event = $this->event(['registration_url' => null, 'registration_status' => 'unknown']);

        $this->assertArrayNotHasKey('offers', $this->jsonLd($this->get(route('en.events.show', $event))->assertOk()->getContent()));
    }

    public function test_prices_in_another_currency_are_left_out_of_the_range(): void
    {
        $event = $this->event();
        RacePrice::factory()->for($event->mainRace)->create(['amount_cents' => 2000, 'currency' => 'EUR']);
        RacePrice::factory()->for($event->mainRace)->create(['amount_cents' => 9900, 'currency' => 'USD']);

        $offers = $this->jsonLd($this->get(route('en.events.show', $event))->assertOk()->getContent())['offers'];

        $this->assertSame(20, $offers['highPrice']);
        $this->assertSame(1, $offers['offerCount']);
    }

    public function test_the_home_page_describes_the_site_and_the_organization_behind_it(): void
    {
        $html = $this->get(route('en.home'))->assertOk()->getContent();

        $site = $this->jsonLd($html, 'WebSite');
        $organization = $this->jsonLd($html, 'Organization');

        $this->assertSame(route('en.home'), $site['url']);
        $this->assertSame('en', $site['inLanguage']);
        $this->assertSame(config('app.name'), $site['name']);
        $this->assertSame($organization['@id'], $site['publisher']['@id']);
        $this->assertSame(url('/apple-touch-icon.png'), $organization['logo']);
        $this->assertSame(route('en.home'), $organization['url']);

        $this->assertSame('el', $this->jsonLd($this->get(route('home'))->getContent(), 'WebSite')['inLanguage']);
    }

    public function test_the_events_list_and_an_event_carry_a_trail_of_crumbs_in_their_language(): void
    {
        $list = $this->jsonLd($this->get(route('en.events.index'))->assertOk()->getContent(), 'BreadcrumbList');

        $this->assertSame(
            [['Home', route('en.home')], ['Races', route('en.events.index')]],
            array_map(fn (array $crumb) => [$crumb['name'], $crumb['item']], $list['itemListElement']),
        );

        $event = $this->event();
        $trail = $this->jsonLd($this->get(route('events.show', $event))->assertOk()->getContent(), 'BreadcrumbList');

        $this->assertSame(
            [['Αρχική', route('home')], ['Αγώνες', route('events.index')], ['Ημιμαραθώνιος Ηρακλείου', route('events.show', $event)]],
            array_map(fn (array $crumb) => [$crumb['name'], $crumb['item']], $trail['itemListElement']),
        );
        $this->assertSame([1, 2, 3], array_column($trail['itemListElement'], 'position'));
    }

    public function test_pages_without_fixed_copy_carry_no_structured_data(): void
    {
        $this->assertSame([], $this->jsonLdBlocks($this->get(route('login'))->getContent()));
    }
}
