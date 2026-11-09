<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_token_that_works(): void
    {
        $user = User::factory()->create();

        $this->artisan('api-token:create', ['name' => 'scout', '--ability' => ['events:read', 'events:write', 'posts:write'], '--user' => $user->email])
            ->expectsOutputToContain('nr_')
            ->assertSuccessful();

        $token = ApiToken::query()->sole();

        $this->assertSame('scout', $token->name);
        $this->assertSame(['events:read', 'events:write', 'posts:write'], $token->abilities);
        $this->assertSame($user->id, $token->user_id);
        $this->assertNull($token->expires_at);
    }

    public function test_it_sets_an_expiry(): void
    {
        $this->artisan('api-token:create', ['name' => 'scout', '--ability' => ['*'], '--expires' => '+90 days'])->assertSuccessful();

        $this->assertTrue(ApiToken::query()->sole()->expires_at->isFuture());
    }

    public function test_it_needs_an_ability(): void
    {
        $this->artisan('api-token:create', ['name' => 'scout'])->assertFailed();

        $this->assertDatabaseCount('api_tokens', 0);
    }

    public function test_it_rejects_an_unknown_ability(): void
    {
        $this->artisan('api-token:create', ['name' => 'scout', '--ability' => ['events:delete']])->assertFailed();

        $this->assertDatabaseCount('api_tokens', 0);
    }

    public function test_it_rejects_an_unknown_user(): void
    {
        $this->artisan('api-token:create', ['name' => 'scout', '--ability' => ['*'], '--user' => 'nobody@example.com'])->assertFailed();

        $this->assertDatabaseCount('api_tokens', 0);
    }

    public function test_it_rejects_an_expiry_in_the_past_or_unreadable(): void
    {
        $this->artisan('api-token:create', ['name' => 'scout', '--ability' => ['*'], '--expires' => '2020-01-01'])->assertFailed();
        $this->artisan('api-token:create', ['name' => 'scout', '--ability' => ['*'], '--expires' => 'whenever'])->assertFailed();

        $this->assertDatabaseCount('api_tokens', 0);
    }

    public function test_it_revokes_a_token(): void
    {
        $token = ApiToken::factory()->create();

        $this->artisan('api-token:revoke', ['id' => $token->id])->assertSuccessful();

        $this->assertModelMissing($token);
    }

    public function test_revoking_an_unknown_token_fails(): void
    {
        $this->artisan('api-token:revoke', ['id' => 999])->assertFailed();
    }
}
