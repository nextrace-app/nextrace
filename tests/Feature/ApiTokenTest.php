<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/events';

    public function test_a_request_without_a_token_is_unauthenticated(): void
    {
        $this->getJson(self::URL)
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate', 'Bearer');
    }

    public function test_an_unknown_token_is_unauthenticated(): void
    {
        $this->withToken('nr_unknown')->getJson(self::URL)->assertUnauthorized();
    }

    public function test_an_expired_token_is_unauthenticated(): void
    {
        [, $plain] = $this->issue(['*'], expired: true);

        $this->withToken($plain)->getJson(self::URL)->assertUnauthorized();
    }

    public function test_a_token_without_the_ability_is_forbidden(): void
    {
        [, $plain] = $this->issue([ApiToken::POSTS_WRITE]);

        $this->withToken($plain)->getJson(self::URL)->assertForbidden();
    }

    public function test_errors_are_json_even_when_the_client_does_not_ask_for_json(): void
    {
        $this->get(self::URL)->assertUnauthorized()->assertHeader('Content-Type', 'application/json');

        [, $plain] = $this->issue([ApiToken::POSTS_WRITE]);

        $this->withToken($plain)->get(self::URL)->assertForbidden()->assertHeader('Content-Type', 'application/json');
    }

    public function test_a_token_with_the_ability_or_the_wildcard_gets_through(): void
    {
        [, $specific] = $this->issue([ApiToken::EVENTS_READ]);
        [, $wildcard] = $this->issue(['*']);

        $this->withToken($specific)->getJson(self::URL)->assertOk();
        $this->withToken($wildcard)->getJson(self::URL)->assertOk();
    }

    public function test_only_the_hash_of_the_token_is_stored(): void
    {
        [$token, $plain] = $this->issue(['*']);

        $this->assertNotSame($plain, $token->token_hash);
        $this->assertSame(hash('sha256', $plain), $token->token_hash);
        $this->assertStringStartsWith('nr_', $plain);
        $this->assertArrayNotHasKey('token_hash', $token->toArray());
    }

    public function test_the_last_use_of_the_token_is_recorded(): void
    {
        [$token, $plain] = $this->issue(['*']);

        $this->assertNull($token->last_used_at);

        $this->withToken($plain)->getJson(self::URL)->assertOk();

        $this->assertNotNull($token->fresh()->last_used_at);
    }

    public function test_a_revoked_token_stops_working(): void
    {
        [$token, $plain] = $this->issue(['*']);

        $this->withToken($plain)->getJson(self::URL)->assertOk();

        $token->delete();

        $this->withToken($plain)->getJson(self::URL)->assertUnauthorized();
    }

    public function test_a_token_with_a_user_makes_it_the_user_of_the_request(): void
    {
        $user = User::factory()->create();
        [, $plain] = ApiToken::issue('scout', ['*'], $user);

        $this->app['router']->get('/api/v1/whoami', fn () => ['id' => request()->user()?->id])->middleware('api.token:*');

        $this->withToken($plain)->getJson('/api/v1/whoami')->assertOk()->assertJson(['id' => $user->id]);
    }

    /**
     * @param  list<string>  $abilities
     * @return array{0: ApiToken, 1: string}
     */
    private function issue(array $abilities, bool $expired = false): array
    {
        return ApiToken::issue('test', $abilities, expiresAt: $expired ? now()->subMinute()->toImmutable() : null);
    }
}
