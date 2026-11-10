<?php

namespace App\Policies;

class FeatureFlagPolicy extends PermissionPolicy
{
    protected function resource(): string
    {
        return 'features';
    }
}
