<?php

namespace Database\Factories;

use App\Models\ApiToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ApiToken>
 */
class ApiTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'token_hash' => ApiToken::hash(Str::random(40)),
            'abilities' => [ApiToken::ALL],
            'user_id' => null,
            'last_used_at' => null,
            'expires_at' => null,
        ];
    }

    /**
     * A token that may only do the given things.
     *
     * @param  list<string>  $abilities
     */
    public function withAbilities(array $abilities): static
    {
        return $this->state(fn (): array => ['abilities' => $abilities]);
    }

    /**
     * A token whose expiry has passed.
     */
    public function expired(): static
    {
        return $this->state(fn (): array => ['expires_at' => now()->subMinute()]);
    }
}
