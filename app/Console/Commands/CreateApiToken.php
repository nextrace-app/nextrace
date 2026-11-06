<?php

namespace App\Console\Commands;

use App\Models\ApiToken;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

/**
 * Issues an API token and prints it. The token cannot be read back later:
 * only its hash is stored.
 */
#[Signature('api-token:create {name : What the token is for, e.g. race-news-scout} {--ability=* : events:read, posts:write or * (repeat for several)} {--user= : Email or id of the user the token acts as} {--expires= : Date or period after which the token stops working, e.g. 2027-01-01 or "+90 days"}')]
#[Description('Create an API token and print it once')]
class CreateApiToken extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');

        if (! is_string($name) || trim($name) === '') {
            $this->error('Give the token a name.');

            return self::FAILURE;
        }

        /** @var list<string> $abilities */
        $abilities = array_values(array_unique((array) $this->option('ability')));

        if ($abilities === []) {
            $this->error('Give the token at least one --ability: '.implode(', ', ApiToken::ABILITIES).'.');

            return self::FAILURE;
        }

        $unknown = array_diff($abilities, ApiToken::ABILITIES);

        if ($unknown !== []) {
            $this->error('Unknown ability: '.implode(', ', $unknown).'. Use '.implode(', ', ApiToken::ABILITIES).'.');

            return self::FAILURE;
        }

        $user = null;
        $userOption = $this->option('user');

        if (is_string($userOption) && $userOption !== '') {
            $user = User::query()->where('email', $userOption)->orWhere('id', ctype_digit($userOption) ? (int) $userOption : 0)->first();

            if ($user === null) {
                $this->error("No user with the email or id {$userOption}.");

                return self::FAILURE;
            }
        }

        $expires = $this->option('expires');
        $expiresAt = null;

        if (is_string($expires) && $expires !== '') {
            try {
                $expiresAt = CarbonImmutable::parse($expires);
            } catch (Throwable) {
                $this->error("Cannot read the expiry {$expires}.");

                return self::FAILURE;
            }

            if ($expiresAt->isPast()) {
                $this->error('The expiry is already in the past.');

                return self::FAILURE;
            }
        }

        [$token, $plain] = ApiToken::issue(trim($name), $abilities, $user, $expiresAt);

        $this->info("Token #{$token->id} created. Copy it now, it is not shown again:");
        $this->line($plain);

        return self::SUCCESS;
    }
}
