<?php

namespace Tests\Feature;

use App\Http\Middleware\RememberLocale;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Make Google answer the callback with the given account.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function fakeGoogle(array $attributes = [], bool $verified = true): void
    {
        $attributes = [
            'id' => '1234567890',
            'name' => 'Maria Papadaki',
            'email' => 'maria@example.com',
            ...$attributes,
        ];

        Socialite::fake('google', (new GoogleUser)->map($attributes)->setRaw([
            'email' => $attributes['email'],
            'email_verified' => $verified,
        ]));
    }

    public function test_the_login_and_register_pages_offer_google(): void
    {
        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();
    }

    public function test_the_redirect_sends_the_visitor_to_google(): void
    {
        $this->fakeGoogle();

        $this->get(route('auth.google.redirect'))->assertRedirect('https://socialite.fake/google/authorize');
    }

    public function test_a_new_visitor_gets_a_verified_account_without_a_password(): void
    {
        $this->fakeGoogle();

        $this->get(route('auth.google.callback'))->assertRedirect(route('home'));

        $user = User::where('email', 'maria@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('Maria Papadaki', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertFalse($user->hasPassword());
        $this->assertSame('en', $user->locale);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => '1234567890',
        ]);
    }

    public function test_the_new_account_speaks_the_language_the_visitor_was_browsing_in(): void
    {
        $this->fakeGoogle();

        $this->withCookie(RememberLocale::COOKIE, 'el')
            ->get(route('auth.google.callback'))
            ->assertRedirect(route('el.home'));

        $this->assertSame('el', User::where('email', 'maria@example.com')->firstOrFail()->locale);
    }

    public function test_a_returning_visitor_signs_in_to_the_linked_account(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);
        SocialAccount::factory()->create(['user_id' => $user->id, 'provider_id' => '1234567890']);

        // Google may report a different address by now; the link is what counts.
        $this->fakeGoogle(['email' => 'new@example.com']);

        $this->get(route('auth.google.callback'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::count());
    }

    public function test_google_links_to_the_account_that_owns_the_same_verified_address(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'maria@example.com']);

        $this->fakeGoogle();

        $this->get(route('auth.google.callback'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, User::count());
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertTrue($user->fresh()->hasPassword());
        $this->assertSame(1, $user->socialAccounts()->count());
    }

    public function test_an_address_google_has_not_verified_is_turned_away(): void
    {
        User::factory()->create(['email' => 'maria@example.com']);

        $this->fakeGoogle(verified: false);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseCount('social_accounts', 0);
    }

    public function test_a_declined_sign_in_returns_to_the_login_page(): void
    {
        Socialite::fake('google', fn () => throw new \RuntimeException('access_denied'));

        $this->get(route('auth.google.callback'))->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_signed_in_users_are_kept_away_from_the_google_routes(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('auth.google.redirect'))->assertRedirect();
        $this->get(route('auth.google.callback'))->assertRedirect();
    }

    public function test_the_confirm_password_page_tells_a_user_without_a_password_how_to_set_one(): void
    {
        $user = User::factory()->create(['password' => null]);

        $this->actingAs($user)
            ->get(route('password.confirm'))
            ->assertInertia(fn (Assert $page) => $page->where('hasPassword', false));
    }
}
