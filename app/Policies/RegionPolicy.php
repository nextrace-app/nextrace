<?php

namespace App\Policies;

class RegionPolicy extends PermissionPolicy
{
    protected function resource(): string
    {
        return 'regions';
    }
}
