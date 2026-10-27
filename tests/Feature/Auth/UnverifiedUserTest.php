<?php

namespace Tests\Feature\Auth;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UnverifiedUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every page that belongs to an account.
     *
     * @return array<string, array{string, string}>
     */
    public static function accountPages(): array
    {
        return [
            'profile' => ['get', 'profile.show'],
            'dashboard' => ['get', 'dashboard'],
            'favorites' => ['get', 'favorites.index'],
            'settings profile' => ['get', 'profile.edit'],
            'settings appearance' => ['get', 'appearance.edit'],
            'greek profile' => ['get', 'el.profile.show'],
        ];
    }

    #[DataProvider('accountPages')]
    public function test_an_unverified_user_only_gets_the_verification_notice(string $method, string $route): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->{$method}(route($route))->assertRedirect(route('verification.notice'));
    }

    public function test_an_unverified_user_cannot_favorite_an_event(): void
    {
        $user = User::factory()->unverified()->create();
        $event = Event::factory()->create();

        $this->actingAs($user)->post(route('events.favorite.store', $event))->assertRedirect(route('verification.notice'));

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_an_unverified_user_can_still_reach_the_notice_and_the_public_pages(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('verification.notice'))->assertOk();
        $this->actingAs($user)->get(route('events.index'))->assertOk();
        $this->actingAs($user)->get(route('home'))->assertOk();
    }

    public function test_a_verified_user_reaches_the_account_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('profile.show'))->assertOk();
        $this->actingAs($user)->get(route('favorites.index'))->assertOk();
        $this->actingAs($user)->get(route('profile.edit'))->assertOk();
    }
}
