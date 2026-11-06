<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ApiTokenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * An access token of the JSON API, for scripts and agents rather than visitors.
 *
 * Only the hash of the token is stored, so a lost token cannot be read back:
 * revoke it (delete the row) and issue a new one.
 *
 * @property int $id
 * @property string $name
 * @property string $token_hash
 * @property list<string> $abilities
 * @property int|null $user_id
 * @property CarbonImmutable|null $last_used_at
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'token_hash', 'abilities', 'user_id', 'last_used_at', 'expires_at'])]
#[Hidden(['token_hash'])]
class ApiToken extends Model
{
    /** @use HasFactory<ApiTokenFactory> */
    use HasFactory;

    /**
     * The ability that stands for every other one.
     */
    public const ALL = '*';

    public const EVENTS_READ = 'events:read';

    public const POSTS_WRITE = 'posts:write';

    /**
     * Every ability a token can be given.
     *
     * @var list<string>
     */
    public const ABILITIES = [self::ALL, self::EVENTS_READ, self::POSTS_WRITE];

    private const PREFIX = 'nr_';

    /**
     * Create a token and return it together with its plain text, which is
     * available only now.
     *
     * @param  list<string>  $abilities
     * @return array{0: self, 1: string}
     */
    public static function issue(string $name, array $abilities, ?User $user = null, ?CarbonImmutable $expiresAt = null): array
    {
        $plain = self::PREFIX.Str::random(40);

        $token = self::query()->create([
            'name' => $name,
            'token_hash' => self::hash($plain),
            'abilities' => $abilities,
            'user_id' => $user?->id,
            'expires_at' => $expiresAt,
        ]);

        return [$token, $plain];
    }

    /**
     * The token a plain text belongs to, if any.
     */
    public static function findByPlainText(string $plain): ?self
    {
        return self::query()->where('token_hash', self::hash($plain))->first();
    }

    public static function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Whether the token is past its expiry.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Whether the token may do what the ability stands for.
     */
    public function can(string $ability): bool
    {
        return in_array(self::ALL, $this->abilities, true) || in_array($ability, $this->abilities, true);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
