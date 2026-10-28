<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_login_page_of_their_locale(): void
    {
        $this->get(route('profile.show'))->assertRedirect(route('login'));
        $this->get(route('el.profile.show'))->assertRedirect(route('el.login'));
    }

    public function test_a_signed_in_user_sees_their_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('profile.show'))->assertInertia(
            fn ($page) => $page
                ->component('Profile')
                ->where('auth.user.name', $user->name)
                ->where('auth.user.email', $user->email)
        );
    }

    public function test_the_page_is_served_under_every_locale(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('el.profile.show'))->assertInertia(
            fn ($page) => $page->component('Profile')->where('locale', 'el')
        );
    }

    /**
     * The page only reports the state of the address, so it stays reachable
     * while the address is still waiting to be verified.
     */
    public function test_a_user_with_an_unverified_email_can_still_open_it(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('profile.show'))->assertOk();
    }
}
