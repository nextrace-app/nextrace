<?php

namespace Tests\Feature;

use App\Http\Middleware\RememberLocale;
use App\Models\Event;
use App\Models\FeatureFlag;
use App\Models\User;
use App\Support\Locale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LocalizationFlagTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->switchLocalization(false);
    }

    public function test_the_application_ships_the_flag_switched_off(): void
    {
        $this->assertDatabaseHas('feature_flags', ['key' => Locale::FEATURE]);

        $this->assertFalse(FeatureFlag::query()->where('key', Locale::FEATURE)->value('is_active'));
    }

    public function test_a_missing_flag_also_keeps_the_site_in_greek(): void
    {
        FeatureFlag::query()->where('key', Locale::FEATURE)->delete();

        $this->get('/en/events')->assertRedirect('/events');
    }

    public function test_english_pages_are_sent_to_the_same_greek_page_with_their_query(): void
    {
        $event = Event::factory()->create();

        $this->get('/en')->assertStatus(302)->assertRedirect('/');
        $this->get('/en/events?page=2')->assertStatus(302)->assertRedirect('/events?page=2');
        $this->get("/en/events/{$event->slug}")->assertRedirect("/events/{$event->slug}");
    }

    public function test_the_english_admin_is_sent_to_the_greek_one(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/en/admin')
            ->assertRedirect('/admin');
    }

    public function test_greek_pages_are_served_and_share_only_greek(): void
    {
        $this->get('/events')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('locale', 'el')
            ->where('supportedLocales', ['el']));
    }

    public function test_a_remembered_english_choice_is_neither_followed_nor_overwritten(): void
    {
        $this->withCookie(RememberLocale::COOKIE, 'en')
            ->get('/events')
            ->assertOk()
            ->assertCookieMissing(RememberLocale::COOKIE);
    }

    public function test_choosing_english_through_the_url_is_ignored(): void
    {
        $this->get('/events?locale=en')
            ->assertOk()
            ->assertCookieMissing(RememberLocale::COOKIE);
    }

    public function test_the_account_language_is_kept_for_when_english_returns(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->actingAs($user)->get('/events')->assertOk();
        $this->actingAs($user)->get('/en/events')->assertRedirect('/events');

        $this->assertSame('en', $user->fresh()->locale);
    }

    public function test_pages_name_no_alternate_languages(): void
    {
        $html = $this->get(route('events.index'))->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="'.route('events.index').'">', $html);
        $this->assertStringNotContainsString('hreflang=', $html);
    }

    public function test_the_sitemap_lists_only_greek_pages(): void
    {
        $event = Event::factory()->create();

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('<loc>'.route('events.show', $event).'</loc>', $xml);
        $this->assertStringNotContainsString(route('en.events.show', $event), $xml);
        $this->assertStringNotContainsString(url('/en'), $xml);
        $this->assertStringNotContainsString('hreflang=', $xml);
    }

    public function test_switching_the_flag_on_brings_english_back(): void
    {
        $this->switchLocalization(true);

        $this->get('/en/events')->assertOk();
        $this->assertSame('en', app()->getLocale());
    }
}
