<?php

namespace Tests\Feature;

use App\Http\Middleware\RememberLocale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class LocalePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_choosing_a_locale_is_remembered_and_cleans_up_the_url(): void
    {
        $this->get('/events?locale=en')
            ->assertRedirect('/en/events')
            ->assertCookie(RememberLocale::COOKIE, 'en');
    }

    public function test_choosing_the_default_locale_is_remembered_too(): void
    {
        $this->withCookie(RememberLocale::COOKIE, 'en')
            ->get('/events?locale=el')
            ->assertRedirect('/events')
            ->assertCookie(RememberLocale::COOKIE, 'el');
    }

    public function test_choosing_the_locale_a_page_already_carries_does_not_prefix_it_twice(): void
    {
        // The switcher links to the page in the target locale, so the choice
        // usually arrives on a URL that is already prefixed.
        $this->get('/en/events?locale=en')
            ->assertRedirect('/en/events')
            ->assertCookie(RememberLocale::COOKIE, 'en');
    }

    public function test_switching_away_from_a_prefixed_page_drops_the_prefix(): void
    {
        $this->get('/en/events?locale=el')
            ->assertRedirect('/events')
            ->assertCookie(RememberLocale::COOKIE, 'el');
    }

    public function test_query_parameters_survive_the_switch(): void
    {
        $this->get('/events?locale=en&page=2')->assertRedirect('/en/events?page=2');
        $this->get('/en/events?locale=el&page=2')->assertRedirect('/events?page=2');
    }

    public function test_visiting_a_prefixed_url_is_remembered(): void
    {
        $this->get('/en/events')
            ->assertOk()
            ->assertCookie(RememberLocale::COOKIE, 'en');
    }

    public function test_returning_visitors_are_sent_to_the_locale_they_chose(): void
    {
        $this->withCookie(RememberLocale::COOKIE, 'en')
            ->get('/events')
            ->assertRedirect('/en/events');
    }

    public function test_visitors_without_a_preference_stay_on_the_default_locale(): void
    {
        $this->get('/events')->assertOk();
    }

    public function test_an_unsupported_preference_is_ignored(): void
    {
        $this->withCookie(RememberLocale::COOKIE, 'de')->get('/events')->assertOk();
    }

    public function test_the_choice_is_stored_on_the_account_of_a_signed_in_user(): void
    {
        $user = User::factory()->create(['locale' => null]);

        $this->actingAs($user)->get('/en/events')->assertOk();

        $this->assertSame('en', $user->refresh()->locale);
    }

    public function test_the_account_preference_wins_over_the_cookie(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->actingAs($user)
            ->withCookie(RememberLocale::COOKIE, 'el')
            ->get('/events')
            ->assertRedirect('/en/events');
    }

    public function test_signed_links_are_never_redirected(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        // Moving the visitor to another locale would break the signature.
        $this->actingAs($user)
            ->withCookie(RememberLocale::COOKIE, 'en')
            ->get($url)
            ->assertRedirectContains('/dashboard');

        $this->assertTrue($user->refresh()->hasVerifiedEmail());
    }

    public function test_form_submissions_are_never_redirected(): void
    {
        $user = User::factory()->create();

        $this->withCookie(RememberLocale::COOKIE, 'en')
            ->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_responses_tell_caches_that_the_cookie_changes_the_page(): void
    {
        $vary = $this->get('/events')->headers->get('Vary');

        $this->assertStringContainsString('Cookie', (string) $vary);
    }
}
