<?php

namespace App\Console\Commands;

use App\Models\ApiToken;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Deletes an API token, which stops it working at once.
 */
#[Signature('api-token:revoke {id : The id printed by api-token:create}')]
#[Description('Revoke an API token')]
class RevokeApiToken extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $token = ApiToken::query()->whereKey($this->argument('id'))->first();

        if ($token === null) {
            $this->error('No token with that id.');

            return self::FAILURE;
        }

        $token->delete();

        $this->info("Token #{$token->id} ({$token->name}) revoked.");

        return self::SUCCESS;
    }
}
