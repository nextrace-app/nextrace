<?php

namespace App\Policies;

class EventSeriesPolicy extends PermissionPolicy
{
    protected function resource(): string
    {
        return 'series';
    }
}
