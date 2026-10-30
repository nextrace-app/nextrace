<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_locale_is_served_without_a_url_prefix(): void
    {
        $this->get('/events')->assertOk();

        $this->assertSame('el', app()->getLocale());
    }

    public function test_home_page_is_served_without_a_url_prefix(): void
    {
        $this->get('/')->assertOk();

        $this->assertSame('el', app()->getLocale());
    }

    public function test_non_default_locale_is_served_behind_its_prefix(): void
    {
        $this->get('/en/events')->assertOk();

        $this->assertSame('en', app()->getLocale());
    }

    public function test_non_default_locale_home_page_is_served_behind_its_prefix(): void
    {
        $this->get('/en')->assertOk();

        $this->assertSame('en', app()->getLocale());
    }

    public function test_default_locale_prefix_is_not_routable(): void
    {
        $this->get('/el/events')->assertNotFound();
        $this->get('/el')->assertNotFound();
    }

    public function test_locale_is_shared_with_inertia(): void
    {
        $this->get('/en/events')->assertInertia(
            fn ($page) => $page
                ->where('locale', 'en')
                ->where('defaultLocale', 'el')
                ->where('supportedLocales', ['el', 'en'])
        );
    }

    public function test_bare_route_names_point_at_the_default_locale(): void
    {
        $this->assertSame(url('/events'), route('events.index'));
        $this->assertSame(url('/'), route('home'));
    }

    public function test_prefixed_route_names_point_at_their_locale(): void
    {
        $this->assertSame(url('/en/events'), route('en.events.index'));
        $this->assertSame(url('/en'), route('en.home'));
    }

    public function test_auth_pages_are_served_behind_the_locale_prefix(): void
    {
        $this->get('/en/login')->assertOk();
        $this->get('/en/register')->assertOk();
        $this->get('/en/forgot-password')->assertOk();

        $this->assertSame(url('/en/login'), route('en.login'));
    }

    public function test_guests_are_redirected_to_the_login_page_of_their_locale(): void
    {
        $this->get('/en/settings/profile')->assertRedirect(route('en.login'));
        $this->get('/settings/profile')->assertRedirect(route('login'));
    }

    public function test_settings_redirects_within_the_current_locale(): void
    {
        // A user per request: visiting a locale is remembered on the account,
        // so the same user would be carried back to the locale of the first one.
        $this->actingAs(User::factory()->create())->get('/en/settings')->assertRedirect(route('en.profile.edit'));
        $this->actingAs(User::factory()->create())->get('/settings')->assertRedirect(route('profile.edit'));
    }

    public function test_login_redirects_to_the_profile_of_its_locale(): void
    {
        $user = User::factory()->create();

        $this->post('/en/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('en.profile.show'));

        $this->assertAuthenticated();
    }

    public function test_pages_point_search_engines_at_their_other_languages(): void
    {
        $this->get('/en/events')
            ->assertSee('<link rel="canonical" href="'.url('/en/events').'">', false)
            ->assertSee('hreflang="el" href="'.url('/events').'"', false)
            ->assertSee('hreflang="en" href="'.url('/en/events').'"', false)
            ->assertSee('hreflang="x-default" href="'.url('/events').'"', false);
    }

    public function test_alternate_links_keep_the_query_string(): void
    {
        $this->get('/en/events?page=2')
            ->assertSee('hreflang="el" href="'.url('/events?page=2').'"', false)
            ->assertSee('<link rel="canonical" href="'.url('/en/events?page=2').'">', false);
    }

    public function test_logging_out_keeps_the_visitor_in_their_locale(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('en.logout'))->assertRedirect(route('en.home'));
        $this->assertGuest();

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_login_redirects_stay_in_their_locale_across_requests(): void
    {
        // Config is rewritten per request but survives inside one process, so the
        // prefixed locale must not leak into the request that follows it.
        $prefixed = User::factory()->create();
        $default = User::factory()->create();

        $this->post('/en/login', ['email' => $prefixed->email, 'password' => 'password'])
            ->assertRedirect(route('en.profile.show'));

        $this->post('/logout');

        $this->post('/login', ['email' => $default->email, 'password' => 'password'])
            ->assertRedirect(route('profile.show'));
    }

    public function test_locale_prefix_is_recomputed_for_each_request(): void
    {
        $this->get('/en/events')->assertOk();
        $this->assertSame('en', app()->getLocale());

        $this->get('/events')->assertOk();
        $this->assertSame('el', app()->getLocale());
    }
}
