<?php

namespace App\Policies;

class RacePolicy extends PermissionPolicy
{
    protected function resource(): string
    {
        return 'races';
    }
}
